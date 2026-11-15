<?php

namespace Tests\Feature;

use App\Http\Resources\EquipmentResource;
use App\Http\Resources\MaintenanceRequestResource;
use App\Http\Resources\WorkOrderResource;
use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\MaintenanceHistory;
use App\Models\MaintenanceRequest;
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

    public function test_equipment_maintenance_histories_endpoint_returns_history(): void
    {
        MaintenanceHistory::create([
            'equipment_id' => $this->equipment->id,
            'maintenance_type' => 'Preventive',
            'classification' => 'Preventive',
            'description' => 'Cek rutin',
            'maintenance_date' => now(),
            'performed_by' => $this->user->id,
            'status' => 'Completed',
        ]);

        Sanctum::actingAs($this->user, ['*']);

        $response = $this->getJson("/api/equipment/{$this->equipment->id}/maintenance-histories")->assertOk();

        $this->assertSame(1, $response->json('meta.total'));
        $this->assertSame('Cek rutin', $response->json('data.0.description'));
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
