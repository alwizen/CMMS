<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingPlan;
use App\Models\HousekeepingPlanTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiDailyReportTest extends TestCase
{
    use RefreshDatabase;

    protected HousekeepingArea $area;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'spv_mos', 'spv_gos', 'spv_hsse', 'pengawas', 'ltsa_tad', 'patra-jasa'] as $role) {
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

        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => '2026-11-18',
            'status' => 'In Progress',
        ]);

        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Sapu Daun',
            'status' => 'Pending',
        ]);

        // Approval diberikan setelah checklist dibuat: mengubah pekerjaan
        // membatalkan approval sebelumnya (butuh review ulang).
        $plan->update([
            'approval_status' => HousekeepingPlan::APPROVAL_APPROVED,
            'approved_at' => now(),
        ]);
    }

    protected function makeUser(string $role): User
    {
        $user = User::factory()->create(['status' => true]);
        $user->assignRole($role);

        return $user;
    }

    public function test_petugas_housekeeping_can_download_daily_report(): void
    {
        Sanctum::actingAs($this->makeUser('patra-jasa'), ['*']);

        $response = $this->get('/api/reports/daily-housekeeping?date=2026-11-18');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition', 'attachment; filename="laporan-harian-housekeeping-20261118.pdf"');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_full_access_roles_can_download_daily_report(): void
    {
        foreach (['super_admin', 'spv_gos', 'pengawas'] as $role) {
            Sanctum::actingAs($this->makeUser($role), ['*']);

            $this->get('/api/reports/daily-housekeeping?date=2026-11-18')
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');
        }
    }

    public function test_other_roles_are_forbidden(): void
    {
        Sanctum::actingAs($this->makeUser('ltsa_tad'), ['*']);

        $this->get('/api/reports/daily-housekeeping?date=2026-11-18')
            ->assertForbidden();
    }

    public function test_inactive_user_is_forbidden(): void
    {
        $user = $this->makeUser('patra-jasa');
        $user->update(['status' => false]);

        Sanctum::actingAs($user, ['*']);

        $this->get('/api/reports/daily-housekeeping?date=2026-11-18')
            ->assertForbidden();
    }

    public function test_report_is_blocked_while_work_awaits_spv_review(): void
    {
        // Pekerjaan berjalan tetapi belum direview/disetujui SPV GOS.
        HousekeepingPlan::query()->update([
            'status' => 'In Progress',
            'approval_status' => HousekeepingPlan::APPROVAL_PENDING,
            'approved_at' => null,
        ]);

        Sanctum::actingAs($this->makeUser('patra-jasa'), ['*']);

        $this->getJson('/api/reports/daily-housekeeping?date=2026-11-18')
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['message', 'data' => ['pending_plans']]);
    }

    public function test_report_is_allowed_after_spv_gos_approval(): void
    {
        // Approval SPV GOS adalah trigger laporan harian.
        HousekeepingPlan::query()->update([
            'status' => 'In Progress',
            'approval_status' => HousekeepingPlan::APPROVAL_APPROVED,
            'approved_at' => now(),
        ]);

        Sanctum::actingAs($this->makeUser('patra-jasa'), ['*']);

        $this->getJson('/api/reports/daily-housekeeping?date=2026-11-18')->assertOk();
    }

    public function test_report_is_blocked_when_plan_is_rejected(): void
    {
        HousekeepingPlan::query()->update([
            'approval_status' => HousekeepingPlan::APPROVAL_REJECTED,
            'approved_at' => now(),
        ]);

        Sanctum::actingAs($this->makeUser('patra-jasa'), ['*']);

        $this->getJson('/api/reports/daily-housekeeping?date=2026-11-18')
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['data' => ['rejected_plans']]);
    }

    public function test_completed_plan_from_another_date_does_not_block_report(): void
    {
        // Plan yang sudah selesai pada tanggal lain tidak muncul di laporan
        // tanggal ini, jadi approval-nya tidak relevan.
        HousekeepingPlan::create([
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => '2026-11-10',
            'status' => 'Completed',
        ]);

        Sanctum::actingAs($this->makeUser('patra-jasa'), ['*']);

        $this->getJson('/api/reports/daily-housekeeping?date=2026-11-18')->assertOk();
    }

    public function test_running_plan_from_another_date_blocks_report(): void
    {
        // Plan yang masih berjalan ikut tampil di laporan, jadi harus
        // direview SPV GOS lebih dulu.
        HousekeepingPlan::create([
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => '2026-11-10',
            'status' => 'In Progress',
        ]);

        Sanctum::actingAs($this->makeUser('patra-jasa'), ['*']);

        $this->getJson('/api/reports/daily-housekeeping?date=2026-11-18')
            ->assertStatus(422);
    }

    public function test_requires_authentication(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/reports/daily-housekeeping')->assertUnauthorized();
    }
}
