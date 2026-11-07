<?php

namespace Tests\Feature;

use App\Filament\Pages\DowntimeReport;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\MaintenanceHistory;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Models\WorkOrder;
use App\Support\Downtime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class DowntimeTest extends TestCase
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
        $type = EquipmentType::create(['code' => 'pompa', 'name' => 'Pompa']);

        $this->equipment = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $type->id,
            'tag_number' => 'P-101',
        ]);
    }

    protected function makeRequest(array $attributes = []): MaintenanceRequest
    {
        return MaintenanceRequest::create(array_merge([
            'equipment_id' => $this->equipment->id,
            'reported_by' => $this->user->id,
            'operation_status' => 'Stopped',
            'description' => 'Pompa berhenti beroperasi',
            'damage_date' => today()->subDay()->toDateString(),
            'damage_time' => '08:00:00',
            'status' => MaintenanceRequest::STATUSES['In Progress'],
        ], $attributes));
    }

    protected function makeWorkOrder(MaintenanceRequest $request, array $attributes = []): WorkOrder
    {
        return WorkOrder::create(array_merge([
            'equipment_id' => $this->equipment->id,
            'maintenance_request_id' => $request->id,
            'issued_by' => $this->user->id,
            'classification' => 'Corrective',
            'start_at' => today()->setTime(9, 0),
            'finish_at' => today()->setTime(10, 0),
            'status' => WorkOrder::STATUSES['Completed'],
        ], $attributes));
    }

    public function test_request_downtime_is_calculated_from_damage_to_work_order_finish(): void
    {
        $request = $this->makeRequest();
        $this->makeWorkOrder($request);

        $request->refresh()->load('workOrders');

        $this->assertTrue($request->isUnplannedDowntime());
        $this->assertSame(today()->subDay()->setTime(8, 0)->toDateTimeString(), $request->downtimeStart()->toDateTimeString());
        $this->assertSame(26 * 60, $request->downtimeMinutes());
        $this->assertFalse($request->downtimeOngoing());
    }

    public function test_damage_time_falls_back_to_start_of_day(): void
    {
        $request = $this->makeRequest(['damage_time' => null]);
        $this->makeWorkOrder($request);

        $request->refresh()->load('workOrders');

        $this->assertSame(today()->subDay()->startOfDay()->toDateTimeString(), $request->downtimeStart()->toDateTimeString());
    }

    public function test_ongoing_request_uses_current_time_as_downtime_end(): void
    {
        $request = $this->makeRequest(['status' => MaintenanceRequest::STATUSES['Approved']]);

        $request->refresh()->load('workOrders');

        $this->assertTrue($request->downtimeOngoing());
        $this->assertNotNull($request->downtimeEnd());
        $this->assertGreaterThan(0, $request->downtimeMinutes());
    }

    public function test_preventive_request_or_work_order_has_no_downtime(): void
    {
        $request = $this->makeRequest();
        $this->makeWorkOrder($request, ['classification' => 'Preventive']);

        $request->refresh()->load('workOrders');

        $this->assertFalse($request->isUnplannedDowntime());
        $this->assertNull($request->downtimeMinutes());
    }

    public function test_maintenance_history_downtime_only_applies_to_corrective_from_request(): void
    {
        $request = $this->makeRequest();
        $workOrder = $this->makeWorkOrder($request);

        $history = MaintenanceHistory::create([
            'equipment_id' => $this->equipment->id,
            'work_order_id' => $workOrder->id,
            'work_order_number' => $workOrder->work_order_number,
            'maintenance_type' => 'Corrective',
            'classification' => 'Corrective',
            'description' => 'Perbaikan pompa',
            'maintenance_date' => today()->setTime(10, 0),
            'performed_by' => $this->user->id,
            'status' => 'Completed',
        ]);

        $history->load('workOrder.maintenanceRequest');

        $this->assertSame(26 * 60, $history->downtimeMinutes());
        $this->assertSame('26 jam', Downtime::formatMinutes($history->downtimeMinutes()));

        $preventive = MaintenanceHistory::create([
            'equipment_id' => $this->equipment->id,
            'maintenance_type' => 'Preventive',
            'classification' => 'Preventive',
            'description' => 'Perawatan rutin',
            'maintenance_date' => today(),
            'performed_by' => $this->user->id,
            'status' => 'Completed',
        ]);

        $this->assertNull($preventive->downtimeMinutes());
    }

    public function test_downtime_page_renders_with_summary_and_table(): void
    {
        $request = $this->makeRequest(['description' => 'Kebocoran seal pompa']);
        $this->makeWorkOrder($request);

        $this->actingAs($this->user);

        Livewire::test(DowntimeReport::class)
            ->assertOk()
            ->assertSee('Total Downtime')
            ->assertSee('Kebocoran seal pompa')
            ->assertSee('26,0 jam')
            ->assertSee('26 jam');
    }

    public function test_dashboard_shows_unplanned_downtime_stat(): void
    {
        $request = $this->makeRequest();
        $this->makeWorkOrder($request);

        $this->actingAs($this->user);

        Livewire::test(StatsOverviewWidget::class)
            ->assertSee('Unplanned Downtime')
            ->assertSee('26,0 jam');
    }
}
