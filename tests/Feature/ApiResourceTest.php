<?php

namespace Tests\Feature;

use App\Http\Resources\EquipmentResource;
use App\Http\Resources\MaintenancePlanResource;
use App\Http\Resources\MaintenanceRequestResource;
use App\Http\Resources\MaintenanceScheduleResource;
use App\Http\Resources\WorkOrderResource;
use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\MaintenancePlan;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceSchedule;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderWorker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiResourceTest extends TestCase
{
    use RefreshDatabase;

    protected Equipment $equipment;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();

        $this->user = User::factory()->create();

        $company = Company::create(['code' => 'CO', 'name' => 'Demo Company']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'AR', 'name' => 'Demo Area']);
        $type = EquipmentType::create(['code' => 'pompa', 'name' => 'Pompa']);

        $this->equipment = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $type->id,
            'tag_number' => 'P-101',
            'description' => 'Pompa sentrifugal utama',
        ]);
    }

    protected function plan(): MaintenancePlan
    {
        return MaintenancePlan::create([
            'equipment_id' => $this->equipment->id,
            'maintenance_classification' => 'Preventive',
            'interval' => 'monthly',
            'start_date' => today()->toDateString(),
            'created_by' => $this->user->id,
            'status' => 'Active',
        ]);
    }

    protected function workOrder(array $attributes = []): WorkOrder
    {
        return WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->user->id,
            'classification' => 'Corrective',
            'start_at' => now(),
            'status' => 'Pending',
            ...$attributes,
        ]);
    }

    public function test_plan_resource_exposes_area_and_company_when_loaded(): void
    {
        $plan = $this->plan()->load(['equipment.area.company']);

        $data = (new MaintenancePlanResource($plan))->toArray(Request::create('/'));

        $this->assertSame('Demo Area', $data['equipment']['area']['name']);
        $this->assertSame('Demo Company', $data['equipment']['area']['company']['name']);
    }

    public function test_schedule_resource_exposes_area_when_loaded(): void
    {
        $plan = $this->plan();
        $schedule = MaintenanceSchedule::create([
            'maintenance_plan_id' => $plan->id,
            'equipment_id' => $this->equipment->id,
            'scheduled_date' => today()->toDateString(),
            'status' => MaintenanceSchedule::STATUSES['Scheduled'],
        ])->load(['equipment.area']);

        $data = (new MaintenanceScheduleResource($schedule))->toArray(Request::create('/'));

        $this->assertSame('Demo Area', $data['equipment']['area']['name']);
    }

    public function test_work_order_resource_exposes_area_when_loaded(): void
    {
        $workOrder = $this->workOrder()->load(['equipment.area']);

        $data = (new WorkOrderResource($workOrder))->toArray(Request::create('/'));

        $this->assertSame('Demo Area', $data['equipment']['area']['name']);
    }

    public function test_request_resource_exposes_area_when_loaded(): void
    {
        $request = MaintenanceRequest::create([
            'equipment_id' => $this->equipment->id,
            'reported_by' => $this->user->id,
            'operation_status' => 'Faulty',
            'description' => 'Seal bocor',
            'damage_date' => today()->toDateString(),
            'status' => MaintenanceRequest::STATUSES['Open'],
        ])->load(['equipment.area']);

        $data = (new MaintenanceRequestResource($request))->toArray(Request::create('/'));

        $this->assertSame('Demo Area', $data['equipment']['area']['name']);
    }

    public function test_equipment_resource_exposes_company_when_loaded(): void
    {
        $equipment = $this->equipment->load(['area.company']);

        $data = (new EquipmentResource($equipment))->toArray(Request::create('/'));

        $this->assertSame('Demo Company', $data['area']['company']['name']);
    }

    public function test_technician_only_sees_work_orders_they_are_linked_to(): void
    {
        $technician = User::factory()->create();
        Role::create(['name' => 'technician', 'guard_name' => 'web']);
        $technician->assignRole('technician');

        $mine = $this->workOrder(['technician_coordinator_id' => $technician->id]);
        $theirs = $this->workOrder(['technician_coordinator_id' => $this->user->id, 'issued_by' => $this->user->id]);

        WorkOrderWorker::create([
            'work_order_id' => $theirs->id,
            'user_id' => $this->user->id,
            'role' => 'Teknisi',
        ]);

        Sanctum::actingAs($technician, ['*']);

        $ids = collect($this->getJson('/api/work-orders')->assertOk()->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($mine->id));
        $this->assertFalse($ids->contains($theirs->id));
    }

    public function test_technician_filter_still_applies_status_filter_on_top_of_scope(): void
    {
        $technician = User::factory()->create();
        Role::create(['name' => 'technician', 'guard_name' => 'web']);
        $technician->assignRole('technician');

        $this->workOrder(['technician_coordinator_id' => $technician->id, 'status' => 'Pending']);
        $this->workOrder(['technician_coordinator_id' => $technician->id, 'status' => 'Completed']);

        Sanctum::actingAs($technician, ['*']);

        $statuses = collect($this->getJson('/api/work-orders?status=Completed')->assertOk()->json('data'))
            ->pluck('status')
            ->unique()
            ->values()
            ->all();

        $this->assertSame(['Completed'], $statuses);
    }
}
