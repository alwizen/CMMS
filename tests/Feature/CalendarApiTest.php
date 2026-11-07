<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\MaintenancePlan;
use App\Models\MaintenanceSchedule;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderWorker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CalendarApiTest extends TestCase
{
    use RefreshDatabase;

    protected Equipment $equipment;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();

        foreach (['super_admin', 'pengawas', 'spv_mos', 'ltsa_tad'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');

        $company = Company::create(['code' => 'CO', 'name' => 'Demo Company']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'AR', 'name' => 'Demo Area']);
        $type = EquipmentType::create(['code' => 'pompa', 'name' => 'Pompa']);

        $this->equipment = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $type->id,
            'tag_number' => 'P-101',
            'description' => 'Pompa utama',
        ]);
    }

    protected function makeUserWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    protected function makeSchedule(User $coordinator, string $date): MaintenanceSchedule
    {
        $plan = MaintenancePlan::create([
            'equipment_id' => $this->equipment->id,
            'maintenance_classification' => 'Preventive',
            'interval' => 'monthly',
            'start_date' => now()->startOfMonth()->toDateString(),
            'created_by' => $this->admin->id,
            'technician_coordinator_id' => $coordinator->id,
            'status' => 'Active',
        ]);

        return MaintenanceSchedule::create([
            'maintenance_plan_id' => $plan->id,
            'equipment_id' => $this->equipment->id,
            'scheduled_date' => $date,
            'status' => MaintenanceSchedule::STATUSES['Scheduled'],
        ]);
    }

    public function test_super_admin_sees_all_calendar_events(): void
    {
        $risna = $this->makeUserWithRole('ltsa_tad');
        $kholis = $this->makeUserWithRole('ltsa_tad');

        $this->makeSchedule($risna, now()->toDateString());
        $this->makeSchedule($kholis, now()->toDateString());

        Sanctum::actingAs($this->admin, ['*']);

        $events = $this->getJson('/api/maintenance-calendar')->assertOk()->json('data.events');

        $this->assertCount(2, $events);
    }

    public function test_ltsa_only_sees_schedules_where_they_are_coordinator(): void
    {
        $risna = $this->makeUserWithRole('ltsa_tad');
        $kholis = $this->makeUserWithRole('ltsa_tad');

        $mine = $this->makeSchedule($risna, now()->toDateString());
        $this->makeSchedule($kholis, now()->toDateString());

        Sanctum::actingAs($risna, ['*']);

        $events = $this->getJson('/api/maintenance-calendar')->assertOk()->json('data.events');

        $this->assertCount(1, $events);
        $this->assertSame($mine->schedule_number, $events[0]['title']);
    }

    public function test_ltsa_sees_work_order_when_assigned_as_worker(): void
    {
        $risna = $this->makeUserWithRole('ltsa_tad');
        $kholis = $this->makeUserWithRole('ltsa_tad');

        $workOrder = WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->admin->id,
            'technician_coordinator_id' => $kholis->id,
            'classification' => 'Corrective',
            'start_at' => now()->setTime(9, 0),
            'status' => WorkOrder::STATUSES['In Progress'],
        ]);

        WorkOrderWorker::create([
            'work_order_id' => $workOrder->id,
            'user_id' => $risna->id,
            'role' => 'Teknisi',
        ]);

        Sanctum::actingAs($risna, ['*']);

        $events = $this->getJson('/api/maintenance-calendar')->assertOk()->json('data.events');
        $woEvents = collect($events)->where('type', 'work_order');

        $this->assertCount(1, $woEvents);
        $this->assertSame($workOrder->work_order_number, $woEvents->first()['title']);
    }

    public function test_ltsa_does_not_see_other_users_work_orders(): void
    {
        $risna = $this->makeUserWithRole('ltsa_tad');
        $kholis = $this->makeUserWithRole('ltsa_tad');

        WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->admin->id,
            'technician_coordinator_id' => $kholis->id,
            'classification' => 'Corrective',
            'start_at' => now()->setTime(9, 0),
            'status' => WorkOrder::STATUSES['In Progress'],
        ]);

        Sanctum::actingAs($risna, ['*']);

        $events = $this->getJson('/api/maintenance-calendar')->assertOk()->json('data.events');

        $this->assertCount(0, $events);
    }

    public function test_pengawas_and_spv_see_everything(): void
    {
        $risna = $this->makeUserWithRole('ltsa_tad');
        $this->makeSchedule($risna, now()->toDateString());

        foreach (['pengawas', 'spv_mos'] as $role) {
            $user = $this->makeUserWithRole($role);
            Sanctum::actingAs($user, ['*']);

            $events = $this->getJson('/api/maintenance-calendar')->assertOk()->json('data.events');
            $this->assertCount(1, $events, "role {$role} harus melihat semua jadwal");
        }
    }

    public function test_calendar_respects_date_range(): void
    {
        $risna = $this->makeUserWithRole('ltsa_tad');
        $this->makeSchedule($risna, now()->toDateString());

        Sanctum::actingAs($this->admin, ['*']);

        $future = now()->addMonths(3);

        $events = $this->getJson('/api/maintenance-calendar?date_from='.$future->copy()->startOfMonth()->toDateString()
            .'&date_to='.$future->copy()->endOfMonth()->toDateString())
            ->assertOk()
            ->json('data.events');

        $this->assertCount(0, $events);
    }

    public function test_calendar_validates_date_range(): void
    {
        Sanctum::actingAs($this->admin, ['*']);

        $this->getJson('/api/maintenance-calendar?date_from=2026-12-31&date_to=2026-01-01')
            ->assertStatus(422);
    }

    public function test_calendar_requires_authentication(): void
    {
        $this->getJson('/api/maintenance-calendar')->assertStatus(401);
    }

    public function test_work_order_list_is_scoped_for_ltsa(): void
    {
        $risna = $this->makeUserWithRole('ltsa_tad');
        $kholis = $this->makeUserWithRole('ltsa_tad');

        $mine = WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->admin->id,
            'technician_coordinator_id' => $risna->id,
            'classification' => 'Corrective',
            'start_at' => now(),
            'status' => WorkOrder::STATUSES['Pending'],
        ]);

        WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->admin->id,
            'technician_coordinator_id' => $kholis->id,
            'classification' => 'Corrective',
            'start_at' => now(),
            'status' => WorkOrder::STATUSES['Pending'],
        ]);

        Sanctum::actingAs($risna, ['*']);

        $ids = collect($this->getJson('/api/work-orders')->assertOk()->json('data'))->pluck('id');

        $this->assertCount(1, $ids);
        $this->assertSame($mine->id, $ids->first());
    }
}
