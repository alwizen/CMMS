<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\MaintenanceRequest;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestStatusSyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected MaintenanceRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();

        $this->user = User::factory()->create();

        $company = Company::create(['code' => 'CO', 'name' => 'Demo Company']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'AR', 'name' => 'Demo Area']);
        $type = EquipmentType::create(['code' => 'pompa', 'name' => 'Pompa']);

        $equipment = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $type->id,
            'tag_number' => 'P-101',
            'description' => 'Pompa utama',
        ]);

        $this->request = MaintenanceRequest::create([
            'equipment_id' => $equipment->id,
            'reported_by' => $this->user->id,
            'operation_status' => 'Faulty',
            'description' => 'Seal bocor',
            'damage_date' => now()->toDateString(),
            'status' => MaintenanceRequest::STATUSES['Approved'],
        ]);
    }

    protected function makeWorkOrder(array $attributes = []): WorkOrder
    {
        return WorkOrder::create([
            'equipment_id' => $this->request->equipment_id,
            'maintenance_request_id' => $this->request->id,
            'issued_by' => $this->user->id,
            'classification' => 'Corrective',
            'start_at' => now(),
            'status' => WorkOrder::STATUSES['Pending'],
            ...$attributes,
        ]);
    }

    public function test_creating_work_order_moves_open_request_to_assigned(): void
    {
        $this->request->update(['status' => MaintenanceRequest::STATUSES['Open']]);

        $this->makeWorkOrder();

        $this->assertSame(MaintenanceRequest::STATUSES['Assigned'], $this->request->fresh()->status);
    }

    public function test_creating_work_order_keeps_approved_request_approved(): void
    {
        $this->makeWorkOrder();

        $this->assertSame(MaintenanceRequest::STATUSES['Approved'], $this->request->fresh()->status);
    }

    public function test_starting_work_order_moves_request_to_in_progress(): void
    {
        $workOrder = $this->makeWorkOrder();
        $workOrder->update(['status' => WorkOrder::STATUSES['In Progress']]);

        $this->assertSame(MaintenanceRequest::STATUSES['In Progress'], $this->request->fresh()->status);
    }

    public function test_on_hold_work_order_keeps_request_in_progress(): void
    {
        $workOrder = $this->makeWorkOrder();
        $workOrder->update(['status' => WorkOrder::STATUSES['In Progress']]);
        $workOrder->update(['status' => WorkOrder::STATUSES['On Hold']]);

        $this->assertSame(MaintenanceRequest::STATUSES['In Progress'], $this->request->fresh()->status);
    }

    public function test_completing_work_order_completes_request(): void
    {
        $workOrder = $this->makeWorkOrder();
        $workOrder->update(['status' => WorkOrder::STATUSES['In Progress']]);
        $workOrder->update([
            'status' => WorkOrder::STATUSES['Completed'],
            'finish_at' => now(),
        ]);

        $this->assertSame(MaintenanceRequest::STATUSES['Completed'], $this->request->fresh()->status);
    }

    public function test_request_completed_only_when_all_work_orders_closed(): void
    {
        $first = $this->makeWorkOrder();
        $second = $this->makeWorkOrder();

        $first->update(['status' => WorkOrder::STATUSES['Completed'], 'finish_at' => now()]);

        $this->assertSame(MaintenanceRequest::STATUSES['Approved'], $this->request->fresh()->status);

        $second->update(['status' => WorkOrder::STATUSES['Completed'], 'finish_at' => now()]);

        $this->assertSame(MaintenanceRequest::STATUSES['Completed'], $this->request->fresh()->status);
    }

    public function test_completed_and_cancelled_work_orders_close_the_request(): void
    {
        $first = $this->makeWorkOrder();
        $second = $this->makeWorkOrder();

        $first->update(['status' => WorkOrder::STATUSES['Completed'], 'finish_at' => now()]);
        $second->update(['status' => WorkOrder::STATUSES['Cancelled']]);

        $this->assertSame(MaintenanceRequest::STATUSES['Completed'], $this->request->fresh()->status);
    }

    public function test_all_cancelled_work_orders_leave_request_untouched(): void
    {
        $first = $this->makeWorkOrder();
        $second = $this->makeWorkOrder();

        $first->update(['status' => WorkOrder::STATUSES['Cancelled']]);
        $second->update(['status' => WorkOrder::STATUSES['Cancelled']]);

        $this->assertSame(MaintenanceRequest::STATUSES['Approved'], $this->request->fresh()->status);
    }

    public function test_rejected_request_is_not_overwritten(): void
    {
        $this->request->update(['status' => MaintenanceRequest::STATUSES['Rejected']]);

        $workOrder = $this->makeWorkOrder();
        $workOrder->update(['status' => WorkOrder::STATUSES['In Progress']]);

        $this->assertSame(MaintenanceRequest::STATUSES['Rejected'], $this->request->fresh()->status);
    }

    public function test_completed_request_is_not_reopened(): void
    {
        $this->request->update(['status' => MaintenanceRequest::STATUSES['Completed']]);

        $workOrder = $this->makeWorkOrder();
        $workOrder->update(['status' => WorkOrder::STATUSES['In Progress']]);

        $this->assertSame(MaintenanceRequest::STATUSES['Completed'], $this->request->fresh()->status);
    }
}
