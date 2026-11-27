<?php

namespace Tests\Feature;

use App\Filament\App\Resources\WorkOrders\Pages\ViewWorkOrder;
use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderWorker;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkOrderExecutorTest extends TestCase
{
    use RefreshDatabase;

    protected Equipment $equipment;

    protected User $technician;

    protected User $supervisor;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();

        foreach (['spv_mos', 'mos'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->supervisor = User::factory()->create();
        $this->supervisor->assignRole('spv_mos');

        $this->technician = User::factory()->create(['fungsi' => 'MOS LTSA']);
        $this->technician->assignRole('mos');

        $company = Company::create(['code' => 'CO', 'name' => 'Demo Company']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'AR', 'name' => 'Demo Area']);
        $type = EquipmentType::create(['code' => 'pump', 'name' => 'Pompa', 'fungsi' => 'MOS LTSA']);

        $this->equipment = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $type->id,
            'tag_number' => 'P-101',
        ]);
    }

    protected function makeWorkOrder(array $attributes = []): WorkOrder
    {
        return WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->supervisor->id,
            'fungsi' => 'MOS LTSA',
            'classification' => 'Preventive',
            'start_at' => now(),
            'status' => WorkOrder::STATUSES['Pending'],
            ...$attributes,
        ]);
    }

    public function test_claiming_work_order_registers_executor_worker(): void
    {
        $workOrder = $this->makeWorkOrder();

        $this->assertSame(0, WorkOrderWorker::count());

        $this->assertTrue($workOrder->claimBy($this->technician->id));

        $worker = WorkOrderWorker::where('work_order_id', $workOrder->id)->first();

        $this->assertNotNull($worker);
        $this->assertSame($this->technician->id, $worker->user_id);
        $this->assertSame('Teknisi', $worker->role);
        $this->assertNotNull($worker->started_at);
        $this->assertNull($worker->finished_at);
    }

    public function test_claiming_twice_does_not_duplicate_executor(): void
    {
        $workOrder = $this->makeWorkOrder();

        $workOrder->claimBy($this->technician->id);
        $workOrder->claimBy($this->technician->id);

        $this->assertSame(1, WorkOrderWorker::where('work_order_id', $workOrder->id)->count());
    }

    public function test_starting_work_order_from_app_registers_executor(): void
    {
        $workOrder = $this->makeWorkOrder();

        Filament::setCurrentPanel(Filament::getPanel('app'));
        $this->actingAs($this->technician);

        Livewire::test(ViewWorkOrder::class, ['record' => $workOrder->id])
            ->callAction('start')
            ->assertHasNoActionErrors();

        $this->assertSame(WorkOrder::STATUSES['In Progress'], $workOrder->fresh()->status);

        $worker = WorkOrderWorker::where('work_order_id', $workOrder->id)->firstOrFail();

        $this->assertSame($this->technician->id, $worker->user_id);
        $this->assertSame('Teknisi', $worker->role);
        $this->assertNotNull($worker->started_at);
    }

    public function test_completing_work_order_sets_executor_finished_at(): void
    {
        $workOrder = $this->makeWorkOrder();

        Filament::setCurrentPanel(Filament::getPanel('app'));
        $this->actingAs($this->technician);

        Livewire::test(ViewWorkOrder::class, ['record' => $workOrder->id])
            ->callAction('start')
            ->callAction('complete')
            ->assertHasNoActionErrors();

        $workOrder->refresh();
        $worker = WorkOrderWorker::where('work_order_id', $workOrder->id)->firstOrFail();

        $this->assertSame(WorkOrder::STATUSES['Completed'], $workOrder->status);
        $this->assertNotNull($worker->finished_at);
    }

    public function test_rejected_review_clears_executor_finished_at(): void
    {
        $workOrder = $this->makeWorkOrder();

        $workOrder->claimBy($this->technician->id);
        $workOrder->update([
            'status' => WorkOrder::STATUSES['Completed'],
            'finish_at' => now(),
        ]);

        $this->assertNotNull(
            WorkOrderWorker::where('work_order_id', $workOrder->id)->value('finished_at'),
        );

        // Review ditolak: WO dibuka kembali ke In Progress.
        $workOrder->update([
            'approval_status' => WorkOrder::APPROVAL_REJECTED,
            'approval_notes' => 'Foto kurang jelas',
            'status' => WorkOrder::STATUSES['In Progress'],
            'finish_at' => null,
        ]);

        $this->assertNull(
            WorkOrderWorker::where('work_order_id', $workOrder->id)->value('finished_at'),
        );
    }

    public function test_supervisor_completing_claimed_work_order_finishes_technician_worker(): void
    {
        $workOrder = $this->makeWorkOrder();
        $workOrder->claimBy($this->technician->id);

        $this->actingAs($this->supervisor);

        $workOrder->update([
            'status' => WorkOrder::STATUSES['Completed'],
            'finish_at' => now(),
        ]);

        $worker = WorkOrderWorker::where('work_order_id', $workOrder->id)
            ->where('user_id', $this->technician->id)
            ->firstOrFail();

        $this->assertNotNull($worker->finished_at);
        $this->assertSame(
            0,
            WorkOrderWorker::where('work_order_id', $workOrder->id)
                ->where('user_id', $this->supervisor->id)
                ->count(),
        );
    }

    public function test_supervisor_completing_without_coordinator_is_recorded_as_supervisor(): void
    {
        $workOrder = $this->makeWorkOrder();

        $this->actingAs($this->supervisor);

        $workOrder->update([
            'status' => WorkOrder::STATUSES['Completed'],
            'finish_at' => now(),
        ]);

        $worker = WorkOrderWorker::where('work_order_id', $workOrder->id)->first();

        $this->assertNotNull($worker);
        $this->assertSame($this->supervisor->id, $worker->user_id);
        $this->assertSame('Supervisor', $worker->role);
        $this->assertNotNull($worker->finished_at);
    }

    public function test_executor_worker_is_visible_in_work_order_infolist(): void
    {
        $workOrder = $this->makeWorkOrder();
        $workOrder->claimBy($this->technician->id);

        Filament::setCurrentPanel(Filament::getPanel('app'));

        $this->actingAs($this->technician);

        Livewire::test(ViewWorkOrder::class, ['record' => $workOrder->id])
            ->assertOk()
            ->assertSee('Pelaksana')
            ->assertSee($this->technician->name);
    }
}
