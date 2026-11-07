<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\MaintenanceHistory;
use App\Models\MaintenancePlan;
use App\Models\MaintenancePlanActivity;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceSchedule;
use App\Models\MeterLog;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderActivity;
use App\Models\WorkOrderWorker;
use Database\Seeders\DummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DummySeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();

        $this->seed(DummySeeder::class);
    }

    public function test_it_seeds_companies_areas_and_equipment(): void
    {
        $this->assertGreaterThanOrEqual(3, Company::count());
        $this->assertGreaterThanOrEqual(9, Area::count());
        $this->assertGreaterThanOrEqual(20, Equipment::count());
        $this->assertSame(0, Equipment::whereNull('equipment_type_id')->count());
    }

    public function test_it_seeds_users_with_roles(): void
    {
        $this->assertNotNull(User::where('email', 'admin@admin.com')->first());
        $this->assertTrue(User::where('email', 'admin@admin.com')->first()->hasRole('super_admin'));
        $this->assertTrue(User::where('email', 'spv@demo.com')->first()->hasRole('spv_mos'));
        $this->assertTrue(User::where('email', 'pengawas@demo.com')->first()->hasRole('pengawas'));
        $this->assertTrue(User::where('email', 'risna@demo.com')->first()->hasRole('ltsa_tad'));
    }

    public function test_it_seeds_activities_plans_and_plan_checklists(): void
    {
        $this->assertGreaterThanOrEqual(20, Activity::count());
        $this->assertGreaterThanOrEqual(10, MaintenancePlan::count());
        $this->assertGreaterThan(0, MaintenancePlanActivity::count());

        foreach (MaintenancePlan::all() as $plan) {
            $this->assertNotNull($plan->plan_number);
            $this->assertGreaterThan(0, $plan->planActivities()->count());
        }
    }

    public function test_it_seeds_schedules_with_valid_statuses(): void
    {
        $this->assertGreaterThan(0, MaintenanceSchedule::count());

        $invalid = MaintenanceSchedule::whereNotIn('status', array_values(MaintenanceSchedule::STATUSES))->count();
        $this->assertSame(0, $invalid);

        foreach (MaintenanceSchedule::all() as $schedule) {
            $this->assertNotNull($schedule->schedule_number);
        }
    }

    public function test_it_seeds_requests_including_open_and_approved(): void
    {
        $this->assertGreaterThan(0, MaintenanceRequest::count());
        $this->assertGreaterThan(0, MaintenanceRequest::where('status', 'Open')->count());
        $this->assertGreaterThan(0, MaintenanceRequest::where('status', 'Approved')->count());
        $this->assertGreaterThan(0, MaintenanceRequest::where('status', 'Rejected')->count());

        $invalid = MaintenanceRequest::whereNotIn('status', array_keys(MaintenanceRequest::STATUSES))->count();
        $this->assertSame(0, $invalid);
    }

    public function test_it_seeds_work_orders_with_activities_and_workers(): void
    {
        $this->assertGreaterThan(0, WorkOrder::count());
        $this->assertGreaterThan(0, WorkOrderActivity::count());
        $this->assertGreaterThan(0, WorkOrderWorker::count());

        $invalid = WorkOrder::whereNotIn('status', array_values(WorkOrder::STATUSES))->count();
        $this->assertSame(0, $invalid);

        foreach (WorkOrder::whereNotNull('maintenance_schedule_id')->get() as $workOrder) {
            $this->assertNotNull($workOrder->work_order_number);
            $this->assertSame(1, WorkOrder::where('maintenance_schedule_id', $workOrder->maintenance_schedule_id)->count());
        }
    }

    public function test_completed_work_orders_produce_maintenance_history(): void
    {
        $completed = WorkOrder::where('status', WorkOrder::STATUSES['Completed'])->count();

        $this->assertGreaterThan(0, $completed);
        $this->assertGreaterThan(0, MaintenanceHistory::count());
        $this->assertSame($completed, MaintenanceHistory::count());
    }

    public function test_it_seeds_corrective_work_orders_linked_to_requests(): void
    {
        $corrective = WorkOrder::where('classification', 'Corrective')->whereNotNull('maintenance_request_id')->count();

        $this->assertGreaterThan(0, $corrective);
    }

    public function test_it_seeds_preventive_work_orders_linked_to_plans(): void
    {
        $preventive = WorkOrder::where('classification', 'Preventive')->whereNotNull('maintenance_plan_id')->count();

        $this->assertGreaterThan(0, $preventive);
    }

    public function test_it_seeds_meter_logs(): void
    {
        $this->assertGreaterThan(0, MeterLog::count());
        $this->assertSame(0, MeterLog::whereNull('recorded_by')->count());
    }

    public function test_it_is_safe_to_run_twice(): void
    {
        $counts = [
            Company::count(),
            Equipment::count(),
            MaintenancePlan::count(),
            MaintenanceRequest::count(),
            WorkOrder::count(),
            MeterLog::count(),
        ];

        $this->seed(DummySeeder::class);

        $this->assertSame($counts, [
            Company::count(),
            Equipment::count(),
            MaintenancePlan::count(),
            MaintenanceRequest::count(),
            WorkOrder::count(),
            MeterLog::count(),
        ]);
    }
}
