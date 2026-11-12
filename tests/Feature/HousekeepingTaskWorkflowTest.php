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
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class HousekeepingTaskWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_requires_before_photo_to_start_and_after_to_complete(): void
    {
        Storage::fake('public');
        Gate::before(fn () => true);
        $user = User::factory()->create();
        $this->actingAs($user);

        $company = Company::create(['code' => 'HK-1', 'name' => 'HK Co']);
        $area = HousekeepingArea::create(['company_id' => $company->id, 'code' => 'a', 'name' => 'A']);
        $plan = HousekeepingPlan::create(['housekeeping_area_id' => $area->id, 'plan_date' => now(), 'status' => 'Draft']);

        $task = HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Pemotongan Rumput',
            'status' => 'Pending',
        ]);

        $component = fn () => Livewire::test(TasksRelationManager::class, [
            'ownerRecord' => $plan,
            'pageClass' => EditHousekeepingPlan::class,
        ]);

        $component()
            ->callTableAction('start', $task, data: [])
            ->assertHasTableActionErrors(['photo_before' => 'required']);

        $component()
            ->callTableAction('start', $task, data: [
                'photo_before' => ['housekeeping/plan-tasks/before/test.jpg'],
            ])
            ->assertHasNoTableActionErrors();

        $task->refresh();
        $this->assertSame('In Progress', $task->status);
        $this->assertNotNull($task->started_at);

        $component()
            ->callTableAction('complete', $task, data: [])
            ->assertHasTableActionErrors(['photo_after' => 'required']);

        $component()
            ->callTableAction('complete', $task, data: [
                'photo_after' => ['housekeeping/plan-tasks/after/test.jpg'],
            ])
            ->assertHasNoTableActionErrors();

        $task->refresh();
        $this->assertSame('Done', $task->status);
        $this->assertNotNull($task->completed_at);
    }
}
