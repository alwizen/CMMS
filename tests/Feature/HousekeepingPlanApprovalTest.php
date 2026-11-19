<?php

namespace Tests\Feature;

use App\Filament\Resources\HousekeepingPlans\Pages\ListHousekeepingPlans;
use App\Filament\Resources\HousekeepingPlans\Pages\ViewHousekeepingPlan;
use App\Models\Company;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingPlan;
use App\Models\HousekeepingPlanTask;
use App\Models\User;
use App\Support\HousekeepingReportGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HousekeepingPlanApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected HousekeepingArea $area;

    protected User $spv;

    protected User $petugas;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'spv_mos', 'spv_gos', 'spv_hsse', 'pengawas', 'ltsa_tad', 'patra-jasa'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        Gate::before(fn () => true);

        $company = Company::create(['code' => 'HK', 'name' => 'HK Co']);

        $this->area = HousekeepingArea::create([
            'company_id' => $company->id,
            'code' => 'penerimaan',
            'name' => 'Penerimaan',
            'sla_days' => 14,
            'kpi_class' => 'outdoor',
        ]);

        $this->spv = User::factory()->create(['status' => true]);
        $this->spv->assignRole('spv_gos');

        $this->petugas = User::factory()->create(['status' => true]);
        $this->petugas->assignRole('patra-jasa');
    }

    protected function makePlan(string $date = '2026-11-18'): HousekeepingPlan
    {
        return HousekeepingPlan::create([
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => $date,
            'status' => 'Draft',
        ]);
    }

    protected function completePlan(HousekeepingPlan $plan, array $tasks = ['Sapu']): void
    {
        foreach ($tasks as $name) {
            HousekeepingPlanTask::create([
                'housekeeping_plan_id' => $plan->id,
                'name' => $name,
                'status' => 'Done',
            ]);
        }

        $plan->refresh();
    }

    public function test_new_plan_can_be_worked_on_without_initial_approval(): void
    {
        $plan = $this->makePlan();

        $this->assertSame(HousekeepingPlan::APPROVAL_PENDING, $plan->approval_status);
        $this->assertFalse($plan->isApproved());
        $this->assertFalse($plan->hasStartedWork());
    }

    public function test_report_needs_spv_gos_approval_even_before_work_finishes(): void
    {
        $plan = $this->makePlan();

        // Plan dibuat SPV GOS: patra-jasa langsung mengerjakan tanpa approval awal.
        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Sapu',
            'status' => 'In Progress',
        ]);

        $inspection = HousekeepingReportGate::inspect($plan->plan_date->toDateString());

        // Approval SPV GOS adalah trigger laporan harian.
        $this->assertFalse($inspection['allowed']);
        $this->assertCount(1, $inspection['pending']);
        $this->assertStringContainsString('belum direview/disetujui SPV GOS', HousekeepingReportGate::message($inspection));
    }

    public function test_approval_can_review_work_in_progress(): void
    {
        $this->actingAs($this->spv);

        $plan = $this->makePlan();

        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Sapu',
            'status' => 'In Progress',
        ]);

        Livewire::test(ViewHousekeepingPlan::class, ['record' => $plan->getKey()])
            ->assertActionVisible('approvePlan')
            ->callAction('approvePlan', data: ['approval_notes' => 'Sudah sesuai rencana'])
            ->assertHasNoActionErrors();

        $this->assertSame(HousekeepingPlan::APPROVAL_APPROVED, $plan->fresh()->approval_status);
        $this->assertTrue(HousekeepingReportGate::allows($plan->plan_date->toDateString()));
    }

    public function test_work_change_after_approval_requires_review_again(): void
    {
        $this->actingAs($this->spv);

        $plan = $this->makePlan();
        $task = HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Sapu',
            'status' => 'In Progress',
        ]);

        Livewire::test(ViewHousekeepingPlan::class, ['record' => $plan->getKey()])
            ->callAction('approvePlan', data: ['approval_notes' => 'OK']);

        $this->assertTrue(HousekeepingReportGate::allows($plan->plan_date->toDateString()));

        // Pekerjaan berubah setelah disetujui -> approval dibatalkan.
        $task->update(['status' => 'Done']);

        $plan->refresh();

        $this->assertSame(HousekeepingPlan::APPROVAL_PENDING, $plan->approval_status);
        $this->assertNull($plan->approved_at);
        $this->assertFalse(HousekeepingReportGate::allows($plan->plan_date->toDateString()));
    }

    public function test_no_notification_when_plan_is_created(): void
    {
        $this->makePlan();

        $this->assertSame(0, $this->spv->notifications()->count());
    }

    public function test_spv_gos_is_notified_when_work_is_completed(): void
    {
        $plan = $this->makePlan();

        $this->completePlan($plan);

        $this->assertSame(1, $this->spv->notifications()->count());
    }

    public function test_area_responsible_petugas_is_not_the_report_verifier(): void
    {
        $responsible = User::factory()->create(['status' => true]);
        $responsible->assignRole('patra-jasa');

        $this->area->update(['supervisor_id' => $responsible->id]);

        $this->completePlan($this->makePlan());

        // Penanggung jawab area adalah petugas patra-jasa; verifikasi tetap
        // milik SPV GOS sehingga ia tidak menerima notifikasi verifikasi.
        $this->assertSame(0, $responsible->notifications()->count());
        $this->assertSame(1, $this->spv->notifications()->count());
    }

    public function test_report_is_locked_while_completed_work_awaits_verification(): void
    {
        $plan = $this->makePlan();
        $this->completePlan($plan);

        $inspection = HousekeepingReportGate::inspect($plan->plan_date->toDateString());

        $this->assertFalse($inspection['allowed']);
        $this->assertCount(1, $inspection['pending']);
        $this->assertStringContainsString('belum direview/disetujui SPV GOS', HousekeepingReportGate::message($inspection));
    }

    public function test_approval_actions_hidden_before_work_started(): void
    {
        $this->actingAs($this->spv);

        $plan = $this->makePlan();

        Livewire::test(ViewHousekeepingPlan::class, ['record' => $plan->getKey()])
            ->assertActionHidden('approvePlan')
            ->assertActionHidden('rejectPlan');

        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Sapu',
            'status' => 'In Progress',
        ]);

        Livewire::test(ViewHousekeepingPlan::class, ['record' => $plan->getKey()])
            ->assertActionVisible('approvePlan')
            ->assertActionVisible('rejectPlan');
    }

    public function test_spv_can_approve_plan_and_unlock_report(): void
    {
        $this->actingAs($this->spv);

        $plan = $this->makePlan();
        $this->completePlan($plan);

        Livewire::test(ViewHousekeepingPlan::class, ['record' => $plan->getKey()])
            ->callAction('approvePlan', data: ['approval_notes' => 'Sudah sesuai'])
            ->assertHasNoActionErrors();

        $plan->refresh();

        $this->assertSame(HousekeepingPlan::APPROVAL_APPROVED, $plan->approval_status);
        $this->assertSame($this->spv->id, $plan->approved_by);
        $this->assertNotNull($plan->approved_at);
        $this->assertSame('Sudah sesuai', $plan->approval_notes);
        $this->assertTrue(HousekeepingReportGate::allows($plan->plan_date->toDateString()));
    }

    public function test_spv_can_reject_and_laporan_stays_locked(): void
    {
        $this->actingAs($this->spv);

        $plan = $this->makePlan();
        $this->completePlan($plan);

        Livewire::test(ViewHousekeepingPlan::class, ['record' => $plan->getKey()])
            ->callAction('rejectPlan', data: ['approval_notes' => 'Foto belum lengkap'])
            ->assertHasNoActionErrors();

        $plan->refresh();

        $this->assertSame(HousekeepingPlan::APPROVAL_REJECTED, $plan->approval_status);

        $inspection = HousekeepingReportGate::inspect($plan->plan_date->toDateString());

        $this->assertFalse($inspection['allowed']);
        $this->assertCount(1, $inspection['rejected']);
        $this->assertStringContainsString('ditolak SPV GOS', HousekeepingReportGate::message($inspection));
    }

    public function test_petugas_cannot_decide_approval(): void
    {
        $this->actingAs($this->petugas);

        $plan = $this->makePlan();

        Livewire::test(ViewHousekeepingPlan::class, ['record' => $plan->getKey()])
            ->assertActionHidden('approvePlan')
            ->assertActionHidden('rejectPlan');
    }

    public function test_list_page_exposes_approval_actions_for_spv(): void
    {
        $this->actingAs($this->spv);

        $plan = $this->makePlan();
        $this->completePlan($plan);

        Livewire::test(ListHousekeepingPlans::class)
            ->assertOk()
            ->assertTableActionVisible('approvePlan', $plan)
            ->assertTableActionVisible('rejectPlan', $plan)
            ->callTableAction('approvePlan', $plan, data: ['approval_notes' => 'OK']);

        $this->assertSame(HousekeepingPlan::APPROVAL_APPROVED, $plan->fresh()->approval_status);
    }

    public function test_report_blocked_by_unreviewed_plan_from_another_date(): void
    {
        // Plan lain yang masih berjalan / belum direview SPV GOS juga menahan
        // laporan karena pekerjaannya ikut tampil di laporan.
        $other = $this->makePlan('2026-11-10');
        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $other->id,
            'name' => 'Masih Jalan',
            'status' => 'In Progress',
        ]);

        $inspection = HousekeepingReportGate::inspect('2026-11-18');

        $this->assertFalse($inspection['allowed']);
        $this->assertCount(1, $inspection['pending']);
    }

    public function test_gate_allows_date_without_any_plan(): void
    {
        $this->assertTrue(HousekeepingReportGate::allows('2026-12-01'));
    }
}
