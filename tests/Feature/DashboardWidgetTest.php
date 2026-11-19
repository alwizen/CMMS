<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\HousekeepingDashboard;
use App\Filament\Widgets\CompanyAreaMapWidget;
use App\Filament\Widgets\MaintenanceKpiRankingWidget;
use App\Filament\Widgets\RecentWorkOrdersWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\WorkOrderStatusChart;
use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();

        config(['app.env' => 'local']);
        Gate::before(fn (): bool => true);

        $this->user = User::factory()->create();

        $company = Company::create(['code' => 'CO', 'name' => 'Demo Company']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'AR', 'name' => 'Demo Area']);
        $type = EquipmentType::create(['code' => 'MOV', 'name' => 'MOV']);

        $this->equipment = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $type->id,
            'tag_number' => 'MOV-TGL-502B',
        ]);
    }

    public function test_dashboard_page_renders_and_registers_expected_widgets(): void
    {
        $this->actingAs($this->user)
            ->get('/admin')
            ->assertSuccessful();

        $widgets = (new Dashboard)->getWidgets();

        $this->assertContains(CompanyAreaMapWidget::class, $widgets);
        $this->assertContains(WorkOrderStatusChart::class, $widgets);
    }

    public function test_dashboards_are_grouped_under_their_modules(): void
    {
        $this->assertSame('Maintenance', Dashboard::getNavigationGroup());
        $this->assertSame('Maintenance Dashboard', Dashboard::getNavigationLabel());

        $this->assertSame('Housekeeping', HousekeepingDashboard::getNavigationGroup());
        $this->assertSame('Housekeeping Dashboard', HousekeepingDashboard::getNavigationLabel());
    }

    public function test_map_widget_is_ordered_before_recent_work_orders(): void
    {
        $widgets = (new Dashboard)->getWidgets();

        $mapIndex = array_search(CompanyAreaMapWidget::class, $widgets, true);
        $recentIndex = array_search(RecentWorkOrdersWidget::class, $widgets, true);

        $this->assertNotFalse($mapIndex);
        $this->assertNotFalse($recentIndex);
        $this->assertSame($recentIndex - 1, $mapIndex, 'Peta harus tepat sebelum widget WO Terbaru.');
    }

    public function test_stats_widget_shows_total_equipment_type(): void
    {
        $this->actingAs($this->user);

        Livewire::test(StatsOverviewWidget::class)
            ->assertOk()
            ->assertSee('Total Equipment Type');
    }

    public function test_total_equipment_type_follows_fungsi_filter(): void
    {
        EquipmentType::create(['code' => 'HSSE-1', 'name' => 'Panel HSSE', 'fungsi' => 'HSSE', 'status' => true]);

        $this->actingAs($this->user);

        $component = Livewire::test(StatsOverviewWidget::class, [
            'pageFilters' => ['fungsi' => 'HSSE'],
        ])->assertOk();

        $method = new \ReflectionMethod($component->instance(), 'getStats');
        $method->setAccessible(true);

        $stats = collect($method->invoke($component->instance()));

        $stat = $stats->first(fn ($stat): bool => $stat->getLabel() === 'Total Equipment Type');

        $this->assertNotNull($stat);
        $this->assertSame(1, (int) $stat->getValue());
    }

    public function test_stats_widget_counts_overdue_work_orders(): void
    {
        WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->user->id,
            'classification' => 'Preventive',
            'start_at' => now(),
            'status' => 'Completed',
        ]);

        WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->user->id,
            'classification' => 'Corrective',
            'start_at' => now()->subDays(2),
            'status' => 'Pending',
        ]);

        $this->actingAs($this->user);

        Livewire::test(StatsOverviewWidget::class)
            ->assertOk()
            ->assertSee('WO Terlambat')
            ->assertSee('50% selesai');
    }

    public function test_stats_widget_respects_dashboard_date_filter(): void
    {
        $today = WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->user->id,
            'classification' => 'Preventive',
            'start_at' => now(),
            'status' => 'Completed',
        ]);

        $old = WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->user->id,
            'classification' => 'Corrective',
            'start_at' => now()->subMonth(),
            'status' => 'Pending',
        ]);

        \DB::table('work_orders')->where('id', $old->id)->update([
            'created_at' => now()->subMonth(),
        ]);

        $this->actingAs($this->user);

        Livewire::test(StatsOverviewWidget::class, [
            'pageFilters' => ['dateRange' => 'today'],
        ])
            ->assertOk()
            ->assertSee('100% selesai');
    }

    public function test_stats_widget_updates_when_dashboard_filter_event_fires(): void
    {
        WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->user->id,
            'classification' => 'Preventive',
            'start_at' => now(),
            'status' => 'Completed',
        ]);

        $old = WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->user->id,
            'classification' => 'Corrective',
            'start_at' => now()->subMonth(),
            'status' => 'Pending',
        ]);

        \DB::table('work_orders')->where('id', $old->id)->update([
            'created_at' => now()->subMonth(),
        ]);

        $this->actingAs($this->user);

        Livewire::test(StatsOverviewWidget::class)
            ->assertSee('50% selesai')
            ->dispatch('dashboard-filters-updated', filters: ['dateRange' => 'today'])
            ->assertSee('100% selesai');
    }

    public function test_dashboard_filters_are_not_persisted_between_requests(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(Dashboard::class)
            ->set('filters', ['dateRange' => 'today', 'fungsi' => 'MOS LTSA'])
            ->assertSet('filters.dateRange', 'today');

        // Instance baru (simulasi refresh/navigasi) harus kembali ke default.
        Livewire::test(Dashboard::class)
            ->assertSet('filters', null)
            ->assertSet('filters.dateRange', null);

        $this->assertFalse($component->instance()->persistsFiltersInSession());
    }

    public function test_dashboard_filter_property_is_not_bound_to_url(): void
    {
        $property = new \ReflectionProperty(Dashboard::class, 'filters');

        $this->assertSame(
            [],
            $property->getAttributes(Url::class),
            'Filter tidak boleh di-bind ke query string.',
        );
    }

    public function test_filter_action_applies_filters_to_dashboard(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Dashboard::class)
            ->callAction('filter', data: [
                'dateRange' => 'today',
                'fungsi' => 'MOS LTSA',
            ])
            ->assertHasNoActionErrors()
            ->assertSet('filters.dateRange', 'today')
            ->assertSet('filters.fungsi', 'MOS LTSA');
    }

    public function test_maintenance_dashboard_can_reset_global_filter(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Dashboard::class)
            ->assertActionExists('resetDashboardFilters')
            ->set('filters', ['dateRange' => 'today', 'fungsi' => 'MOS LTSA', 'user_id' => $this->user->id])
            ->callAction('resetDashboardFilters')
            ->assertSet('filters', []);
    }

    public function test_housekeeping_dashboard_can_reset_global_filter(): void
    {
        $this->actingAs($this->user);

        Livewire::test(HousekeepingDashboard::class)
            ->assertActionExists('resetDashboardFilters')
            ->set('filters', ['dateRange' => 'today'])
            ->callAction('resetDashboardFilters')
            ->assertSet('filters', []);
    }

    public function test_stats_widget_respects_fungsi_filter(): void
    {
        $ltsa = User::factory()->create(['name' => 'Teknisi LTSA', 'fungsi' => 'MOS LTSA']);
        $hsse = User::factory()->create(['name' => 'Teknisi HSSE', 'fungsi' => 'HSSE']);

        WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->user->id,
            'technician_coordinator_id' => $ltsa->id,
            'classification' => 'Preventive',
            'start_at' => now(),
            'status' => 'Completed',
        ]);

        WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->user->id,
            'technician_coordinator_id' => $hsse->id,
            'classification' => 'Corrective',
            'start_at' => now(),
            'status' => 'Pending',
        ]);

        $this->actingAs($this->user);

        Livewire::test(StatsOverviewWidget::class, [
            'pageFilters' => ['fungsi' => 'MOS LTSA'],
        ])
            ->assertOk()
            ->assertSee('100% selesai');
    }

    public function test_kpi_ranking_widget_lists_technicians_with_context(): void
    {
        $ltsa = User::factory()->create(['name' => 'Teknisi LTSA', 'fungsi' => 'MOS LTSA']);

        WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->user->id,
            'technician_coordinator_id' => $ltsa->id,
            'classification' => 'Preventive',
            'start_at' => now(),
            'finish_at' => now(),
            'status' => 'Completed',
        ]);

        $this->actingAs($this->user);

        Livewire::test(MaintenanceKpiRankingWidget::class, [
            'pageFilters' => ['dateRange' => 'all'],
        ])
            ->assertOk()
            ->assertSee('Teknisi LTSA')
            ->assertSee('MOS LTSA')
            ->assertSee('Peringkat KPI Maintenance');
    }

    public function test_kpi_ranking_widget_respects_fungsi_filter(): void
    {
        $ltsa = User::factory()->create(['name' => 'Teknisi LTSA', 'fungsi' => 'MOS LTSA']);
        $hsse = User::factory()->create(['name' => 'Teknisi HSSE', 'fungsi' => 'HSSE']);

        foreach ([$ltsa, $hsse] as $user) {
            WorkOrder::create([
                'equipment_id' => $this->equipment->id,
                'issued_by' => $this->user->id,
                'technician_coordinator_id' => $user->id,
                'classification' => 'Preventive',
                'start_at' => now(),
                'finish_at' => now(),
                'status' => 'Completed',
            ]);
        }

        $this->actingAs($this->user);

        Livewire::test(MaintenanceKpiRankingWidget::class, [
            'pageFilters' => ['dateRange' => 'all', 'fungsi' => 'HSSE'],
        ])
            ->assertOk()
            ->assertSee('Teknisi HSSE')
            ->assertDontSee('Teknisi LTSA');
    }

    public function test_stats_widget_respects_user_filter(): void
    {
        $teknisiA = User::factory()->create(['name' => 'Teknisi A']);
        $teknisiB = User::factory()->create(['name' => 'Teknisi B']);

        WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->user->id,
            'technician_coordinator_id' => $teknisiA->id,
            'classification' => 'Preventive',
            'start_at' => now(),
            'status' => 'Completed',
        ]);

        WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->user->id,
            'technician_coordinator_id' => $teknisiB->id,
            'classification' => 'Corrective',
            'start_at' => now(),
            'status' => 'Pending',
        ]);

        $this->actingAs($this->user);

        // Tanpa filter user: 1 dari 2 selesai.
        Livewire::test(StatsOverviewWidget::class)
            ->assertOk()
            ->assertSee('50% selesai');

        // Filter Teknisi A: semua WO-nya selesai.
        Livewire::test(StatsOverviewWidget::class, [
            'pageFilters' => ['user_id' => $teknisiA->id],
        ])
            ->assertOk()
            ->assertSee('100% selesai')
            ->assertSee('WO Belum Selesai');
    }

    public function test_recent_work_orders_widget_respects_user_filter(): void
    {
        $teknisiA = User::factory()->create(['name' => 'Teknisi A']);
        $teknisiB = User::factory()->create(['name' => 'Teknisi B']);

        WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->user->id,
            'technician_coordinator_id' => $teknisiA->id,
            'classification' => 'Preventive',
            'start_at' => now(),
            'status' => 'Completed',
        ]);

        $woB = WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->user->id,
            'technician_coordinator_id' => $teknisiB->id,
            'classification' => 'Corrective',
            'start_at' => now(),
            'status' => 'Pending',
        ]);

        $this->actingAs($this->user);

        Livewire::test(RecentWorkOrdersWidget::class, [
            'pageFilters' => ['user_id' => $teknisiA->id],
        ])
            ->assertOk()
            ->assertDontSee($woB->work_order_number);
    }

    public function test_work_order_status_chart_shows_all_statuses(): void
    {
        WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->user->id,
            'classification' => 'Preventive',
            'start_at' => now(),
            'status' => 'On Hold',
        ]);

        $this->actingAs($this->user);

        Livewire::test(WorkOrderStatusChart::class)
            ->assertOk()
            ->assertSee('On Hold');
    }
}
