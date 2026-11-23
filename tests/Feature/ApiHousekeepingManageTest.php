<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingFacility;
use App\Models\HousekeepingManpower;
use App\Models\HousekeepingPlan;
use App\Models\HousekeepingPlanTask;
use App\Models\HousekeepingTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiHousekeepingManageTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected HousekeepingArea $area;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['spv_gos', 'patra-jasa', 'super_admin'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        // Pengelolaan plan (buat/generate/ubah) milik SPV GOS.
        $this->user = User::factory()->create(['status' => true]);
        $this->user->assignRole('spv_gos');
        Sanctum::actingAs($this->user, ['*']);

        $company = Company::create(['code' => 'HK', 'name' => 'HK Co']);
        $this->area = HousekeepingArea::create([
            'company_id' => $company->id,
            'code' => 'taman',
            'name' => 'Taman Kantor',
            'is_active' => true,
        ]);
    }

    public function test_lists_areas(): void
    {
        $this->getJson('/api/housekeeping-areas')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.name', 'Taman Kantor');
    }

    public function test_lists_area_manpowers_and_facilities(): void
    {
        HousekeepingManpower::create([
            'housekeeping_area_id' => $this->area->id,
            'name' => 'Budi',
            'is_active' => true,
        ]);

        $facility = HousekeepingFacility::create([
            'housekeeping_area_id' => $this->area->id,
            'name' => 'Oil Catcher',
            'is_active' => true,
            'polygon' => [[-6.8705, 109.1865], [-6.8700, 109.1870], [-6.8705, 109.1875]],
            'area_size' => 1500.5,
            'latitude' => -6.8703,
            'longitude' => 109.187,
        ]);
        HousekeepingTask::create([
            'housekeeping_facility_id' => $facility->id,
            'name' => 'Cek oil catcher',
            'is_active' => true,
        ]);

        $this->area->update([
            'polygon' => [[-6.8800, 109.1800], [-6.8700, 109.1900], [-6.8800, 109.1900]],
            'latitude' => -6.875,
            'longitude' => 109.185,
        ]);

        $this->getJson("/api/housekeeping-areas/{$this->area->id}/manpowers")
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Budi');

        $this->getJson("/api/housekeeping-areas/{$this->area->id}/facilities")
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Oil Catcher')
            ->assertJsonPath('data.0.tasks.0.name', 'Cek oil catcher')
            ->assertJsonPath('data.0.polygon.0.0', -6.8705)
            ->assertJsonPath('data.0.latitude', -6.8703);

        $this->getJson('/api/housekeeping-areas')
            ->assertOk()
            ->assertJsonPath('data.0.polygon.0.0', -6.8800)
            ->assertJsonPath('data.0.latitude', -6.875);
    }

    public function test_creates_plan(): void
    {
        $response = $this->postJson('/api/housekeeping-plans', [
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => '2026-11-20',
            'note' => 'Plan pagi',
        ])->assertStatus(201);

        $response->assertJsonPath('success', true)
            ->assertJsonPath('data.area.name', 'Taman Kantor');

        $this->assertDatabaseHas('housekeeping_plans', [
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => '2026-11-20 00:00:00',
        ]);
    }

    public function test_create_plan_validates_area(): void
    {
        $this->postJson('/api/housekeeping-plans', [
            'housekeeping_area_id' => 99999,
            'plan_date' => '2026-11-20',
        ])->assertStatus(422);
    }

    public function test_adds_manpowers_to_plan(): void
    {
        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => now(),
            'status' => 'Draft',
        ]);

        $manpower = HousekeepingManpower::create([
            'housekeeping_area_id' => $this->area->id,
            'name' => 'Budi',
            'is_active' => true,
        ]);

        $this->postJson("/api/housekeeping-plans/{$plan->id}/manpowers", [
            'manpower_ids' => [$manpower->id],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('housekeeping_plan_manpowers', [
            'housekeeping_plan_id' => $plan->id,
            'housekeeping_manpower_id' => $manpower->id,
        ]);
    }

    public function test_rejects_manpower_from_other_area(): void
    {
        $company = Company::create(['code' => 'HK2', 'name' => 'HK Co 2']);
        $otherArea = HousekeepingArea::create(['company_id' => $company->id, 'code' => 'other', 'name' => 'Other']);

        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => now(),
            'status' => 'Draft',
        ]);

        $foreignManpower = HousekeepingManpower::create([
            'housekeeping_area_id' => $otherArea->id,
            'name' => 'Orang Lain',
            'is_active' => true,
        ]);

        $this->postJson("/api/housekeeping-plans/{$plan->id}/manpowers", [
            'manpower_ids' => [$foreignManpower->id],
        ])->assertStatus(422);
    }

    public function test_adds_task_from_master_checklist(): void
    {
        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => now(),
            'status' => 'Draft',
        ]);

        $facility = HousekeepingFacility::create([
            'housekeeping_area_id' => $this->area->id,
            'name' => 'Selokan',
            'is_active' => true,
        ]);
        $master = HousekeepingTask::create([
            'housekeeping_facility_id' => $facility->id,
            'name' => 'Bersih selokan',
            'is_active' => true,
        ]);

        $this->postJson("/api/housekeeping-plans/{$plan->id}/tasks", [
            'housekeeping_task_id' => $master->id,
        ])->assertStatus(201)
            ->assertJsonPath('data.name', 'Bersih selokan')
            ->assertJsonPath('data.facility.name', 'Selokan');

        $this->assertDatabaseHas('housekeeping_plan_tasks', [
            'housekeeping_plan_id' => $plan->id,
            'housekeeping_task_id' => $master->id,
            'name' => 'Bersih selokan',
        ]);
    }

    public function test_adds_custom_task_by_name(): void
    {
        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => now(),
            'status' => 'Draft',
        ]);

        $this->postJson("/api/housekeeping-plans/{$plan->id}/tasks", [
            'name' => 'Angkut sampah',
        ])->assertStatus(201)->assertJsonPath('data.name', 'Angkut sampah');
    }

    public function test_deletes_task_from_plan(): void
    {
        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => now(),
            'status' => 'Draft',
        ]);
        $task = HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Hapus saya',
            'status' => 'Pending',
        ]);

        $this->deleteJson("/api/housekeeping-plans/{$plan->id}/tasks/{$task->id}")
            ->assertOk();

        $this->assertDatabaseMissing('housekeeping_plan_tasks', ['id' => $task->id]);
    }

    public function test_generates_plan_with_checklist_from_master(): void
    {
        $facility = HousekeepingFacility::create([
            'housekeeping_area_id' => $this->area->id,
            'name' => 'Gazebo',
            'is_active' => true,
        ]);
        HousekeepingTask::create([
            'housekeeping_facility_id' => $facility->id,
            'name' => 'Sapu daun',
            'is_active' => true,
        ]);
        HousekeepingTask::create([
            'housekeeping_facility_id' => $facility->id,
            'name' => 'Bersih bangku',
            'is_active' => true,
        ]);
        HousekeepingTask::create([
            'housekeeping_facility_id' => $facility->id,
            'name' => 'Tidak aktif',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/housekeeping-plans/generate', [
            'area_ids' => [$this->area->id],
            'plan_date' => '2026-11-20',
        ])->assertStatus(201);

        $response->assertJsonPath('success', true)
            ->assertJsonPath('created', 1)
            ->assertJsonPath('data.0.task_count', 2)
            ->assertJsonPath('data.0.area.name', 'Taman Kantor');

        $this->assertSame(2, HousekeepingPlanTask::count());
    }

    public function test_generate_skips_duplicate_plan_on_same_date(): void
    {
        $facility = HousekeepingFacility::create([
            'housekeeping_area_id' => $this->area->id,
            'name' => 'Gazebo',
            'is_active' => true,
        ]);
        HousekeepingTask::create([
            'housekeeping_facility_id' => $facility->id,
            'name' => 'Sapu daun',
            'is_active' => true,
        ]);

        $this->postJson('/api/housekeeping-plans/generate', [
            'area_ids' => [$this->area->id],
            'plan_date' => '2026-11-20',
        ])->assertStatus(201);

        $this->postJson('/api/housekeeping-plans/generate', [
            'area_ids' => [$this->area->id],
            'plan_date' => '2026-11-20',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('created', 0);

        $this->assertSame(1, HousekeepingPlan::count());
    }

    public function test_generate_requires_area_with_active_checklist(): void
    {
        $this->postJson('/api/housekeeping-plans/generate', [
            'area_ids' => [$this->area->id],
            'plan_date' => '2026-11-20',
        ])->assertStatus(422);
    }

    public function test_removes_manpower_from_plan(): void
    {
        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => now(),
            'status' => 'Draft',
        ]);

        $manpower = HousekeepingManpower::create([
            'housekeeping_area_id' => $this->area->id,
            'name' => 'Budi',
            'is_active' => true,
        ]);
        $plan->manpowers()->attach($manpower->id);

        $this->deleteJson("/api/housekeeping-plans/{$plan->id}/manpowers/{$manpower->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('housekeeping_plan_manpowers', [
            'housekeeping_plan_id' => $plan->id,
            'housekeeping_manpower_id' => $manpower->id,
        ]);
    }

    public function test_petugas_cannot_create_or_generate_plan(): void
    {
        $petugas = User::factory()->create(['status' => true]);
        $petugas->assignRole('patra-jasa');
        Sanctum::actingAs($petugas, ['*']);

        $this->postJson('/api/housekeeping-plans', [
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => '2026-11-20',
        ])->assertForbidden();

        $this->postJson('/api/housekeeping-plans/generate', [
            'area_ids' => [$this->area->id],
            'plan_date' => '2026-11-20',
        ])->assertForbidden();
    }

    public function test_spv_cannot_execute_checklist(): void
    {
        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => now(),
            'status' => 'Draft',
        ]);

        $task = HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Sapu',
            'status' => 'Pending',
        ]);

        $this->putJson("/api/housekeeping-plans/{$plan->id}/tasks/{$task->id}", [
            'status' => 'In Progress',
        ])->assertForbidden();

        $this->deleteJson("/api/housekeeping-plans/{$plan->id}/tasks/999/photos", [
            'type' => 'before',
            'path' => 'x.jpg',
        ])->assertForbidden();
    }

    public function test_updates_and_deletes_plan(): void
    {
        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => now(),
            'status' => 'Draft',
        ]);

        $this->putJson("/api/housekeeping-plans/{$plan->id}", [
            'plan_date' => '2026-11-21',
            'note' => 'Catatan baru',
        ])
            ->assertOk()
            ->assertJsonPath('data.plan_date', '2026-11-21');

        $this->deleteJson("/api/housekeeping-plans/{$plan->id}")->assertOk();

        $this->assertDatabaseMissing('housekeeping_plans', ['id' => $plan->id]);
    }
}
