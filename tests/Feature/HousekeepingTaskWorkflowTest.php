<?php

namespace Tests\Feature;

use App\Filament\Resources\HousekeepingPlans\Pages\EditHousekeepingPlan;
use App\Filament\Resources\HousekeepingPlans\RelationManagers\TasksRelationManager;
use App\Models\Company;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingPlan;
use App\Models\HousekeepingPlanTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HousekeepingTaskWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Eksekusi checklist hanya untuk petugas housekeeping (patra-jasa).
     */
    public function test_task_can_be_started_and_completed_after_task_photos_uploaded(): void
    {
        Gate::before(fn () => true);
        Role::findOrCreate('patra-jasa', 'web');

        $petugas = User::factory()->create(['status' => true]);
        $petugas->assignRole('patra-jasa');
        $this->actingAs($petugas);

        $company = Company::create(['code' => 'HK-1', 'name' => 'HK Co']);
        $area = HousekeepingArea::create(['company_id' => $company->id, 'code' => 'a', 'name' => 'A', 'sla_days' => 2]);
        $plan = HousekeepingPlan::create(['housekeeping_area_id' => $area->id, 'plan_date' => now(), 'status' => 'Draft']);

        $task = HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Pemotongan Rumput',
            'status' => 'Pending',
            'photo_before' => ['housekeeping/plan-tasks/before/a.jpg'],
            'photo_after' => ['housekeeping/plan-tasks/after/a.jpg'],
        ]);

        $component = fn () => Livewire::test(TasksRelationManager::class, [
            'ownerRecord' => $plan,
            'pageClass' => EditHousekeepingPlan::class,
        ]);

        $component()
            ->callTableAction('start', $task)
            ->assertHasNoTableActionErrors();

        $task->refresh();
        $this->assertSame('In Progress', $task->status);
        $this->assertNotNull($task->started_at);

        $component()
            ->callTableAction('complete', $task, data: ['note' => 'Selesai rapi'])
            ->assertHasNoTableActionErrors();

        $task->refresh();
        $this->assertSame('Done', $task->status);
        $this->assertNotNull($task->completed_at);
        $this->assertSame('Selesai rapi', $task->note);
        $this->assertSame('Completed', $plan->fresh()->status);
        $this->assertSame(100, $plan->fresh()->progress_percent);
    }

    public function test_task_without_photos_is_not_started(): void
    {
        Gate::before(fn () => true);
        Role::findOrCreate('patra-jasa', 'web');

        $petugas = User::factory()->create(['status' => true]);
        $petugas->assignRole('patra-jasa');
        $this->actingAs($petugas);

        $company = Company::create(['code' => 'HK-3', 'name' => 'HK Co']);
        $area = HousekeepingArea::create(['company_id' => $company->id, 'code' => 'c', 'name' => 'C']);
        $plan = HousekeepingPlan::create(['housekeeping_area_id' => $area->id, 'plan_date' => now(), 'status' => 'Draft']);

        $task = HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Tanpa Foto',
            'status' => 'Pending',
        ]);

        Livewire::test(TasksRelationManager::class, [
            'ownerRecord' => $plan,
            'pageClass' => EditHousekeepingPlan::class,
        ])->callTableAction('start', $task);

        $this->assertSame('Pending', $task->fresh()->status);

        $task->update(['photo_before' => ['housekeeping/plan-tasks/before/a.jpg']]);

        Livewire::test(TasksRelationManager::class, [
            'ownerRecord' => $plan,
            'pageClass' => EditHousekeepingPlan::class,
        ])->callTableAction('start', $task);

        $this->assertSame('In Progress', $task->fresh()->status);

        Livewire::test(TasksRelationManager::class, [
            'ownerRecord' => $plan,
            'pageClass' => EditHousekeepingPlan::class,
        ])->callTableAction('complete', $task, data: ['note' => 'Coba selesai']);

        $this->assertSame('In Progress', $task->fresh()->status);

        $task->update(['photo_after' => ['housekeeping/plan-tasks/after/a.jpg']]);

        Livewire::test(TasksRelationManager::class, [
            'ownerRecord' => $plan,
            'pageClass' => EditHousekeepingPlan::class,
        ])->callTableAction('complete', $task, data: ['note' => 'Selesai']);

        $this->assertSame('Done', $task->fresh()->status);
    }

    public function test_task_photos_are_stored_per_checklist(): void
    {
        $company = Company::create(['code' => 'HK-2', 'name' => 'HK Co']);
        $area = HousekeepingArea::create(['company_id' => $company->id, 'code' => 'b', 'name' => 'B', 'sla_days' => 7]);
        $plan = HousekeepingPlan::create(['housekeeping_area_id' => $area->id, 'plan_date' => now(), 'status' => 'Draft']);

        $task = HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Sapu',
            'status' => 'Pending',
            'photo_before' => ['housekeeping/plan-tasks/before/a.jpg'],
            'photo_after' => ['housekeeping/plan-tasks/after/a.jpg'],
        ]);

        $this->assertCount(1, $task->photo_before);
        $this->assertCount(1, $task->photo_after);
        $this->assertTrue($task->hasPhotoBefore());
        $this->assertTrue($task->hasPhotoAfter());
        $this->assertSame(
            $plan->plan_date->copy()->addDays(7)->toDateString(),
            $plan->due_date->toDateString(),
        );
    }
}
