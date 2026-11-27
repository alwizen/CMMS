<?php

namespace Tests\Feature;

use App\Filament\Resources\WorkOrders\Pages\EditWorkOrder;
use App\Filament\Resources\WorkOrders\RelationManagers\ActivitiesRelationManager as WorkOrderActivitiesRelationManager;
use App\Models\Activity;
use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\MaintenanceHistory;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderActivity;
use App\Models\WorkOrderWorker;
use Filament\Actions\Action;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class MaintenanceFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create();

        $company = Company::create(['code' => 'CO', 'name' => 'Demo Company']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'AR', 'name' => 'Demo Area']);
        $type = EquipmentType::create(['code' => 'MOV', 'name' => 'MOV']);

        $this->equipment = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $type->id,
            'tag_number' => 'MOV-TGL-502B',
            'description' => 'MOV OUTLET T.6 500B',
        ]);
    }

    public function test_on_hold_status_is_no_longer_available(): void
    {
        $this->assertArrayNotHasKey('On Hold', WorkOrder::STATUSES);
        $this->assertEqualsCanonicalizing(
            ['Pending', 'In Progress', 'Completed', 'Cancelled'],
            array_keys(WorkOrder::STATUSES),
        );
    }

    public function test_completing_work_order_creates_maintenance_history(): void
    {
        $workOrder = WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => User::first()->id,
            'classification' => 'Preventive',
            'start_at' => '2026-01-05 08:00:00',
            'finish_at' => '2026-01-05 10:30:00',
            'status' => 'In Progress',
        ]);

        $activity = Activity::create([
            'equipment_type_id' => $this->equipment->equipment_type_id,
            'name' => 'Cek Tekanan',
            'type' => 'measurement',
            'maintenance_classification' => 'preventive',
            'interval' => 'monthly',
            'answer_type' => 'quantitative',
            'unit' => 'bar',
            'status' => true,
        ]);

        WorkOrderActivity::create([
            'work_order_id' => $workOrder->id,
            'activity_id' => $activity->id,
            'final_result' => '4.4',
            'executed' => true,
            'note' => 'Normal',
        ]);

        $workOrder->update(['status' => 'Completed']);

        // Selesai dikerjakan belum membuat history: menunggu review SPV MOS.
        $this->assertNull(MaintenanceHistory::where('work_order_id', $workOrder->id)->first());
        $this->assertSame(WorkOrder::APPROVAL_PENDING, $workOrder->fresh()->approval_status);

        $workOrder->update([
            'approval_status' => WorkOrder::APPROVAL_APPROVED,
            'approved_by' => User::first()->id,
            'approved_at' => now(),
        ]);

        $history = MaintenanceHistory::where('work_order_id', $workOrder->id)->first();

        $this->assertNotNull($history);
        $this->assertSame(150, $history->duration_minutes);
        $this->assertSame('Completed', $history->status);
        $this->assertSame('4.4', $history->findings);
        $this->assertSame('Normal', $history->actions_taken);

        $workOrder->update(['status' => 'In Progress']);
        $workOrder->update(['status' => 'Completed']);

        $this->assertSame(WorkOrder::APPROVAL_PENDING, $workOrder->fresh()->approval_status);
        $this->assertSame(1, MaintenanceHistory::where('work_order_id', $workOrder->id)->count());
    }

    public function test_completed_work_order_history_uses_technician_coordinator_as_performed_by(): void
    {
        $coordinator = User::factory()->create();
        $otherWorker = User::factory()->create();

        $workOrder = WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => User::first()->id,
            'technician_coordinator_id' => $coordinator->id,
            'classification' => 'Preventive',
            'start_at' => '2026-01-05 08:00:00',
            'finish_at' => '2026-01-05 09:00:00',
            'status' => 'In Progress',
        ]);

        WorkOrderWorker::create([
            'work_order_id' => $workOrder->id,
            'user_id' => $otherWorker->id,
            'role' => 'Teknisi',
        ]);

        $workOrder->update(['status' => 'Completed']);

        $this->assertNull(MaintenanceHistory::where('work_order_id', $workOrder->id)->first());

        $workOrder->update([
            'approval_status' => WorkOrder::APPROVAL_APPROVED,
            'approved_by' => User::first()->id,
            'approved_at' => now(),
        ]);

        $history = MaintenanceHistory::where('work_order_id', $workOrder->id)->first();

        $this->assertNotNull($history);
        $this->assertSame($coordinator->id, $history->performed_by);
    }

    public function test_admin_photo_action_opens_modal_with_photos(): void
    {
        Gate::before(fn (): bool => true);

        $user = User::factory()->create();
        $this->actingAs($user);

        $workOrder = WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $user->id,
            'classification' => 'Preventive',
            'start_at' => now(),
            'status' => WorkOrder::STATUSES['In Progress'],
        ]);

        $activity = WorkOrderActivity::create([
            'work_order_id' => $workOrder->id,
            'name' => 'Cek visual',
            'executed' => true,
            'photos' => ['work-order-activities/foto-1.jpeg'],
        ]);

        $component = Livewire::test(WorkOrderActivitiesRelationManager::class, [
            'ownerRecord' => $workOrder,
            'pageClass' => EditWorkOrder::class,
        ])
            ->assertOk()
            ->assertTableActionVisible('photos', $activity)
            ->mountTableAction('photos', $activity);

        $this->assertNotNull($component->instance()->getMountedAction());

        $html = view('filament.work-order-activity-photos', ['record' => $activity->fresh()])->render();

        $this->assertStringContainsString('foto-1.jpeg', $html);
        $this->assertStringContainsString('1 foto', $html);

        // Badge kolom foto terhubung ke action 'photos' (klik membuka modal).
        $column = $component->instance()->getTable()->getColumn('photos');

        $this->assertInstanceOf(Action::class, $column->getAction());
        $this->assertSame('photos', $column->getAction()->getName());
    }

    public function test_admin_checklist_table_shows_photo_count(): void
    {
        Gate::before(fn (): bool => true);

        $user = User::factory()->create();
        $this->actingAs($user);

        $workOrder = WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $user->id,
            'classification' => 'Preventive',
            'start_at' => now(),
            'status' => WorkOrder::STATUSES['In Progress'],
        ]);

        WorkOrderActivity::create([
            'work_order_id' => $workOrder->id,
            'name' => 'Cek visual',
            'executed' => true,
            'photos' => ['work-order-activities/foto-1.jpeg'],
        ]);

        Livewire::test(WorkOrderActivitiesRelationManager::class, [
            'ownerRecord' => $workOrder,
            'pageClass' => EditWorkOrder::class,
        ])
            ->assertOk()
            ->assertSee('1 foto')
            ->assertDontSee('0 foto');
    }

    public function test_admin_checklist_form_uses_dropdown_for_qualitative_activity(): void
    {
        Gate::before(fn (): bool => true);

        $user = User::factory()->create();
        $this->actingAs($user);

        $workOrder = WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $user->id,
            'classification' => 'Preventive',
            'start_at' => now(),
            'status' => WorkOrder::STATUSES['In Progress'],
        ]);

        $activity = Activity::create([
            'equipment_type_id' => $this->equipment->equipment_type_id,
            'name' => 'Cek visual',
            'type' => 'visual_check',
            'maintenance_classification' => 'preventive',
            'interval' => 'monthly',
            'answer_type' => 'qualitative',
            'status' => true,
        ]);

        $workOrderActivity = WorkOrderActivity::create([
            'work_order_id' => $workOrder->id,
            'activity_id' => $activity->id,
            'name' => $activity->name,
            'executed' => false,
        ]);

        Livewire::test(WorkOrderActivitiesRelationManager::class, [
            'ownerRecord' => $workOrder,
            'pageClass' => EditWorkOrder::class,
        ])
            ->mountTableAction('edit', $workOrderActivity)
            ->fillForm([
                'pre_inspection' => 'Poor',
                'final_result' => 'Good',
            ])
            ->callMountedAction()
            ->assertHasNoTableActionErrors();

        $workOrderActivity->refresh();

        $this->assertSame('Poor', $workOrderActivity->pre_inspection);
        $this->assertSame('Good', $workOrderActivity->final_result);
    }

    public function test_admin_checklist_form_keeps_free_text_for_quantitative_activity(): void
    {
        Gate::before(fn (): bool => true);

        $user = User::factory()->create();
        $this->actingAs($user);

        $workOrder = WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $user->id,
            'classification' => 'Preventive',
            'start_at' => now(),
            'status' => WorkOrder::STATUSES['In Progress'],
        ]);

        $activity = Activity::create([
            'equipment_type_id' => $this->equipment->equipment_type_id,
            'name' => 'Ukur tekanan',
            'type' => 'measurement',
            'maintenance_classification' => 'preventive',
            'interval' => 'monthly',
            'answer_type' => 'quantitative',
            'unit' => 'bar',
            'status' => true,
        ]);

        $workOrderActivity = WorkOrderActivity::create([
            'work_order_id' => $workOrder->id,
            'activity_id' => $activity->id,
            'name' => $activity->name,
            'executed' => false,
        ]);

        Livewire::test(WorkOrderActivitiesRelationManager::class, [
            'ownerRecord' => $workOrder,
            'pageClass' => EditWorkOrder::class,
        ])
            ->mountTableAction('edit', $workOrderActivity)
            ->fillForm([
                'pre_inspection' => '4.1',
                'final_result' => '4.4',
            ])
            ->callMountedAction()
            ->assertHasNoTableActionErrors();

        $workOrderActivity->refresh();

        $this->assertSame('4.1', $workOrderActivity->pre_inspection);
        $this->assertSame('4.4', $workOrderActivity->final_result);
    }

    public function test_work_order_edit_page_renders_with_relation_managers(): void
    {
        Gate::before(fn (): bool => true);

        $this->actingAs(User::firstOrFail());

        $workOrder = WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => User::first()->id,
            'classification' => 'Preventive',
            'start_at' => '2026-01-05 08:00:00',
            'status' => 'Pending',
        ]);

        Livewire::test(EditWorkOrder::class, ['record' => $workOrder->getKey()])
            ->assertSuccessful()
            ->assertSeeLivewire(WorkOrderActivitiesRelationManager::class)
            ->assertSee('Pelaksana');
    }
}
