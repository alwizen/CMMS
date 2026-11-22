<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingPlan;
use App\Models\HousekeepingPlanTask;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiHousekeepingDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected HousekeepingArea $area;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-11-18 10:00:00'));

        foreach (['super_admin', 'spv_mos', 'spv_gos', 'spv_hsse', 'pengawas', 'mos', 'patra-jasa'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $company = Company::create(['code' => 'HK', 'name' => 'HK Co']);

        $this->area = HousekeepingArea::create([
            'company_id' => $company->id,
            'code' => 'penerimaan',
            'name' => 'Penerimaan',
            'sla_days' => 14,
            'kpi_class' => 'outdoor',
        ]);
    }

    protected function makeUser(string $role): User
    {
        $user = User::factory()->create(['status' => true]);
        $user->assignRole($role);

        // Petugas hanya melihat plan area yang ia pertanggungjawabkan.
        if ($role === 'patra-jasa') {
            $this->area->update(['supervisor_id' => $user->id]);
        }

        return $user;
    }

    protected function makePlan(string $date, string $status = 'In Progress', array $tasks = []): HousekeepingPlan
    {
        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => $date,
            'status' => $status,
        ]);

        foreach ($tasks as $task) {
            HousekeepingPlanTask::create([
                'housekeeping_plan_id' => $plan->id,
                'name' => $task['name'],
                'status' => $task['status'],
                'completed_at' => $task['completed_at'] ?? null,
            ]);
        }

        return $plan;
    }

    public function test_petugas_housekeeping_gets_housekeeping_stats(): void
    {
        Sanctum::actingAs($this->makeUser('patra-jasa'), ['*']);

        $this->makePlan('2026-11-18', 'In Progress', [
            ['name' => 'Sapu', 'status' => 'Done', 'completed_at' => '2026-11-18 08:00:00'],
            ['name' => 'Pel', 'status' => 'In Progress'],
        ]);

        // Plan lama yang masih berjalan (SLA 14 hari, belum lewat).
        $this->makePlan('2026-11-10', 'In Progress', [
            ['name' => 'Potong Rumput', 'status' => 'Pending'],
        ]);

        // Plan yang sudah selesai hari ini.
        $this->makePlan('2026-11-17', 'Completed', [
            ['name' => 'Bersih Selokan', 'status' => 'Done', 'completed_at' => '2026-11-18 09:00:00'],
        ]);

        $response = $this->getJson('/api/dashboard/housekeeping')->assertOk();

        $response->assertJsonPath('success', true)
            ->assertJsonPath('data.stats.plans_today', 1)
            ->assertJsonPath('data.stats.plans_active', 2)
            ->assertJsonPath('data.stats.plans_overdue', 0)
            ->assertJsonPath('data.stats.plans_completed_today', 1)
            ->assertJsonPath('data.stats.tasks_open', 2)
            ->assertJsonPath('data.stats.tasks_done_today', 2)
            ->assertJsonPath('data.stats.tasks_total', 3)
            ->assertJsonPath('data.stats.tasks_done', 1)
            ->assertJsonPath('data.stats.completion_rate', 33)
            ->assertJsonStructure([
                'data' => [
                    'active_plans' => [
                        '*' => ['id', 'plan_number', 'progress_percent', 'sla_status', 'area'],
                    ],
                ],
            ]);
    }

    public function test_overdue_plans_are_reported_separately(): void
    {
        Sanctum::actingAs($this->makeUser('patra-jasa'), ['*']);

        $this->area->update(['sla_days' => 1]);

        $this->makePlan('2026-11-10', 'In Progress', [
            ['name' => 'Terlambat', 'status' => 'Pending'],
        ]);

        $response = $this->getJson('/api/dashboard/housekeeping')->assertOk();

        $response->assertJsonPath('data.stats.plans_active', 0)
            ->assertJsonPath('data.stats.plans_overdue', 1);
    }

    public function test_full_access_roles_can_access(): void
    {
        foreach (['super_admin', 'spv_gos', 'pengawas'] as $role) {
            Sanctum::actingAs($this->makeUser($role), ['*']);

            $this->getJson('/api/dashboard/housekeeping')->assertOk();
        }
    }

    public function test_other_roles_are_forbidden(): void
    {
        Sanctum::actingAs($this->makeUser('mos'), ['*']);

        $this->getJson('/api/dashboard/housekeeping')->assertForbidden();
    }

    public function test_requires_authentication(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/dashboard/housekeeping')->assertUnauthorized();
    }
}
