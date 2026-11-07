<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderActivity;
use App\Models\WorkOrderWorker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MobileExecutionTest extends TestCase
{
    use RefreshDatabase;

    protected Equipment $equipment;

    protected User $admin;

    protected User $technician;

    protected User $otherTechnician;

    protected WorkOrder $workOrder;

    protected WorkOrderActivity $activity;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();

        foreach (['super_admin', 'pengawas', 'spv_mos', 'ltsa_tad'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');

        $this->technician = User::factory()->create();
        $this->technician->assignRole('ltsa_tad');

        $this->otherTechnician = User::factory()->create();
        $this->otherTechnician->assignRole('ltsa_tad');

        $company = Company::create(['code' => 'CO', 'name' => 'Demo Company']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'AR', 'name' => 'Demo Area']);
        $type = EquipmentType::create(['code' => 'pompa', 'name' => 'Pompa']);

        $this->equipment = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $type->id,
            'tag_number' => 'P-101',
            'description' => 'Pompa utama',
        ]);

        $activity = Activity::create([
            'equipment_type_id' => $type->id,
            'name' => 'Inspeksi Visual',
            'type' => 'visual_check',
            'maintenance_classification' => 'preventive',
            'interval' => 'monthly',
            'answer_type' => 'qualitative',
            'status' => true,
        ]);

        $this->workOrder = WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->admin->id,
            'technician_coordinator_id' => $this->technician->id,
            'classification' => 'Preventive',
            'start_at' => now(),
            'status' => WorkOrder::STATUSES['Pending'],
        ]);

        WorkOrderWorker::create([
            'work_order_id' => $this->workOrder->id,
            'user_id' => $this->technician->id,
            'role' => 'Teknisi',
        ]);

        $this->activity = WorkOrderActivity::create([
            'work_order_id' => $this->workOrder->id,
            'activity_id' => $activity->id,
            'reference' => 'Cek visual',
            'executed' => false,
        ]);
    }

    protected function makeWorkOrderFor(User $user): WorkOrder
    {
        return WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->admin->id,
            'technician_coordinator_id' => $user->id,
            'classification' => 'Corrective',
            'start_at' => now(),
            'status' => WorkOrder::STATUSES['Pending'],
        ]);
    }

    public function test_dashboard_recent_work_orders_only_contains_logged_in_users_work_orders(): void
    {
        $other = $this->makeWorkOrderFor($this->otherTechnician);

        Sanctum::actingAs($this->technician, ['*']);

        $ids = collect($this->getJson('/api/dashboard')->assertOk()->json('data.recent_work_orders'))
            ->pluck('id');

        $this->assertTrue($ids->contains($this->workOrder->id));
        $this->assertFalse($ids->contains($other->id));
    }

    public function test_dashboard_recent_work_orders_is_scoped_even_for_super_admin(): void
    {
        // WO yang sama sekali tidak melibatkan admin
        $unrelated = WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->technician->id,
            'technician_coordinator_id' => $this->otherTechnician->id,
            'classification' => 'Corrective',
            'start_at' => now(),
            'status' => WorkOrder::STATUSES['Pending'],
        ]);

        Sanctum::actingAs($this->admin, ['*']);

        $ids = collect($this->getJson('/api/dashboard')->assertOk()->json('data.recent_work_orders'))
            ->pluck('id');

        $this->assertFalse($ids->contains($unrelated->id));
    }

    public function test_dashboard_stats_do_not_leak_across_users(): void
    {
        $this->makeWorkOrderFor($this->otherTechnician);

        Sanctum::actingAs($this->technician, ['*']);

        $stats = $this->getJson('/api/dashboard')->assertOk()->json('data.stats');

        $this->assertSame(1, $stats['total_work_orders']);
        $this->assertSame(1, $stats['pending']);
    }

    public function test_dashboard_reports_due_today_and_overdue_for_logged_in_user(): void
    {
        $this->workOrder->update([
            'start_at' => now()->setTime(9, 0),
            'status' => WorkOrder::STATUSES['Pending'],
        ]);

        $overdue = WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->admin->id,
            'technician_coordinator_id' => $this->technician->id,
            'classification' => 'Corrective',
            'start_at' => now()->subDays(2)->setTime(8, 0),
            'status' => WorkOrder::STATUSES['In Progress'],
        ]);

        // WO orang lain tidak boleh ikut terhitung
        WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->admin->id,
            'technician_coordinator_id' => $this->otherTechnician->id,
            'classification' => 'Corrective',
            'start_at' => now()->subDays(3)->setTime(8, 0),
            'status' => WorkOrder::STATUSES['Pending'],
        ]);

        Sanctum::actingAs($this->technician, ['*']);

        $data = $this->getJson('/api/dashboard')->assertOk()->json('data');

        $this->assertSame(1, $data['stats']['due_today']);
        $this->assertSame(1, $data['stats']['overdue']);
        $this->assertCount(1, $data['today_work_orders']);
        $this->assertSame($this->workOrder->id, $data['today_work_orders'][0]['id']);
        $this->assertSame('09:00', $data['today_work_orders'][0]['start_time']);
        $recentIds = collect($data['recent_work_orders'])->pluck('id');
        $this->assertTrue($recentIds->contains($overdue->id));
    }

    public function test_completed_work_orders_are_not_counted_as_overdue(): void
    {
        $this->workOrder->update([
            'start_at' => now()->subDays(5),
            'status' => WorkOrder::STATUSES['Completed'],
            'finish_at' => now()->subDays(4),
        ]);

        Sanctum::actingAs($this->technician, ['*']);

        $stats = $this->getJson('/api/dashboard')->assertOk()->json('data.stats');

        $this->assertSame(0, $stats['overdue']);
        $this->assertSame(0, $stats['due_today']);
    }

    public function test_assigned_technician_can_update_activity_result(): void
    {
        Sanctum::actingAs($this->technician, ['*']);

        $response = $this->putJson(
            "/api/work-orders/{$this->workOrder->id}/activities/{$this->activity->id}",
            [
                'pre_inspection' => 'Ada kebocoran kecil',
                'final_result' => 'Sudah diganti',
                'follow_up' => 'Pantau 3 hari',
                'executed' => true,
                'note' => 'Selesai',
            ],
        )->assertOk();

        $this->assertTrue($response->json('data.executed'));

        $this->assertDatabaseHas('work_order_activities', [
            'id' => $this->activity->id,
            'pre_inspection' => 'Ada kebocoran kecil',
            'final_result' => 'Sudah diganti',
            'executed' => true,
        ]);
    }

    public function test_unrelated_user_cannot_update_activity(): void
    {
        Sanctum::actingAs($this->otherTechnician, ['*']);

        $this->putJson(
            "/api/work-orders/{$this->workOrder->id}/activities/{$this->activity->id}",
            ['executed' => true],
        )->assertForbidden();

        $this->assertDatabaseHas('work_order_activities', [
            'id' => $this->activity->id,
            'executed' => false,
        ]);
    }

    public function test_unrelated_user_cannot_change_work_order_status(): void
    {
        Sanctum::actingAs($this->otherTechnician, ['*']);

        $this->putJson("/api/work-orders/{$this->workOrder->id}", [
            'status' => 'Completed',
        ])->assertForbidden();

        $this->assertSame(WorkOrder::STATUSES['Pending'], $this->workOrder->fresh()->status);
    }

    public function test_assigned_technician_can_start_and_complete_work_order(): void
    {
        Sanctum::actingAs($this->technician, ['*']);

        $this->putJson("/api/work-orders/{$this->workOrder->id}", [
            'status' => 'In Progress',
        ])->assertOk();

        $this->assertSame(WorkOrder::STATUSES['In Progress'], $this->workOrder->fresh()->status);

        $this->putJson("/api/work-orders/{$this->workOrder->id}", [
            'status' => 'Completed',
            'finish_at' => now()->toDateTimeString(),
        ])->assertOk();

        $this->assertSame(WorkOrder::STATUSES['Completed'], $this->workOrder->fresh()->status);
    }

    public function test_activity_result_length_is_validated(): void
    {
        Sanctum::actingAs($this->technician, ['*']);

        $this->putJson(
            "/api/work-orders/{$this->workOrder->id}/activities/{$this->activity->id}",
            ['final_result' => str_repeat('a', 120)],
        )->assertStatus(422);
    }
}
