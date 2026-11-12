<?php

namespace Tests\Feature;

use App\Filament\Resources\HousekeepingPlans\Pages\EditHousekeepingPlan;
use App\Filament\Resources\HousekeepingPlans\RelationManagers\TasksRelationManager;
use App\Models\Company;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class HousekeepingTaskFormPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_create_form_has_before_and_after_photo_fields(): void
    {
        Gate::before(fn () => true);
        $this->actingAs(User::factory()->create());

        $company = Company::create(['code' => 'HK-1', 'name' => 'HK Co']);
        $area = HousekeepingArea::create(['company_id' => $company->id, 'code' => 'a', 'name' => 'A']);
        $plan = HousekeepingPlan::create(['housekeeping_area_id' => $area->id, 'plan_date' => now(), 'status' => 'Draft']);

        Livewire::test(TasksRelationManager::class, [
            'ownerRecord' => $plan,
            'pageClass' => EditHousekeepingPlan::class,
        ])
            ->mountTableAction('create')
            ->assertFormFieldExists('photo_before')
            ->assertFormFieldExists('photo_after');
    }
}
