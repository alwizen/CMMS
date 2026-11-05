<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\CompanyAreaMapWidget;
use App\Filament\Widgets\DueScheduleWidget;
use App\Filament\Widgets\EquipmentByLocationChart;
use App\Filament\Widgets\EquipmentHealthWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\WorkOrderStatusChart;
use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\MaintenancePlan;
use App\Models\MaintenanceSchedule;
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
        $this->assertContains(DueScheduleWidget::class, $widgets);
        $this->assertContains(WorkOrderStatusChart::class, $widgets);
        $this->assertNotContains(EquipmentHealthWidget::class, $widgets);
        $this->assertNotContains(EquipmentByLocationChart::class, $widgets);
    }

    public function test_due_schedule_widget_lists_overdue_and_upcoming_only(): void
    {
        $plan = $this->makePlan();

        $overdue = $this->makeSchedule($plan, today()->subDays(3)->toDateString(), MaintenanceSchedule::STATUSES['Scheduled']);
        $upcoming = $this->makeSchedule($plan, today()->addDays(3)->toDateString(), MaintenanceSchedule::STATUSES['Scheduled']);
        $farAway = $this->makeSchedule($plan, today()->addDays(30)->toDateString(), MaintenanceSchedule::STATUSES['Scheduled']);
        $done = $this->makeSchedule($plan, today()->subDays(10)->toDateString(), MaintenanceSchedule::STATUSES['Completed']);

        $this->actingAs($this->user);

        Livewire::test(DueScheduleWidget::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$overdue, $upcoming])
            ->assertCanNotSeeTableRecords([$farAway, $done])
            ->assertSee('3 hari terlambat');
    }

    public function test_stats_widget_counts_overdue_schedules(): void
    {
        $plan = $this->makePlan();
        $this->makeSchedule($plan, today()->subDays(2)->toDateString(), MaintenanceSchedule::STATUSES['Scheduled']);
        $this->makeSchedule($plan, today()->subDays(1)->toDateString(), MaintenanceSchedule::STATUSES['Delayed']);
        $this->makeSchedule($plan, today()->addDays(5)->toDateString(), MaintenanceSchedule::STATUSES['Scheduled']);

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
            'start_at' => now(),
            'status' => 'Pending',
        ]);

        $this->actingAs($this->user);

        Livewire::test(StatsOverviewWidget::class)
            ->assertOk()
            ->assertSee('Jadwal Terlambat')
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

    protected function makePlan(): MaintenancePlan
    {
        return MaintenancePlan::create([
            'equipment_id' => $this->equipment->id,
            'maintenance_classification' => 'Preventive',
            'interval' => 'monthly',
            'start_date' => today()->subMonth()->toDateString(),
            'created_by' => $this->user->id,
            'status' => 'Active',
        ]);
    }

    protected function makeSchedule(MaintenancePlan $plan, string $date, string $status): MaintenanceSchedule
    {
        return MaintenanceSchedule::create([
            'maintenance_plan_id' => $plan->id,
            'equipment_id' => $this->equipment->id,
            'scheduled_date' => $date,
            'status' => $status,
        ]);
    }
}
