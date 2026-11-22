<?php

namespace Tests\Feature;

use App\Filament\Resources\WorkOrders\Pages\ViewWorkOrder;
use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\MaintenanceHistory;
use App\Models\MaintenanceRequest;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderActivity;
use App\Notifications\WorkOrderReviewRejectedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkOrderReviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $spv;

    protected User $technician;

    protected Equipment $equipment;

    protected WorkOrder $workOrder;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();
        config(['app.env' => 'local']);
        Gate::before(fn (): bool => true);

        foreach (['super_admin', 'spv_mos', 'mos'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->spv = User::factory()->create();
        $this->spv->assignRole('spv_mos');

        $this->technician = User::factory()->create(['fungsi' => 'MOS PM Reborn']);
        $this->technician->assignRole('mos');

        $company = Company::create(['code' => 'CO', 'name' => 'Demo Company']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'AR', 'name' => 'Demo Area']);
        $type = EquipmentType::create(['code' => 'pump', 'name' => 'Pompa', 'fungsi' => 'MOS PM Reborn']);

        $this->equipment = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $type->id,
            'tag_number' => 'P-101',
        ]);

        $this->workOrder = WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->spv->id,
            'technician_coordinator_id' => $this->technician->id,
            'fungsi' => 'MOS PM Reborn',
            'classification' => 'Corrective',
            'start_at' => now(),
            'status' => WorkOrder::STATUSES['In Progress'],
        ]);

        WorkOrderActivity::create([
            'work_order_id' => $this->workOrder->id,
            'name' => 'Ganti seal',
            'final_result' => 'Seal diganti',
            'executed' => true,
        ]);
    }

    protected function completeWorkOrder(): void
    {
        $this->workOrder->update([
            'status' => WorkOrder::STATUSES['Completed'],
            'finish_at' => now(),
        ]);
    }

    public function test_completed_work_order_waits_for_spv_review(): void
    {
        $this->completeWorkOrder();

        $this->workOrder->refresh();

        $this->assertSame(WorkOrder::STATUSES['Completed'], $this->workOrder->status);
        $this->assertSame(WorkOrder::APPROVAL_PENDING, $this->workOrder->approval_status);
        $this->assertTrue($this->workOrder->isAwaitingReview());
        $this->assertFalse($this->workOrder->isFinalized());
        $this->assertNull(MaintenanceHistory::where('work_order_id', $this->workOrder->id)->first());
    }

    public function test_spv_mos_can_approve_and_history_is_created(): void
    {
        $this->completeWorkOrder();

        $this->actingAs($this->spv);

        Livewire::test(ViewWorkOrder::class, ['record' => $this->workOrder->getKey()])
            ->assertActionVisible('approveWorkOrder')
            ->assertActionVisible('rejectWorkOrder')
            ->callAction('approveWorkOrder', data: ['approval_notes' => 'Sesuai hasil pekerjaan'])
            ->assertHasNoActionErrors();

        $this->workOrder->refresh();

        $this->assertSame(WorkOrder::APPROVAL_APPROVED, $this->workOrder->approval_status);
        $this->assertSame($this->spv->id, $this->workOrder->approved_by);
        $this->assertNotNull($this->workOrder->approved_at);
        $this->assertTrue($this->workOrder->isFinalized());

        $history = MaintenanceHistory::where('work_order_id', $this->workOrder->id)->first();

        $this->assertNotNull($history);
        $this->assertSame('Seal diganti', $history->findings);
    }

    public function test_spv_mos_rejection_reopens_work_order_and_notifies_technician(): void
    {
        Notification::fake();

        $this->completeWorkOrder();

        $this->actingAs($this->spv);

        Livewire::test(ViewWorkOrder::class, ['record' => $this->workOrder->getKey()])
            ->callAction('rejectWorkOrder', data: ['approval_notes' => 'Foto kurang jelas'])
            ->assertHasNoActionErrors();

        $this->workOrder->refresh();

        $this->assertSame(WorkOrder::APPROVAL_REJECTED, $this->workOrder->approval_status);
        $this->assertSame(WorkOrder::STATUSES['In Progress'], $this->workOrder->status);
        $this->assertNull($this->workOrder->finish_at);
        $this->assertSame('Foto kurang jelas', $this->workOrder->approval_notes);
        $this->assertNull(MaintenanceHistory::where('work_order_id', $this->workOrder->id)->first());

        Notification::assertSentTo(
            $this->technician,
            WorkOrderReviewRejectedNotification::class,
        );
    }

    public function test_technician_cannot_see_review_actions(): void
    {
        $this->completeWorkOrder();

        $this->actingAs($this->technician);

        Livewire::test(ViewWorkOrder::class, ['record' => $this->workOrder->getKey()])
            ->assertActionHidden('approveWorkOrder')
            ->assertActionHidden('rejectWorkOrder');
    }

    public function test_approving_completed_work_order_completes_related_request(): void
    {
        $request = MaintenanceRequest::create([
            'equipment_id' => $this->equipment->id,
            'reported_by' => $this->spv->id,
            'operation_status' => 'Faulty',
            'description' => 'Seal bocor',
            'damage_date' => now(),
            'status' => MaintenanceRequest::STATUSES['In Progress'],
        ]);

        $this->workOrder->update(['maintenance_request_id' => $request->id]);
        $this->completeWorkOrder();

        $this->assertSame(MaintenanceRequest::STATUSES['In Progress'], $request->fresh()->status);

        $this->actingAs($this->spv);

        Livewire::test(ViewWorkOrder::class, ['record' => $this->workOrder->getKey()])
            ->callAction('approveWorkOrder')
            ->assertHasNoActionErrors();

        $this->assertSame(MaintenanceRequest::STATUSES['Completed'], $request->fresh()->status);
    }

    public function test_checklist_cannot_be_edited_after_completion_via_api(): void
    {
        $this->completeWorkOrder();

        $activity = $this->workOrder->workOrderActivities()->firstOrFail();

        Sanctum::actingAs($this->technician, ['*']);

        $this->putJson("/api/work-orders/{$this->workOrder->id}/activities/{$activity->id}", [
            'final_result' => 'Diubah setelah selesai',
        ])
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->assertSame('Seal diganti', $activity->fresh()->final_result);
    }
}
