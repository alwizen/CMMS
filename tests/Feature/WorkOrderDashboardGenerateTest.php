<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\WorkOrders\Pages\ViewWorkOrder;
use App\Models\Activity;
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

class WorkOrderDashboardGenerateTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();

        $this->user = User::factory()->create();
        $company = Company::create(['code' => 'CO', 'name' => 'Demo Company']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'AR', 'name' => 'Demo Area']);
        $type = EquipmentType::create([
            'code' => 'mov',
            'name' => 'MOV',
            'responsible_user_id' => $this->user->id,
        ]);

        $this->equipment = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $type->id,
            'tag_number' => 'MOV-1',
        ]);

        Activity::create([
            'equipment_type_id' => $type->id,
            'name' => 'Cek',
            'type' => 'maintenance',
            'maintenance_classification' => 'preventive',
            'interval' => 'monthly',
            'answer_type' => 'qualitative',
            'status' => true,
        ]);
    }

    public function test_dashboard_renders_generate_actions(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->user);

        Livewire::test(Dashboard::class)
            ->assertOk()
            ->assertActionExists('generateWorkOrders');
    }

    public function test_generate_wo_action_creates_pending_work_orders(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->user);

        Livewire::test(Dashboard::class)
            ->callAction('generateWorkOrders', data: [
                'equipment_type_id' => $this->equipment->equipment_type_id,
                'equipment_ids' => [$this->equipment->id],
                'interval' => 'monthly',
                'quantity' => 2,
                'start_time' => '08:00',
                'issued_by' => $this->user->id,
                'skip_weekend' => true,
            ])
            ->assertHasNoActionErrors();

        $this->assertSame(2, WorkOrder::count());
        $this->assertSame(2, WorkOrder::where('status', WorkOrder::STATUSES['Pending'])->count());
    }

    public function test_generate_wo_shows_warning_when_no_checklist_matches_interval(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->user);

        Livewire::test(Dashboard::class)
            ->callAction('generateWorkOrders', data: [
                'equipment_type_id' => $this->equipment->equipment_type_id,
                'equipment_ids' => [$this->equipment->id],
                'interval' => 'yearly',
                'quantity' => 2,
                'start_time' => '08:00',
                'issued_by' => $this->user->id,
                'skip_weekend' => true,
            ])
            ->assertNotified('Tidak ada Work Order yang dibuat');

        $this->assertSame(0, WorkOrder::count());
    }

    public function test_view_work_order_page_renders(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->user);

        $workOrder = WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->user->id,
            'technician_coordinator_id' => $this->user->id,
            'classification' => 'Preventive',
            'start_at' => '2026-11-16 08:00:00',
            'status' => 'Pending',
        ]);

        Livewire::test(ViewWorkOrder::class, ['record' => $workOrder->getKey()])
            ->assertOk()
            ->assertSee($workOrder->work_order_number);
    }
}
