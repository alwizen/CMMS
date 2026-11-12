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
use Tests\TestCase;

class ApiHousekeepingManageTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected HousekeepingArea $area;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
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
        ]);
        HousekeepingTask::create([
            'housekeeping_facility_id' => $facility->id,
            'name' => 'Cek oil catcher',
            'is_active' => true,
        ]);

        $this->getJson("/api/housekeeping-areas/{$this->area->id}/manpowers")
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Budi');

        $this->getJson("/api/housekeeping-areas/{$this->area->id}/facilities")
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Oil Catcher')
            ->assertJsonPath('data.0.tasks.0.name', 'Cek oil catcher');
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
}
