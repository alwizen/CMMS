<?php

namespace Tests\Feature;

use App\Filament\Pages\KpiDashboard;
use App\Filament\Resources\KpiSettings\Pages\ListKpiSettings;
use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingPlan;
use App\Models\HousekeepingPlanFacility;
use App\Models\HousekeepingPlanTask;
use App\Models\KpiMetric;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderActivity;
use App\Support\Kpi\KpiPeriod;
use App\Support\Kpi\KpiRegistry;
use App\Support\Kpi\KpiResult;
use App\Support\Kpi\KpiScorer;
use App\Support\Kpi\KpiService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class KpiTest extends TestCase
{
    use RefreshDatabase;

    protected User $technician;

    protected Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();
        Carbon::setTestNow(Carbon::parse('2026-11-16 10:00:00'));

        $this->technician = User::factory()->create(['name' => 'Teknisi A']);

        $company = Company::create(['code' => 'CO', 'name' => 'Demo']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'AR', 'name' => 'Area']);
        $type = EquipmentType::create(['code' => 'pump', 'name' => 'Pompa']);

        $this->equipment = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $type->id,
            'tag_number' => 'P-101',
        ]);
    }

    protected function makeWorkOrder(array $attributes = []): WorkOrder
    {
        return WorkOrder::create(array_merge([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->technician->id,
            'technician_coordinator_id' => $this->technician->id,
            'classification' => 'Preventive',
            'start_at' => '2026-11-10 08:00:00',
            'finish_at' => '2026-11-10 10:00:00',
            'status' => WorkOrder::STATUSES['Completed'],
        ], $attributes));
    }

    protected function currentPeriod(): KpiPeriod
    {
        return KpiPeriod::fromPreset('custom', '2026-11-01', '2026-11-30');
    }

    public function test_scorer_higher_and_lower_direction(): void
    {
        $this->assertSame(100.0, KpiScorer::metricScore(90, 90, 'higher'));
        $this->assertSame(50.0, KpiScorer::metricScore(45, 90, 'higher'));
        $this->assertSame(100.0, KpiScorer::metricScore(100, 90, 'higher'), 'Skor dibatasi maksimal 100');
        $this->assertSame(100.0, KpiScorer::metricScore(0, 5, 'lower'));
        $this->assertSame(50.0, KpiScorer::metricScore(10, 5, 'lower'));
    }

    public function test_maintenance_metrics_calculated_per_technician(): void
    {
        $this->makeWorkOrder();

        $late = $this->makeWorkOrder([
            'start_at' => '2026-11-11 08:00:00',
            'finish_at' => '2026-11-13 17:00:00',
        ]);
        WorkOrderActivity::create([
            'work_order_id' => $late->id,
            'reference' => 'Cek',
            'executed' => true,
        ]);
        WorkOrderActivity::create([
            'work_order_id' => $late->id,
            'reference' => 'Cek 2',
            'executed' => false,
        ]);

        $result = app(KpiService::class)
            ->results(KpiRegistry::SOURCE_MAINTENANCE, $this->currentPeriod())
            ->first();

        $this->assertInstanceOf(KpiResult::class, $result);
        $this->assertSame('Teknisi A', $result->objectName);
        $this->assertSame(100, (int) $result->metrics['wo_completion_rate']['value']);
        $this->assertSame(50, (int) $result->metrics['wo_on_time_rate']['value']);
        $this->assertSame(50, (int) $result->metrics['checklist_compliance']['value']);
        $this->assertSame(100, (int) $result->metrics['preventive_ratio']['value']);
        $this->assertTrue($result->metrics['wo_on_time_rate']['achieved'] === false);
    }

    public function test_cancelled_work_orders_excluded_from_completion_rate(): void
    {
        $this->makeWorkOrder();
        $this->makeWorkOrder(['status' => WorkOrder::STATUSES['Cancelled']]);

        $result = app(KpiService::class)
            ->results(KpiRegistry::SOURCE_MAINTENANCE, $this->currentPeriod())
            ->first();

        $this->assertSame(100, (int) $result->metrics['wo_completion_rate']['value']);
    }

    public function test_overdue_work_orders_penalize_score(): void
    {
        $this->makeWorkOrder();

        $clean = app(KpiService::class)
            ->results(KpiRegistry::SOURCE_MAINTENANCE, $this->currentPeriod())
            ->first();

        $this->makeWorkOrder([
            'start_at' => '2026-11-01 08:00:00',
            'finish_at' => null,
            'status' => WorkOrder::STATUSES['In Progress'],
        ]);

        $penalized = app(KpiService::class)
            ->results(KpiRegistry::SOURCE_MAINTENANCE, $this->currentPeriod())
            ->first();

        $this->assertSame(1, $penalized->metrics['overdue_open_wo']['value']);
        $this->assertLessThan($clean->score, $penalized->score);
    }

    public function test_fungsi_filter_only_includes_users_of_that_fungsi(): void
    {
        $this->technician->update(['fungsi' => 'MOS LTSA']);

        $other = User::factory()->create(['name' => 'Teknisi HSSE', 'fungsi' => 'HSSE']);

        $this->makeWorkOrder(['technician_coordinator_id' => $this->technician->id]);
        $this->makeWorkOrder(['technician_coordinator_id' => $other->id]);

        $all = app(KpiService::class)->results(KpiRegistry::SOURCE_MAINTENANCE, $this->currentPeriod());
        $this->assertCount(2, $all);

        $filtered = app(KpiService::class)->results(KpiRegistry::SOURCE_MAINTENANCE, $this->currentPeriod(), 'HSSE');
        $this->assertCount(1, $filtered);
        $this->assertSame('Teknisi HSSE', $filtered->first()->objectName);
        $this->assertSame('HSSE', $filtered->first()->context['fungsi']);
    }

    public function test_user_filter_only_includes_selected_user(): void
    {
        $this->technician->update(['fungsi' => 'MOS LTSA']);

        $other = User::factory()->create(['name' => 'Teknisi Lain', 'fungsi' => 'MOS LTSA']);

        $this->makeWorkOrder(['technician_coordinator_id' => $this->technician->id]);
        $this->makeWorkOrder(['technician_coordinator_id' => $other->id]);

        $filtered = app(KpiService::class)->results(
            KpiRegistry::SOURCE_MAINTENANCE,
            $this->currentPeriod(),
            null,
            $this->technician->id,
        );

        $this->assertCount(1, $filtered);
        $this->assertSame('Teknisi A', $filtered->first()->objectName);
    }

    public function test_kpi_dashboard_has_user_filter(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->technician);

        $this->technician->update(['fungsi' => 'MOS LTSA']);
        $this->makeWorkOrder();

        Livewire::test(KpiDashboard::class)
            ->assertOk()
            ->assertFormFieldExists('user_id', 'filterSchema')
            ->set('draft.user_id', $this->technician->id)
            ->call('applyFilters')
            ->assertOk()
            ->assertSee('Teknisi A');
    }

    public function test_filters_only_apply_after_apply_button_pressed(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->technician);

        $this->technician->update(['fungsi' => 'MOS LTSA']);
        $other = User::factory()->create(['name' => 'Teknisi Lain', 'fungsi' => 'MOS LTSA']);

        $this->makeWorkOrder(['technician_coordinator_id' => $this->technician->id]);
        $this->makeWorkOrder(['technician_coordinator_id' => $other->id]);

        $component = Livewire::test(KpiDashboard::class)->assertOk();

        // Sebelum diterapkan: kedua teknisi masih dinilai.
        $this->assertCount(2, $component->instance()->results());

        // Ubah draft saja (belum tekan Terapkan): hasil belum berubah.
        $component->set('draft.user_id', $this->technician->id);
        $this->assertCount(2, $component->instance()->results());

        // Tekan Terapkan: hasil mengikuti filter.
        $component->call('applyFilters');
        $names = $component->instance()->results()->pluck('objectName')->all();
        $this->assertSame(['Teknisi A'], $names);
    }

    public function test_reset_filters_restores_default_draft(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->technician);

        Livewire::test(KpiDashboard::class)
            ->assertOk()
            ->set('draft.period', 'last_month')
            ->call('resetFilters')
            ->assertSet('draft.period', 'this_month');
    }

    public function test_expired_work_order_metric_counts_open_expired(): void
    {
        $this->makeWorkOrder([
            'start_at' => '2026-11-01 08:00:00',
            'finish_at' => null,
            'status' => WorkOrder::STATUSES['In Progress'],
            'expired_days' => 7,
            'expired_at' => '2026-11-08 08:00:00',
        ]);

        $result = app(KpiService::class)
            ->results(KpiRegistry::SOURCE_MAINTENANCE, $this->currentPeriod())
            ->first();

        $this->assertSame(1, $result->metrics['expired_wo']['value']);
        $this->assertSame(1, $result->context['expired_wo']);
        $this->assertSame(1, $result->context['open_wo']);
    }

    public function test_completed_work_order_is_not_counted_as_expired(): void
    {
        $this->makeWorkOrder([
            'start_at' => '2026-11-01 08:00:00',
            'finish_at' => '2026-11-02 08:00:00',
            'status' => WorkOrder::STATUSES['Completed'],
            'expired_days' => 1,
            'expired_at' => '2026-11-02 08:00:00',
        ]);

        $result = app(KpiService::class)
            ->results(KpiRegistry::SOURCE_MAINTENANCE, $this->currentPeriod())
            ->first();

        $this->assertSame(0, $result->context['expired_wo']);
        $this->assertSame(1, $result->context['completed_wo']);
        $this->assertSame(0, $result->context['open_wo']);
    }

    public function test_housekeeping_metrics_calculated_per_area(): void
    {
        $area = HousekeepingArea::create([
            'company_id' => $this->equipment->area->company_id,
            'code' => 'taman',
            'name' => 'Taman Kantor',
            'kpi_class' => 'indoor',
            'is_active' => true,
        ]);

        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $area->id,
            'plan_date' => '2026-11-10',
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

        HousekeepingPlanFacility::create([
            'housekeeping_plan_id' => $plan->id,
            'photo_after' => ['housekeeping/plan-facilities/after/a.jpg'],
        ]);

        $result = app(KpiService::class)
            ->results(KpiRegistry::SOURCE_HOUSEKEEPING_INDOOR, $this->currentPeriod())
            ->first();

        $this->assertSame('Taman Kantor', $result->objectName);
        $this->assertSame(50, (int) $result->metrics['task_completion_rate']['value']);
        $this->assertSame(100, (int) $result->metrics['documentation_compliance']['value']);
        $this->assertSame(1, $result->metrics['backlog']['value']);
        // Satu plan pada periode 1-30 Nov -> 1/30 hari.
        $this->assertSame(3.33, (float) $result->metrics['plan_consistency']['value']);
    }

    public function test_housekeeping_sla_compliance_and_efficiency(): void
    {
        $area = HousekeepingArea::create([
            'company_id' => $this->equipment->area->company_id,
            'code' => 'kantor',
            'name' => 'Kantor',
            'sla_days' => 1,
            'kpi_class' => 'indoor',
            'is_active' => true,
        ]);

        $onTime = HousekeepingPlan::create([
            'housekeeping_area_id' => $area->id,
            'plan_date' => '2026-11-10',
            'status' => 'Completed',
        ]);
        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $onTime->id,
            'name' => 'Sapu',
            'status' => 'Done',
            'started_at' => '2026-11-10 08:00:00',
            'completed_at' => '2026-11-10 09:00:00',
        ]);

        $late = HousekeepingPlan::create([
            'housekeeping_area_id' => $area->id,
            'plan_date' => '2026-11-11',
            'status' => 'Completed',
        ]);
        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $late->id,
            'name' => 'Pel',
            'status' => 'Done',
            'started_at' => '2026-11-11 08:00:00',
            'completed_at' => '2026-11-14 09:00:00',
        ]);

        $result = app(KpiService::class)
            ->results(KpiRegistry::SOURCE_HOUSEKEEPING_INDOOR, $this->currentPeriod())
            ->first();

        $this->assertSame(50, (int) $result->metrics['sla_compliance']['value']);
    }

    public function test_indoor_area_not_scored_in_outdoor_source(): void
    {
        $indoor = HousekeepingArea::create([
            'company_id' => $this->equipment->area->company_id,
            'code' => 'dalam',
            'name' => 'Ruang Kontrol',
            'sla_days' => 1,
            'kpi_class' => 'indoor',
            'is_active' => true,
        ]);
        $outdoor = HousekeepingArea::create([
            'company_id' => $this->equipment->area->company_id,
            'code' => 'luar',
            'name' => 'Taman Luar',
            'sla_days' => 14,
            'kpi_class' => 'outdoor',
            'is_active' => true,
        ]);

        foreach ([$indoor, $outdoor] as $area) {
            $plan = HousekeepingPlan::create([
                'housekeeping_area_id' => $area->id,
                'plan_date' => '2026-11-10',
                'status' => 'Draft',
            ]);
            HousekeepingPlanTask::create([
                'housekeeping_plan_id' => $plan->id,
                'name' => 'Sapu',
                'status' => 'Pending',
            ]);
        }

        $indoorNames = app(KpiService::class)
            ->results(KpiRegistry::SOURCE_HOUSEKEEPING_INDOOR, $this->currentPeriod())
            ->pluck('objectName')
            ->all();

        $outdoorNames = app(KpiService::class)
            ->results(KpiRegistry::SOURCE_HOUSEKEEPING_OUTDOOR, $this->currentPeriod())
            ->pluck('objectName')
            ->all();

        $this->assertSame(['Ruang Kontrol'], $indoorNames);
        $this->assertSame(['Taman Luar'], $outdoorNames);
    }

    public function test_outdoor_sla_efficiency_scores_early_completion_equal_to_long_sla(): void
    {
        $area = HousekeepingArea::create([
            'company_id' => $this->equipment->area->company_id,
            'code' => 'luar',
            'name' => 'Area Luar',
            'sla_days' => 14,
            'kpi_class' => 'outdoor',
            'is_active' => true,
        ]);

        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $area->id,
            'plan_date' => '2026-11-01',
            'status' => 'Completed',
        ]);
        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Sapu',
            'status' => 'Done',
            'started_at' => '2026-11-01 08:00:00',
            'completed_at' => '2026-11-08 09:00:00',
        ]);

        $result = app(KpiService::class)
            ->results(KpiRegistry::SOURCE_HOUSEKEEPING_OUTDOOR, $this->currentPeriod())
            ->first();

        // Selesai hari ke-7 dari SLA 14 hari -> efisiensi 100%.
        $this->assertSame(100, (int) $result->metrics['sla_efficiency']['value']);
        $this->assertSame(100, (int) $result->metrics['sla_compliance']['value']);
        // Plan 1 Nov + SLA 14 hari mencakup 1-15 Nov -> 15/30 hari periode.
        $this->assertSame(50, (int) $result->metrics['window_coverage']['value']);
    }

    public function test_outdoor_window_coverage_does_not_double_count_overlapping_plans(): void
    {
        $area = HousekeepingArea::create([
            'company_id' => $this->equipment->area->company_id,
            'code' => 'luar',
            'name' => 'Area Luar',
            'sla_days' => 14,
            'kpi_class' => 'outdoor',
            'is_active' => true,
        ]);

        foreach (['2026-11-01', '2026-11-05'] as $date) {
            $plan = HousekeepingPlan::create([
                'housekeeping_area_id' => $area->id,
                'plan_date' => $date,
                'status' => 'Draft',
            ]);
            HousekeepingPlanTask::create([
                'housekeeping_plan_id' => $plan->id,
                'name' => 'Sapu',
                'status' => 'Pending',
            ]);
        }

        $result = app(KpiService::class)
            ->results(KpiRegistry::SOURCE_HOUSEKEEPING_OUTDOOR, $this->currentPeriod())
            ->first();

        // 1-15 Nov (plan 1) union 5-19 Nov (plan 5) = 1-19 Nov = 19 hari.
        $this->assertSame(63.33, (float) $result->metrics['window_coverage']['value']);
    }

    public function test_metric_settings_override_target_and_deactivate_metric(): void
    {
        KpiMetric::create([
            'source' => KpiRegistry::SOURCE_MAINTENANCE,
            'key' => 'wo_on_time_rate',
            'target_value' => 50,
            'weight' => 40,
            'is_active' => false,
        ]);

        $metrics = KpiRegistry::metrics(KpiRegistry::SOURCE_MAINTENANCE);

        $this->assertArrayNotHasKey('wo_on_time_rate', $metrics);
        $this->assertSame(30.0, (float) $metrics['wo_completion_rate']['weight']);
    }

    public function test_kpi_settings_page_seeds_and_lists_metrics(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->technician);

        Livewire::test(ListKpiSettings::class)
            ->assertOk()
            ->assertCanSeeTableRecords(KpiMetric::all());

        $expected = count(KpiRegistry::DEFINITIONS[KpiRegistry::SOURCE_MAINTENANCE])
            + count(KpiRegistry::DEFINITIONS[KpiRegistry::SOURCE_HOUSEKEEPING_INDOOR])
            + count(KpiRegistry::DEFINITIONS[KpiRegistry::SOURCE_HOUSEKEEPING_OUTDOOR]);

        $this->assertSame($expected, KpiMetric::count());
    }

    public function test_kpi_dashboard_period_follows_filter(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->technician);

        $this->makeWorkOrder();

        $component = Livewire::test(KpiDashboard::class)->assertOk();

        $component->set('draft.period', 'last_month')->call('applyFilters')->assertSee('Bulan Lalu');
        $component->set('draft.source', KpiRegistry::SOURCE_HOUSEKEEPING_INDOOR)->call('applyFilters')->assertSee('Plan Consistency');
    }

    public function test_kpi_dashboard_page_renders_results(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->technician);

        $this->makeWorkOrder();

        Livewire::test(KpiDashboard::class)
            ->assertOk()
            ->assertSee('Teknisi A')
            ->assertSee('WO Completion Rate')
            ->assertSee('Objek Dinilai')
            ->assertSee('Rata-rata Skor')
            ->assertSee('Distribusi Status')
            ->assertSee('tercapai');
    }
}
