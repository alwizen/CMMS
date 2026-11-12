<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingPlan;
use App\Models\HousekeepingPlanTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HousekeepingPlanStatusSyncTest extends TestCase
{
    use RefreshDatabase;

    protected HousekeepingPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::create(['code' => 'HK', 'name' => 'HK Co']);
        $area = HousekeepingArea::create(['company_id' => $company->id, 'code' => 'a', 'name' => 'A']);
        $this->plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $area->id,
            'plan_date' => now(),
            'status' => 'Draft',
        ]);
    }

    public function test_status_moves_to_in_progress_when_task_started(): void
    {
        $task = HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $this->plan->id,
            'name' => 'A',
            'status' => 'Pending',
            'photo_before' => ['x.jpg'],
        ]);

        $task->update(['status' => 'In Progress']);

        $this->assertSame('In Progress', $this->plan->fresh()->status);
    }

    public function test_status_completed_when_all_tasks_done(): void
    {
        $a = HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $this->plan->id,
            'name' => 'A',
            'status' => 'Pending',
        ]);
        $b = HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $this->plan->id,
            'name' => 'B',
            'status' => 'Pending',
        ]);

        $a->update(['status' => 'Done', 'photo_after' => ['a.jpg']]);
        $this->assertSame('In Progress', $this->plan->fresh()->status);

        $b->update(['status' => 'Done', 'photo_after' => ['b.jpg']]);
        $this->assertSame('Completed', $this->plan->fresh()->status);
    }

    public function test_status_back_to_draft_when_all_tasks_removed(): void
    {
        $task = HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $this->plan->id,
            'name' => 'A',
            'status' => 'In Progress',
        ]);

        $this->assertSame('In Progress', $this->plan->fresh()->status);

        $task->delete();

        $this->assertSame('Draft', $this->plan->fresh()->status);
    }
}
