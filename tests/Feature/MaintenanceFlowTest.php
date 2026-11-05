<?php

namespace Tests\Feature;

use App\Filament\Resources\MaintenancePlans\Pages\EditMaintenancePlan;
use App\Filament\Resources\MaintenancePlans\RelationManagers\ActivitiesRelationManager as PlanActivitiesRelationManager;
use App\Filament\Resources\WorkOrders\Pages\EditWorkOrder;
use App\Filament\Resources\WorkOrders\RelationManagers\ActivitiesRelationManager as WorkOrderActivitiesRelationManager;
use App\Filament\Resources\WorkOrders\RelationManagers\WorkersRelationManager as WorkOrderWorkersRelationManager;
use App\Models\Activity;
use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\MaintenanceHistory;
use App\Models\MaintenancePlan;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class MaintenanceFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create();

        $company = Company::create(['code' => 'CO', 'name' => 'Demo Company']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'AR', 'name' => 'Demo Area']);
        $type = EquipmentType::create(['code' => 'MOV', 'name' => 'MOV']);

        $this->equipment = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $type->id,
            'tag_number' => 'MOV-TGL-502B',
            'description' => 'MOV OUTLET T.6 500B',
        ]);
    }

    public function test_plan_interval_is_normalized_to_lowercase(): void
    {
        $plan = $this->makePlan(['interval' => 'Monthly']);

        $this->assertSame('monthly', $plan->fresh()->interval);
    }

    public function test_generate_schedules_repeats_for_monthly_interval_regardless_of_casing(): void
    {
        $plan = $this->makePlan([
            'interval' => 'Monthly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $this->artisan('maintenance:generate-schedules')->assertSuccessful();

        $this->assertSame(12, MaintenanceSchedule::where('maintenance_plan_id', $plan->id)->count());
        $this->assertSame(
            MaintenanceSchedule::STATUSES['Scheduled'],
            MaintenanceSchedule::where('maintenance_plan_id', $plan->id)->first()->status,
        );
    }

    public function test_schedule_status_constants_are_used(): void
    {
        $this->assertSame(
            ['Scheduled', 'In Progress', 'Completed', 'Delayed', 'Rescheduled', 'Cancelled'],
            array_values(MaintenanceSchedule::STATUSES),
        );
    }

    public function test_completing_work_order_creates_maintenance_history(): void
    {
        $plan = $this->makePlan();
        $schedule = MaintenanceSchedule::create([
            'maintenance_plan_id' => $plan->id,
            'equipment_id' => $plan->equipment_id,
            'scheduled_date' => '2026-01-05',
            'status' => MaintenanceSchedule::STATUSES['Scheduled'],
        ]);

        $workOrder = WorkOrder::create([
            'equipment_id' => $plan->equipment_id,
            'maintenance_plan_id' => $plan->id,
            'maintenance_schedule_id' => $schedule->id,
            'issued_by' => User::first()->id,
            'classification' => 'Preventive',
            'start_at' => '2026-01-05 08:00:00',
            'finish_at' => '2026-01-05 10:30:00',
            'status' => 'In Progress',
        ]);

        $activity = Activity::create([
            'equipment_type_id' => $plan->equipment->equipment_type_id,
            'name' => 'Cek Tekanan',
            'type' => 'measurement',
            'maintenance_classification' => 'preventive',
            'interval' => 'monthly',
            'answer_type' => 'quantitative',
            'unit' => 'bar',
            'status' => true,
        ]);

        WorkOrderActivity::create([
            'work_order_id' => $workOrder->id,
            'activity_id' => $activity->id,
            'final_result' => '4.4',
            'executed' => true,
            'note' => 'Normal',
        ]);

        $workOrder->update(['status' => 'Completed']);

        $history = MaintenanceHistory::where('work_order_id', $workOrder->id)->first();

        $this->assertNotNull($history);
        $this->assertSame(150, $history->duration_minutes);
        $this->assertSame('Completed', $history->status);
        $this->assertSame('4.4', $history->findings);
        $this->assertSame('Normal', $history->actions_taken);

        $workOrder->update(['status' => 'In Progress']);
        $workOrder->update(['status' => 'Completed']);

        $this->assertSame(1, MaintenanceHistory::where('work_order_id', $workOrder->id)->count());
    }

    public function test_maintenance_plan_and_work_order_edit_pages_render_with_relation_managers(): void
    {
        config(['app.env' => 'local']);
        Gate::before(fn (): bool => true);

        $plan = $this->makePlan();
        $user = User::first();

        $this->actingAs($user)
            ->get("/admin/maintenance-plans/{$plan->id}/edit")
            ->assertSuccessful();

        Livewire::test(PlanActivitiesRelationManager::class, [
            'ownerRecord' => $plan,
            'pageClass' => EditMaintenancePlan::class,
        ])
            ->mountAction('create')
            ->assertHasNoFormErrors();

        $workOrder = WorkOrder::create([
            'equipment_id' => $plan->equipment_id,
            'maintenance_plan_id' => $plan->id,
            'issued_by' => $user->id,
            'classification' => 'Preventive',
            'start_at' => now(),
            'status' => 'Pending',
        ]);

        $this->actingAs($user)
            ->get("/admin/work-orders/{$workOrder->id}/edit")
            ->assertSuccessful();

        Livewire::test(WorkOrderActivitiesRelationManager::class, [
            'ownerRecord' => $workOrder,
            'pageClass' => EditWorkOrder::class,
        ])
            ->mountAction('create')
            ->assertHasNoFormErrors();

        Livewire::test(WorkOrderWorkersRelationManager::class, [
            'ownerRecord' => $workOrder,
            'pageClass' => EditWorkOrder::class,
        ])
            ->mountAction('create')
            ->assertHasNoFormErrors();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function makePlan(array $attributes = []): MaintenancePlan
    {
        return MaintenancePlan::create([
            'equipment_id' => $this->equipment->id,
            'maintenance_classification' => 'Preventive',
            'interval' => 'monthly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'created_by' => User::firstOrFail()->id,
            'status' => 'Active',
            ...$attributes,
        ]);
    }
}
