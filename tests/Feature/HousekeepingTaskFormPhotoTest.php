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

class HousekeepingTaskFormPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_form_has_no_per_task_photo_fields(): void
    {
        Gate::before(fn () => true);
        Role::findOrCreate('spv_gos', 'web');
        Role::findOrCreate('patra-jasa', 'web');

        $petugas = User::factory()->create(['status' => true]);
        $petugas->assignRole('patra-jasa');
        $this->actingAs($petugas);

        $company = Company::create(['code' => 'HK-1', 'name' => 'HK Co']);
        $area = HousekeepingArea::create(['company_id' => $company->id, 'code' => 'a', 'name' => 'A']);
        $plan = HousekeepingPlan::create(['housekeeping_area_id' => $area->id, 'plan_date' => now(), 'status' => 'Draft']);

        Livewire::test(TasksRelationManager::class, [
            'ownerRecord' => $plan,
            'pageClass' => EditHousekeepingPlan::class,
        ])
            ->mountTableAction('create')
            ->assertFormFieldDoesNotExist('photo_before')
            ->assertFormFieldDoesNotExist('photo_after');
    }

    public function test_task_upload_actions_have_photo_upload_fields(): void
    {
        Gate::before(fn () => true);
        Role::findOrCreate('spv_gos', 'web');
        Role::findOrCreate('patra-jasa', 'web');

        $petugas = User::factory()->create(['status' => true]);
        $petugas->assignRole('patra-jasa');
        $this->actingAs($petugas);

        $company = Company::create(['code' => 'HK-2', 'name' => 'HK Co']);
        $area = HousekeepingArea::create(['company_id' => $company->id, 'code' => 'b', 'name' => 'B']);
        $plan = HousekeepingPlan::create(['housekeeping_area_id' => $area->id, 'plan_date' => now(), 'status' => 'Draft']);

        $task = HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Sapu',
            'status' => 'Pending',
        ]);

        Livewire::test(TasksRelationManager::class, [
            'ownerRecord' => $plan,
            'pageClass' => EditHousekeepingPlan::class,
        ])
            ->mountTableAction('upload_before', $task)
            ->assertFormFieldExists('photos')
            ->mountTableAction('upload_after', $task)
            ->assertFormFieldExists('photos');
    }
}
