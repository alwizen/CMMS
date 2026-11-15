<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\MaintenanceHistory;
use App\Models\MaintenanceRequest;
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
        $this->assertSame(1, Company::count());
        $this->assertSame('ft-tegal', Company::first()->code);
        $this->assertGreaterThanOrEqual(4, Area::count());
        $this->assertGreaterThanOrEqual(10, Equipment::count());
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

    public function test_it_seeds_activities(): void
    {
        $this->assertGreaterThanOrEqual(20, Activity::count());
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

    public function test_it_leaves_work_orders_empty(): void
    {
        $this->assertSame(0, WorkOrder::count());
        $this->assertSame(0, WorkOrderActivity::count());
        $this->assertSame(0, WorkOrderWorker::count());
        $this->assertSame(0, MaintenanceHistory::count());
    }

    public function test_it_is_safe_to_run_twice(): void
    {
        $counts = [
            Company::count(),
            Equipment::count(),
            MaintenanceRequest::count(),
            WorkOrder::count(),
        ];

        $this->seed(DummySeeder::class);

        $this->assertSame($counts, [
            Company::count(),
            Equipment::count(),
            MaintenanceRequest::count(),
            WorkOrder::count(),
        ]);
    }
}
