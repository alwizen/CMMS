<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderWorker;
use App\Support\Notify;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotifyRecipientsTest extends TestCase
{
    use RefreshDatabase;

    protected Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::create(['code' => 'CO', 'name' => 'Demo']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'AR', 'name' => 'Area']);
        $type = EquipmentType::create(['code' => 'pump', 'name' => 'Pompa']);

        $this->equipment = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $type->id,
            'tag_number' => 'P-101',
        ]);
    }

    protected function makeWorkOrder(array $attributes = []): WorkOrder
    {
        $issuer = User::factory()->create();

        return WorkOrder::create(array_merge([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $issuer->id,
            'classification' => 'Preventive',
            'start_at' => now(),
            'status' => 'In Progress',
        ], $attributes));
    }

    public function test_supervisors_are_only_users_with_spv_mos_role(): void
    {
        Role::findOrCreate('spv_mos', 'web');
        Role::findOrCreate('pengawas', 'web');

        $spv = User::factory()->create();
        $spv->assignRole('spv_mos');

        $pengawas = User::factory()->create();
        $pengawas->assignRole('pengawas');

        $ids = Notify::supervisors()->pluck('id')->all();

        $this->assertSame([$spv->id], $ids);
    }

    public function test_work_order_recipients_are_coordinator_and_workers_without_duplicates(): void
    {
        $coordinator = User::factory()->create();
        $worker = User::factory()->create();

        $workOrder = $this->makeWorkOrder(['technician_coordinator_id' => $coordinator->id]);

        WorkOrderWorker::create(['work_order_id' => $workOrder->id, 'user_id' => $worker->id]);
        WorkOrderWorker::create(['work_order_id' => $workOrder->id, 'user_id' => $coordinator->id]);

        $ids = Notify::forWorkOrder($workOrder->fresh())->pluck('id')->sort()->values()->all();

        $this->assertSame(collect([$coordinator->id, $worker->id])->sort()->values()->all(), $ids);
    }

    public function test_work_order_recipients_exclude_inactive_users_and_never_fall_back_to_random_users(): void
    {
        $inactive = User::factory()->create(['status' => false]);
        $stranger = User::factory()->create();

        $workOrder = $this->makeWorkOrder(['technician_coordinator_id' => $inactive->id]);

        $ids = Notify::forWorkOrder($workOrder)->pluck('id')->all();

        $this->assertSame([], $ids);
        $this->assertNotContains($stranger->id, $ids);
    }

    public function test_supervisors_fall_back_to_super_admin_when_spv_role_is_missing(): void
    {
        Role::findOrCreate('super_admin', 'web');

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        User::factory()->create();

        $ids = Notify::supervisors()->pluck('id')->all();

        $this->assertSame([$admin->id], $ids);
    }

    public function test_supervisors_prefer_spv_mos_over_super_admin(): void
    {
        Role::findOrCreate('super_admin', 'web');
        Role::findOrCreate('spv_mos', 'web');

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $spv = User::factory()->create();
        $spv->assignRole('spv_mos');

        $ids = Notify::supervisors()->pluck('id')->all();

        $this->assertSame([$spv->id], $ids);
        $this->assertNotContains($admin->id, $ids);
    }
}
