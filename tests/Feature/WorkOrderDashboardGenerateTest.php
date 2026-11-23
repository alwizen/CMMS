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
use Spatie\Permission\Models\Role;
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

        $this->user = User::factory()->create(['fungsi' => 'MOS LTSA']);
        $this->user->assignRole(Role::findOrCreate('super_admin', 'web'));
        $company = Company::create(['code' => 'CO', 'name' => 'Demo Company']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'AR', 'name' => 'Demo Area']);
        $type = EquipmentType::create([
            'code' => 'mov',
            'name' => 'MOV',
            'fungsi' => 'MOS LTSA',
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
                'fungsi' => 'MOS LTSA',
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

    public function test_generate_wo_blocks_duplicate_in_same_period(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->user);

        $data = [
            'fungsi' => 'MOS LTSA',
            'equipment_type_id' => $this->equipment->equipment_type_id,
            'equipment_ids' => [$this->equipment->id],
            'interval' => 'monthly',
            'quantity' => 1,
            'start_date' => '2026-11-10',
            'start_time' => '08:00',
            'expired_days' => 7,
            'issued_by' => $this->user->id,
            'skip_weekend' => true,
        ];

        Livewire::test(Dashboard::class)
            ->callAction('generateWorkOrders', data: $data)
            ->assertHasNoActionErrors();

        $this->assertSame(1, WorkOrder::count());

        // Generate ulang pada bulan yang sama -> dilewati.
        Livewire::test(Dashboard::class)
            ->callAction('generateWorkOrders', data: $data)
            ->assertHasNoActionErrors()
            ->assertNotified('Tidak ada Work Order yang dibuat');

        $this->assertSame(1, WorkOrder::count());
    }

    public function test_generate_wo_allows_duplicate_when_enabled(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->user);

        $data = [
            'fungsi' => 'MOS LTSA',
            'equipment_type_id' => $this->equipment->equipment_type_id,
            'equipment_ids' => [$this->equipment->id],
            'interval' => 'monthly',
            'quantity' => 1,
            'start_date' => '2026-11-10',
            'start_time' => '08:00',
            'expired_days' => 7,
            'issued_by' => $this->user->id,
            'skip_weekend' => true,
            'allow_duplicate' => true,
        ];

        Livewire::test(Dashboard::class)->callAction('generateWorkOrders', data: $data);
        Livewire::test(Dashboard::class)->callAction('generateWorkOrders', data: $data);

        $this->assertSame(2, WorkOrder::count());
    }

    public function test_interval_options_follow_checklist_of_selected_equipment(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->user);

        Activity::create([
            'equipment_type_id' => $this->equipment->equipment_type_id,
            'name' => 'Cek Harian',
            'type' => 'inspection',
            'maintenance_classification' => 'preventive',
            'interval' => 'daily',
            'answer_type' => 'qualitative',
            'status' => true,
        ]);

        $component = Livewire::test(Dashboard::class);

        $this->assertSame([], $component->instance()->intervalOptions(null));

        $options = $component->instance()->intervalOptions([$this->equipment->id]);

        $this->assertSame(['daily' => 'Daily', 'monthly' => 'Monthly'], $options);
        $this->assertArrayNotHasKey('yearly', $options);
    }

    public function test_interval_options_empty_when_checklist_has_no_interval(): void
    {
        Activity::where('equipment_type_id', $this->equipment->equipment_type_id)->update(['interval' => null]);

        $this->assertSame([], app(Dashboard::class)->intervalOptions([$this->equipment->id]));
    }

    public function test_generate_wo_rejects_interval_not_in_checklist(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->user);

        Livewire::test(Dashboard::class)
            ->callAction('generateWorkOrders', data: [
                'fungsi' => 'MOS LTSA',
                'equipment_type_id' => $this->equipment->equipment_type_id,
                'equipment_ids' => [$this->equipment->id],
                'interval' => 'yearly',
                'quantity' => 2,
                'start_time' => '08:00',
                'issued_by' => $this->user->id,
                'skip_weekend' => true,
            ])
            ->assertHasActionErrors(['interval']);

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
