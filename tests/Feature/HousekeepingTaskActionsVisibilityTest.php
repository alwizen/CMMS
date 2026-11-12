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
use Tests\TestCase;

class HousekeepingTaskActionsVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_actions_present_for_each_status(): void
    {
        Gate::before(fn () => true);
        $this->actingAs(User::factory()->create());

        $company = Company::create(['code' => 'HK-1', 'name' => 'HK Co']);
        $area = HousekeepingArea::create(['company_id' => $company->id, 'code' => 'a', 'name' => 'A']);
        $plan = HousekeepingPlan::create(['housekeeping_area_id' => $area->id, 'plan_date' => now(), 'status' => 'Draft']);

        $pending = HousekeepingPlanTask::create(['housekeeping_plan_id' => $plan->id, 'name' => 'P', 'status' => 'Pending']);
        $working = HousekeepingPlanTask::create(['housekeeping_plan_id' => $plan->id, 'name' => 'W', 'status' => 'In Progress']);
        $done = HousekeepingPlanTask::create(['housekeeping_plan_id' => $plan->id, 'name' => 'D', 'status' => 'Done']);

        $comp = fn () => Livewire::test(TasksRelationManager::class, [
            'ownerRecord' => $plan,
            'pageClass' => EditHousekeepingPlan::class,
        ]);

        $comp()->assertTableActionVisible('start', $pending);
        $comp()->assertTableActionHidden('complete', $pending);

        $comp()->assertTableActionVisible('complete', $working);
        $comp()->assertTableActionHidden('start', $working);

        $comp()->assertTableActionHidden('start', $done);
        $comp()->assertTableActionHidden('complete', $done);
    }
}
