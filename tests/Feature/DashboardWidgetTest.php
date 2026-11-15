<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\CompanyAreaMapWidget;
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
