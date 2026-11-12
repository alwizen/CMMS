<?php

namespace Tests\Feature;

use App\Filament\Resources\HousekeepingPlans\Pages\ViewHousekeepingPlan;
use App\Filament\Resources\HousekeepingPlans\RelationManagers\ManpowersRelationManager;
use App\Filament\Resources\HousekeepingPlans\RelationManagers\TasksRelationManager;
use App\Models\Company;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingManpower;
use App\Models\HousekeepingPlan;
use App\Models\HousekeepingPlanTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class HousekeepingPlanViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_page_and_relation_managers_render(): void
    {
        Gate::before(fn () => true);
        $this->actingAs(User::factory()->create());

        $company = Company::create(['code' => 'HK-1', 'name' => 'HK Co']);
        $area = HousekeepingArea::create(['company_id' => $company->id, 'code' => 'a', 'name' => 'Area A']);
        $plan = HousekeepingPlan::create(['housekeeping_area_id' => $area->id, 'plan_date' => now(), 'status' => 'Draft']);

        $manpower = HousekeepingManpower::create([
            'housekeeping_area_id' => $area->id,
            'name' => 'Budi',
            'is_active' => true,
        ]);
        $plan->manpowers()->attach($manpower->id);

        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Pemotongan Rumput',
            'status' => 'Done',
            'photo_before' => ['housekeeping/plan-tasks/before/b.jpg'],
            'photo_after' => ['housekeeping/plan-tasks/after/a.jpg'],
        ]);

        Livewire::test(ViewHousekeepingPlan::class, ['record' => $plan->getKey()])
            ->assertOk()
            ->assertSee('Plan Number')
            ->assertSee('Pemotongan Rumput');

        Livewire::test(ManpowersRelationManager::class, [
            'ownerRecord' => $plan,
            'pageClass' => ViewHousekeepingPlan::class,
        ])->assertOk();

        Livewire::test(TasksRelationManager::class, [
            'ownerRecord' => $plan,
            'pageClass' => ViewHousekeepingPlan::class,
        ])->assertOk();
    }
}
