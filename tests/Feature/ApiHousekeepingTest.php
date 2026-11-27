<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingManpower;
use App\Models\HousekeepingPlan;
use App\Models\HousekeepingPlanTask;
use App\Models\User;
use App\Support\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiHousekeepingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected HousekeepingArea $area;

    protected HousekeepingPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['spv_gos', 'patra-jasa', 'super_admin'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        // Eksekusi checklist & dokumentasi foto milik petugas housekeeping.
        $this->user = User::factory()->create(['status' => true]);
        $this->user->assignRole('patra-jasa');
        Sanctum::actingAs($this->user, ['*']);

        $company = Company::create(['code' => 'HK', 'name' => 'HK Co']);
        $this->area = HousekeepingArea::create([
            'company_id' => $company->id,
            'code' => 'taman',
            'supervisor_id' => $this->user->id,
            'name' => 'Taman Kantor',
            'polygon' => [[-6.87, 109.18], [-6.871, 109.18], [-6.871, 109.181]],
            'area_size' => 6151.52,
        ]);

        $this->plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => now(),
            'status' => 'Draft',
        ]);
    }

    public function test_index_returns_plans_with_meta(): void
    {
        $response = $this->getJson('/api/housekeeping-plans')->assertOk();

        $response->assertJsonPath('success', true)
            ->assertJsonPath('data.0.plan_number', $this->plan->plan_number)
            ->assertJsonPath('data.0.area.name', 'Taman Kantor')
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    public function test_index_filters_by_status_and_search(): void
    {
        $otherArea = HousekeepingArea::create([
            'company_id' => $this->area->company_id,
            'code' => 'parkir',
            'supervisor_id' => $this->user->id,
            'name' => 'Parkir Basement',
        ]);

        HousekeepingPlan::create([
            'housekeeping_area_id' => $otherArea->id,
            'plan_date' => now()->subDay(),
            'status' => 'Completed',
        ]);

        // Search by area name.
        $this->getJson('/api/housekeeping-plans?search=Parkir')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.area.name', 'Parkir Basement');

        // Filter by status.
        $this->getJson('/api/housekeeping-plans?status=Completed')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.status', 'Completed');
    }

    public function test_index_paginates_results(): void
    {
        for ($i = 0; $i < 3; $i++) {
            HousekeepingPlan::create([
                'housekeeping_area_id' => $this->area->id,
                'plan_date' => now()->subDays($i + 1),
                'status' => 'Draft',
            ]);
        }

        $response = $this->getJson('/api/housekeeping-plans?per_page=2&page=1')->assertOk();

        $response->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 4)
            ->assertJsonPath('meta.last_page', 2);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_show_returns_plan_detail_with_tasks_and_manpowers(): void
    {
        $manpower = HousekeepingManpower::create([
            'housekeeping_area_id' => $this->area->id,
            'name' => 'Budi',
            'employee_number' => 'EMP-1',
        ]);
        $this->plan->manpowers()->attach($manpower->id);

        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $this->plan->id,
            'name' => 'Pemotongan Rumput',
            'status' => 'Pending',
        ]);

        $response = $this->getJson('/api/housekeeping-plans/'.$this->plan->id)->assertOk();

        $response->assertJsonPath('data.plan_number', $this->plan->plan_number)
            ->assertJsonPath('data.manpowers.0.name', 'Budi')
            ->assertJsonPath('data.tasks.0.name', 'Pemotongan Rumput');
    }

    public function test_upload_task_photos_and_task_status_transition(): void
    {
        Storage::fake('public');

        $task = HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $this->plan->id,
            'name' => 'Bersih Selokan',
            'status' => 'Pending',
        ]);

        $this->postJson("/api/housekeeping-plans/{$this->plan->id}/tasks/{$task->id}/photos", [
            'type' => 'before',
            'photos' => [UploadedFile::fake()->image('before.jpg')],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertNotEmpty($task->fresh()->photo_before);

        $this->putJson("/api/housekeeping-plans/{$this->plan->id}/tasks/{$task->id}", [
            'status' => 'In Progress',
        ])->assertOk();

        $this->assertSame('In Progress', $task->fresh()->status);
        $this->assertNotNull($task->fresh()->started_at);

        $this->postJson("/api/housekeeping-plans/{$this->plan->id}/tasks/{$task->id}/photos", [
            'type' => 'after',
            'photos' => [UploadedFile::fake()->image('after.jpg')],
        ])->assertOk();

        $this->putJson("/api/housekeeping-plans/{$this->plan->id}/tasks/{$task->id}", [
            'status' => 'Done',
        ])->assertOk();

        $this->assertSame('Done', $task->fresh()->status);
        $this->assertNotNull($task->fresh()->completed_at);
    }

    public function test_cannot_start_task_without_before_photo(): void
    {
        $task = HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $this->plan->id,
            'name' => 'Bersih Selokan',
            'status' => 'Pending',
        ]);

        $this->putJson("/api/housekeeping-plans/{$this->plan->id}/tasks/{$task->id}", [
            'status' => 'In Progress',
        ])->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame('Pending', $task->fresh()->status);

        $task->update(['photo_before' => ['housekeeping/plan-tasks/before/x.jpg']]);

        $this->putJson("/api/housekeeping-plans/{$this->plan->id}/tasks/{$task->id}", [
            'status' => 'In Progress',
        ])->assertOk();

        $this->assertSame('In Progress', $task->fresh()->status);
    }

    public function test_cannot_complete_task_without_after_photo(): void
    {
        $task = HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $this->plan->id,
            'name' => 'Bersih Selokan',
            'status' => 'In Progress',
            'photo_before' => ['housekeeping/plan-tasks/before/x.jpg'],
        ]);

        $this->putJson("/api/housekeeping-plans/{$this->plan->id}/tasks/{$task->id}", [
            'status' => 'Done',
        ])->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame('In Progress', $task->fresh()->status);

        $task->update(['photo_after' => ['housekeeping/plan-tasks/after/x.jpg']]);

        $this->putJson("/api/housekeeping-plans/{$this->plan->id}/tasks/{$task->id}", [
            'status' => 'Done',
        ])->assertOk();

        $this->assertSame('Done', $task->fresh()->status);
    }

    public function test_task_requires_photos_regardless_of_facility(): void
    {
        $task = HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $this->plan->id,
            'name' => 'Tanpa Fasilitas',
            'status' => 'Pending',
        ]);

        $this->putJson("/api/housekeeping-plans/{$this->plan->id}/tasks/{$task->id}", [
            'status' => 'In Progress',
        ])->assertStatus(422);

        $task->update(['photo_before' => ['housekeeping/plan-tasks/before/x.jpg']]);

        $this->putJson("/api/housekeeping-plans/{$this->plan->id}/tasks/{$task->id}", [
            'status' => 'In Progress',
        ])->assertOk();

        $task->update(['photo_after' => ['housekeeping/plan-tasks/after/x.jpg']]);

        $this->putJson("/api/housekeeping-plans/{$this->plan->id}/tasks/{$task->id}", [
            'status' => 'Done',
        ])->assertOk();

        $this->assertSame('Done', $task->fresh()->status);
    }

    public function test_task_resource_exposes_per_checklist_photos(): void
    {
        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $this->plan->id,
            'name' => 'Bersih Selokan',
            'status' => 'Done',
            'photo_before' => ['housekeeping/plan-tasks/before/x.jpg'],
            'photo_after' => ['housekeeping/plan-tasks/after/y.jpg'],
        ]);

        $response = $this->getJson('/api/housekeeping-plans/'.$this->plan->id)->assertOk();

        $response->assertJsonPath('data.tasks.0.photo_before.0', Media::url('housekeeping/plan-tasks/before/x.jpg'))
            ->assertJsonPath('data.tasks.0.photo_after.0', Media::url('housekeeping/plan-tasks/after/y.jpg'))
            ->assertJsonPath('data.progress_percent', 100)
            ->assertJsonPath('data.sla_days', 1)
            ->assertJsonMissingPath('data.facilities');
    }

    public function test_delete_task_photo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('housekeeping/plan-tasks/before/x.jpg', 'x');

        $task = HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $this->plan->id,
            'name' => 'Bersih Selokan',
            'status' => 'Pending',
            'photo_before' => ['housekeeping/plan-tasks/before/x.jpg'],
        ]);

        $this->deleteJson("/api/housekeeping-plans/{$this->plan->id}/tasks/{$task->id}/photos", [
            'type' => 'before',
            'path' => 'housekeeping/plan-tasks/before/x.jpg',
        ])->assertOk();

        $this->assertEmpty($task->fresh()->photo_before);
        Storage::disk('public')->assertMissing('housekeeping/plan-tasks/before/x.jpg');
    }

    public function test_requires_authentication(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/housekeeping-plans')->assertUnauthorized();
    }
}
