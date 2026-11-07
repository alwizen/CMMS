<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\MaintenancePlan;
use App\Models\MaintenancePlanActivity;
use App\Models\MaintenanceSchedule;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkOrder;
use App\Support\Workday;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderAutomationTest extends TestCase
{
    use RefreshDatabase;

    protected Equipment $equipment;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();

        $this->user = User::factory()->create();

        $company = Company::create(['code' => 'CO', 'name' => 'Demo Company']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'AR', 'name' => 'Demo Area']);
        $type = EquipmentType::create(['code' => 'mov', 'name' => 'MOV']);

        $this->equipment = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $type->id,
            'tag_number' => 'MOV-TGL-502B',
        ]);
    }

    public function test_workday_skips_saturday_and_sunday(): void
    {
        $saturday = \Illuminate\Support\Carbon::parse('2026-11-07');
        $monday = \Illuminate\Support\Carbon::parse('2026-11-09');

        $this->assertSame($monday->toDateString(), Workday::nextWorkday($saturday)->toDateString());
        $this->assertFalse(Workday::isWorkday($saturday));
        $this->assertTrue(Workday::isWorkday($monday));
    }

    public function test_workdays_between_excludes_weekend(): void
    {
        $days = Workday::workdaysBetween(
            \Illuminate\Support\Carbon::parse('2026-11-06'),
            \Illuminate\Support\Carbon::parse('2026-11-10'),
        );

        $this->assertSame(
            ['2026-11-06', '2026-11-09', '2026-11-10'],
            array_map(fn ($d) => $d->toDateString(), $days),
        );
    }

    public function test_generates_work_order_with_checklist_for_schedule_in_window(): void
    {
        [$plan, $schedule] = $this->makePlanWithSchedule(now()->addDay()->toDateString());

        $this->artisan('maintenance:generate-work-orders')->assertSuccessful();

        $workOrder = WorkOrder::where('maintenance_schedule_id', $schedule->id)->first();

        $this->assertNotNull($workOrder);
        $this->assertSame($plan->id, $workOrder->maintenance_plan_id);
        $this->assertSame('Preventive', $workOrder->classification);
        $this->assertSame('In Progress', $workOrder->status);
        $this->assertStringStartsWith('WO-', $workOrder->work_order_number);
        $this->assertCount(2, $workOrder->workOrderActivities);
    }

    public function test_running_twice_does_not_duplicate_work_orders(): void
    {
        [, $schedule] = $this->makePlanWithSchedule(now()->addDay()->toDateString());

        $this->artisan('maintenance:generate-work-orders')->assertSuccessful();
        $this->artisan('maintenance:generate-work-orders')->assertSuccessful();

        $this->assertSame(1, WorkOrder::where('maintenance_schedule_id', $schedule->id)->count());
    }

    public function test_schedule_outside_lead_window_is_not_generated(): void
    {
        Setting::set('work_order.lead_days', 1);

        [, $schedule] = $this->makePlanWithSchedule(now()->addDays(10)->toDateString());

        $this->artisan('maintenance:generate-work-orders')->assertSuccessful();

        $this->assertSame(0, WorkOrder::where('maintenance_schedule_id', $schedule->id)->count());
    }

    public function test_weekend_schedule_gets_work_order_on_next_workday(): void
    {
        $this->travelTo(Carbon::parse('2026-11-06 10:00:00'));

        $saturday = Carbon::parse('2026-11-07');

        Setting::set('work_order.lead_days', 5);

        [, $schedule] = $this->makePlanWithSchedule($saturday->toDateString());

        $this->artisan('maintenance:generate-work-orders')->assertSuccessful();

        $workOrder = WorkOrder::where('maintenance_schedule_id', $schedule->id)->first();

        $this->assertNotNull($workOrder);
        $this->assertTrue(Workday::isWorkday($workOrder->start_at));
    }

    public function test_disabled_auto_generate_creates_nothing(): void
    {
        Setting::set('work_order.auto_generate', false);

        [, $schedule] = $this->makePlanWithSchedule(now()->addDay()->toDateString());

        $this->artisan('maintenance:generate-work-orders')->assertSuccessful();

        $this->assertSame(0, WorkOrder::where('maintenance_schedule_id', $schedule->id)->count());
    }

    public function test_overdue_schedule_within_backfill_is_rescheduled_and_marked_delayed(): void
    {
        Setting::set('work_order.backfill_days', 14);

        [, $schedule] = $this->makePlanWithSchedule(now()->subDays(3)->toDateString());

        $this->artisan('maintenance:generate-work-orders')->assertSuccessful();

        $this->assertNotNull(WorkOrder::where('maintenance_schedule_id', $schedule->id)->first());
        $this->assertSame(MaintenanceSchedule::STATUSES['Delayed'], $schedule->fresh()->status);
    }

    public function test_overdue_schedule_beyond_backfill_is_ignored(): void
    {
        Setting::set('work_order.backfill_days', 14);

        [, $schedule] = $this->makePlanWithSchedule(now()->subDays(30)->toDateString());

        $this->artisan('maintenance:generate-work-orders')->assertSuccessful();

        $this->assertSame(0, WorkOrder::where('maintenance_schedule_id', $schedule->id)->count());
    }

    public function test_completing_work_order_marks_its_schedule_completed(): void
    {
        [, $schedule] = $this->makePlanWithSchedule(now()->addDay()->toDateString());

        $this->artisan('maintenance:generate-work-orders')->assertSuccessful();

        $workOrder = WorkOrder::where('maintenance_schedule_id', $schedule->id)->firstOrFail();
        $workOrder->update(['status' => 'Completed']);

        $this->assertSame(MaintenanceSchedule::STATUSES['Completed'], $schedule->fresh()->status);
    }

    public function test_dry_run_does_not_persist(): void
    {
        [, $schedule] = $this->makePlanWithSchedule(now()->addDay()->toDateString());

        $this->artisan('maintenance:generate-work-orders', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame(0, WorkOrder::where('maintenance_schedule_id', $schedule->id)->count());
    }

    public function test_issued_by_falls_back_to_system_user(): void
    {
        [, $schedule] = $this->makePlanWithSchedule(now()->addDay()->toDateString());

        $this->artisan('maintenance:generate-work-orders')->assertSuccessful();

        $workOrder = WorkOrder::where('maintenance_schedule_id', $schedule->id)->first();

        $this->assertDatabaseHas('users', ['id' => $workOrder->issued_by, 'email' => 'system@internal.local']);
    }

    /**
     * @return array{0: MaintenancePlan, 1: MaintenanceSchedule}
     */
    protected function makePlanWithSchedule(string $scheduledDate): array
    {
        $plan = MaintenancePlan::create([
            'equipment_id' => $this->equipment->id,
            'maintenance_classification' => 'Preventive',
            'interval' => 'monthly',
            'start_date' => now()->subMonth()->toDateString(),
            'created_by' => $this->user->id,
            'technician_coordinator_id' => $this->user->id,
            'status' => 'Active',
        ]);

        $activityA = Activity::create([
            'equipment_type_id' => $this->equipment->equipment_type_id,
            'name' => 'Inspeksi Visual',
            'type' => 'visual_check',
            'maintenance_classification' => 'preventive',
            'interval' => 'monthly',
            'answer_type' => 'qualitative',
            'status' => true,
        ]);

        $activityB = Activity::create([
            'equipment_type_id' => $this->equipment->equipment_type_id,
            'name' => 'Uji Operasi',
            'type' => 'testing',
            'maintenance_classification' => 'preventive',
            'interval' => 'monthly',
            'answer_type' => 'quantitative',
            'unit' => 'detik',
            'status' => true,
        ]);

        MaintenancePlanActivity::create(['maintenance_plan_id' => $plan->id, 'activity_id' => $activityA->id, 'sort_order' => 1]);
        MaintenancePlanActivity::create(['maintenance_plan_id' => $plan->id, 'activity_id' => $activityB->id, 'sort_order' => 2]);

        $schedule = MaintenanceSchedule::create([
            'maintenance_plan_id' => $plan->id,
            'equipment_id' => $this->equipment->id,
            'scheduled_date' => $scheduledDate,
            'status' => MaintenanceSchedule::STATUSES['Scheduled'],
        ]);

        return [$plan, $schedule];
    }
}
