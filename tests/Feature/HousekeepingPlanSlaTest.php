<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingPlan;
use App\Models\HousekeepingPlanTask;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HousekeepingPlanSlaTest extends TestCase
{
    use RefreshDatabase;

    protected HousekeepingArea $area;

    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::create(['code' => 'HK', 'name' => 'HK Co']);
        $this->area = HousekeepingArea::create([
            'company_id' => $company->id,
            'code' => 'kantor',
            'name' => 'Kantor',
            'sla_days' => 1,
        ]);
    }

    public function test_area_default_sla_is_one_day(): void
    {
        $area = HousekeepingArea::create([
            'company_id' => $this->area->company_id,
            'code' => 'default',
            'name' => 'Default',
        ]);

        $this->assertSame(1, $area->sla_days);
    }

    public function test_due_date_follows_area_sla(): void
    {
        $this->area->update(['sla_days' => 14]);

        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => '2026-11-01',
            'status' => 'Draft',
        ]);

        $this->assertSame('2026-11-15', $plan->due_date->toDateString());
    }

    public function test_progress_and_status_sla_reflect_tasks(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-01 09:00:00'));

        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => '2026-11-01',
            'status' => 'Draft',
        ]);

        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Sapu',
            'status' => 'Done',
        ]);
        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Pel',
            'status' => 'Pending',
        ]);

        $plan->refresh();

        $this->assertSame(50, $plan->progress_percent);
        $this->assertSame('On Track', $plan->sla_status);
        $this->assertFalse($plan->is_overdue);
    }

    public function test_plan_becomes_overdue_after_sla_deadline(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-05 09:00:00'));

        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => '2026-11-01',
            'status' => 'In Progress',
        ]);

        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Sapu',
            'status' => 'In Progress',
        ]);

        $plan->refresh();

        $this->assertSame('Overdue', $plan->sla_status);
        $this->assertTrue($plan->is_overdue);
    }

    public function test_completed_within_sla_and_completion_days(): void
    {
        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => '2026-11-01',
            'status' => 'Draft',
        ]);

        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Sapu',
            'status' => 'Done',
            'started_at' => '2026-11-01 08:00:00',
            'completed_at' => '2026-11-01 10:00:00',
        ]);

        $plan->refresh();

        $this->assertSame('Completed', $plan->status);
        $this->assertTrue($plan->completed_within_sla);
        $this->assertSame(0, $plan->completion_days);
    }

    public function test_late_completion_is_flagged_as_not_within_sla(): void
    {
        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => '2026-11-01',
            'status' => 'Draft',
        ]);

        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Sapu',
            'status' => 'Done',
            'started_at' => '2026-11-01 08:00:00',
            'completed_at' => '2026-11-05 10:00:00',
        ]);

        $plan->refresh();

        $this->assertFalse($plan->completed_within_sla);
        $this->assertSame(4, $plan->completion_days);
    }
}
