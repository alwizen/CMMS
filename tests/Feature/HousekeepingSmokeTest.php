<?php

namespace Tests\Feature;

use App\Filament\Resources\HousekeepingAreas\Pages\CreateHousekeepingArea;
use App\Filament\Resources\HousekeepingAreas\Pages\EditHousekeepingArea;
use App\Filament\Resources\HousekeepingManpowers\Pages\CreateHousekeepingManpower;
use App\Filament\Resources\HousekeepingPlans\Pages\CreateHousekeepingPlan;
use App\Filament\Resources\HousekeepingPlans\Pages\EditHousekeepingPlan;
use App\Filament\Resources\HousekeepingPlans\RelationManagers\TasksRelationManager;
use App\Filament\Resources\HousekeepingTasks\Pages\CreateHousekeepingTask;
use App\Models\Company;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class HousekeepingSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_housekeeping_pages_render(): void
    {
        Gate::before(fn () => true);
        $user = User::factory()->create();
        $this->actingAs($user);

        $company = Company::create(['code' => 'HK-1', 'name' => 'HK Co']);

        Livewire::test(CreateHousekeepingArea::class)->assertOk();
        Livewire::test(CreateHousekeepingTask::class)->assertOk();
        Livewire::test(CreateHousekeepingManpower::class)->assertOk();
        Livewire::test(CreateHousekeepingPlan::class)->assertOk();

        $area = HousekeepingArea::create(['company_id' => $company->id, 'code' => 'smoke', 'name' => 'Smoke Area']);
        $plan = HousekeepingPlan::create(['housekeeping_area_id' => $area->id, 'plan_date' => now(), 'status' => 'Draft']);

        Livewire::test(EditHousekeepingArea::class, ['record' => $area->getKey()])->assertOk();
        Livewire::test(EditHousekeepingPlan::class, ['record' => $plan->getKey()])->assertOk();

        Livewire::test(TasksRelationManager::class, [
            'ownerRecord' => $plan,
            'pageClass' => EditHousekeepingPlan::class,
        ])->assertOk();
    }
}
