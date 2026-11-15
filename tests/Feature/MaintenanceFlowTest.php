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

        $history = MaintenanceHistory::where('work_order_id', $workOrder->id)->first();

        $this->assertNotNull($history);
        $this->assertSame(150, $history->duration_minutes);
        $this->assertSame('Completed', $history->status);
        $this->assertSame('4.4', $history->findings);
        $this->assertSame('Normal', $history->actions_taken);

        $workOrder->update(['status' => 'In Progress']);
        $workOrder->update(['status' => 'Completed']);

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

        $history = MaintenanceHistory::where('work_order_id', $workOrder->id)->first();

        $this->assertNotNull($history);
        $this->assertSame($coordinator->id, $history->performed_by);
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
            ->assertSee('Teknisi Pelaksana');
    }
}
