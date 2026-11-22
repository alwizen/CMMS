<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiHousekeepingVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected HousekeepingArea $nina;

    protected HousekeepingArea $rasman;

    protected HousekeepingPlan $ninaPlan;

    protected HousekeepingPlan $rasmanPlan;

    protected User $ninaUser;

    protected User $rasmanUser;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'spv_mos', 'spv_gos', 'spv_hsse', 'pengawas', 'mos', 'patra-jasa'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $company = Company::create(['code' => 'HK', 'name' => 'HK Co']);

        $this->ninaUser = User::factory()->create(['name' => 'Nina', 'status' => true]);
        $this->ninaUser->assignRole('patra-jasa');

        $this->rasmanUser = User::factory()->create(['name' => 'Rasman', 'status' => true]);
        $this->rasmanUser->assignRole('patra-jasa');

        $this->nina = HousekeepingArea::create([
            'company_id' => $company->id,
            'code' => 'kantor',
            'name' => 'Kantor',
            'sla_days' => 30,
            'supervisor_id' => $this->ninaUser->id,
        ]);

        $this->rasman = HousekeepingArea::create([
            'company_id' => $company->id,
            'code' => 'penerimaan',
            'name' => 'Penerimaan',
            'sla_days' => 30,
            'supervisor_id' => $this->rasmanUser->id,
        ]);

        $this->ninaPlan = HousekeepingPlan::create([
            'housekeeping_area_id' => $this->nina->id,
            'plan_date' => '2026-11-18',
            'status' => 'In Progress',
        ]);

        $this->rasmanPlan = HousekeepingPlan::create([
            'housekeeping_area_id' => $this->rasman->id,
            'plan_date' => '2026-11-18',
            'status' => 'In Progress',
        ]);
    }

    public function test_petugas_only_lists_plans_of_area_they_are_responsible_for(): void
    {
        Sanctum::actingAs($this->ninaUser, ['*']);

        $this->getJson('/api/housekeeping-plans')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.plan_number', $this->ninaPlan->plan_number);
    }

    public function test_other_petugas_sees_their_own_plan_only(): void
    {
        Sanctum::actingAs($this->rasmanUser, ['*']);

        $this->getJson('/api/housekeeping-plans')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.plan_number', $this->rasmanPlan->plan_number);
    }

    public function test_petugas_cannot_open_plan_of_other_area(): void
    {
        Sanctum::actingAs($this->ninaUser, ['*']);

        $this->getJson('/api/housekeeping-plans/'.$this->rasmanPlan->id)->assertForbidden();
        $this->getJson('/api/housekeeping-plans/'.$this->ninaPlan->id)->assertOk();
    }

    public function test_assigned_petugas_can_see_plan(): void
    {
        $this->ninaPlan->users()->attach($this->rasmanUser->id);

        Sanctum::actingAs($this->rasmanUser, ['*']);

        $this->getJson('/api/housekeeping-plans/'.$this->ninaPlan->id)->assertOk();
    }

    public function test_supervisor_sees_all_plans(): void
    {
        $supervisor = User::factory()->create(['status' => true]);
        $supervisor->assignRole('spv_gos');

        Sanctum::actingAs($supervisor, ['*']);

        $this->getJson('/api/housekeeping-plans')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_dashboard_counts_only_own_plans(): void
    {
        Sanctum::actingAs($this->ninaUser, ['*']);

        $this->getJson('/api/dashboard/housekeeping')
            ->assertOk()
            ->assertJsonPath('data.stats.plans_active', 1)
            ->assertJsonPath('data.active_plans.0.plan_number', $this->ninaPlan->plan_number);
    }

    public function test_dashboard_includes_all_plans_for_supervisor(): void
    {
        $supervisor = User::factory()->create(['status' => true]);
        $supervisor->assignRole('spv_gos');

        Sanctum::actingAs($supervisor, ['*']);

        $this->getJson('/api/dashboard/housekeeping')
            ->assertOk()
            ->assertJsonPath('data.stats.plans_active', 2);
    }
}
