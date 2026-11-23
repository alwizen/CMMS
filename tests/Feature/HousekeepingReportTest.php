<?php

namespace Tests\Feature;

use App\Filament\Pages\HousekeepingReport;
use App\Models\Company;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingPlan;
use App\Models\HousekeepingPlanTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class HousekeepingReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_page_renders_with_task_rows(): void
    {
        Gate::before(fn () => true);
        $user = User::factory()->create();
        $this->actingAs($user);

        $company = Company::create(['code' => 'HK', 'name' => 'HK Co']);
        $area = HousekeepingArea::create(['company_id' => $company->id, 'code' => 'a', 'name' => 'Area A']);
        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $area->id,
            'plan_date' => '2026-01-05',
            'status' => 'In Progress',
            'created_by' => $user->id,
        ]);

        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Pemotongan Rumput',
            'status' => 'Done',
        ]);
        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Pembersihan Selokan',
            'status' => 'Pending',
        ]);

        Livewire::test(HousekeepingReport::class)
            ->assertOk()
            ->assertSee('Pemotongan Rumput')
            ->assertSee('Pembersihan Selokan')
            ->assertSee('Area A');
    }

    public function test_report_can_filter_by_status(): void
    {
        Gate::before(fn () => true);
        $user = User::factory()->create();
        $this->actingAs($user);

        $company = Company::create(['code' => 'HK', 'name' => 'HK Co']);
        $area = HousekeepingArea::create(['company_id' => $company->id, 'code' => 'a', 'name' => 'Area A']);
        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $area->id,
            'plan_date' => '2026-01-05',
            'created_by' => $user->id,
        ]);

        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Tugas Selesai',
            'status' => 'Done',
        ]);
        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Tugas Pending',
            'status' => 'Pending',
        ]);

        Livewire::test(HousekeepingReport::class)
            ->filterTable('status', 'Done')
            ->assertSee('Tugas Selesai')
            ->assertDontSee('Tugas Pending');
    }

    public function test_documentation_action_shows_before_and_after_photos(): void
    {
        Gate::before(fn () => true);
        $user = User::factory()->create();
        $this->actingAs($user);

        $company = Company::create(['code' => 'HK', 'name' => 'HK Co']);
        $area = HousekeepingArea::create(['company_id' => $company->id, 'code' => 'a', 'name' => 'Area A']);
        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $area->id,
            'plan_date' => '2026-01-05',
            'created_by' => $user->id,
        ]);

        $task = HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Pemotongan Rumput',
            'status' => 'Done',
            'note' => 'Sudah rapi',
            'photo_before' => ['housekeeping/plan-tasks/before/a.jpg'],
            'photo_after' => ['housekeeping/plan-tasks/after/b.jpg'],
        ]);

        Livewire::test(HousekeepingReport::class)
            ->mountTableAction('documentation', $task)
            ->assertMountedActionModalSee('Foto Before')
            ->assertMountedActionModalSee('Foto After')
            ->assertMountedActionModalSee('Sudah rapi');
    }
}
