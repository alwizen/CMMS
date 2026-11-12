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

class HousekeepingPhotoColumnTest extends TestCase
{
    use RefreshDatabase;

    public function test_photo_column_shows_count_when_photos_exist(): void
    {
        Gate::before(fn () => true);
        $this->actingAs(User::factory()->create());

        $company = Company::create(['code' => 'HK', 'name' => 'HK Co']);
        $area = HousekeepingArea::create(['company_id' => $company->id, 'code' => 'a', 'name' => 'A']);
        $plan = HousekeepingPlan::create(['housekeeping_area_id' => $area->id, 'plan_date' => now(), 'status' => 'Draft']);
        $task = HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Task',
            'status' => 'Done',
            'photo_before' => ['housekeeping/plan-tasks/before/a.jpg'],
            'photo_after' => ['housekeeping/plan-tasks/after/a.jpg'],
        ]);

        $component = Livewire::test(TasksRelationManager::class, [
            'ownerRecord' => $plan,
            'pageClass' => EditHousekeepingPlan::class,
        ]);

        $component->assertTableColumnStateSet('photo_before', '1 foto', record: $task);

        $html = $component->html();
        $this->assertStringContainsString('1 foto', $html);
        $this->assertStringNotContainsString('Belum ada', $html);
    }
}
