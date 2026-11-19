<?php

namespace Tests\Feature;

use App\Filament\Pages\HousekeepingReport;
use App\Filament\Resources\HousekeepingPlans\Pages\ViewHousekeepingPlan;
use App\Filament\Resources\MaintenanceHistories\Pages\ViewMaintenanceHistory;
use App\Filament\Widgets\CompanyAreaMapWidget;
use App\Filament\Widgets\HousekeepingAreaMapWidget;
use App\Filament\Widgets\RecentHousekeepingPlansWidget;
use App\Models\Activity;
use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingFacility;
use App\Models\HousekeepingPlan;
use App\Models\HousekeepingPlanTask;
use App\Models\MaintenanceHistory;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class QueryCountRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function queryCount(string $component, array $params = []): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        Livewire::test($component, $params)->assertOk();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_housekeeping_report_does_not_n_plus_one(): void
    {
        Gate::before(fn () => true);
        $user = User::factory()->create(['status' => true]);
        $this->actingAs($user);

        for ($a = 0; $a < 10; $a++) {
            $company = Company::create(['code' => "C{$a}", 'name' => "Co{$a}"]);
            $area = HousekeepingArea::create(['company_id' => $company->id, 'code' => "a{$a}", 'name' => "Area{$a}"]);
            $facility = HousekeepingFacility::create(['housekeeping_area_id' => $area->id, 'name' => "Fac{$a}"]);
            $plan = HousekeepingPlan::create([
                'housekeeping_area_id' => $area->id,
                'plan_date' => '2026-01-01',
                'created_by' => $user->id,
            ]);

            for ($t = 0; $t < 5; $t++) {
                HousekeepingPlanTask::create([
                    'housekeeping_plan_id' => $plan->id,
                    'housekeeping_facility_id' => $facility->id,
                    'name' => "Task{$a}{$t}",
                    'status' => 'Done',
                ]);
            }
        }

        $this->assertLessThan(15, $this->queryCount(HousekeepingReport::class));
        $this->assertLessThan(10, $this->queryCount(RecentHousekeepingPlansWidget::class));
        $this->assertLessThan(10, $this->queryCount(HousekeepingAreaMapWidget::class));
        $this->assertLessThan(10, $this->queryCount(CompanyAreaMapWidget::class));
    }

    public function test_view_housekeeping_plan_does_not_n_plus_one(): void
    {
        Gate::before(fn () => true);
        $user = User::factory()->create(['status' => true]);
        $this->actingAs($user);

        $company = Company::create(['code' => 'C', 'name' => 'Co']);
        $area = HousekeepingArea::create(['company_id' => $company->id, 'code' => 'a', 'name' => 'A']);
        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $area->id,
            'plan_date' => '2026-01-01',
            'created_by' => $user->id,
        ]);

        for ($i = 0; $i < 30; $i++) {
            $facility = HousekeepingFacility::create(['housekeeping_area_id' => $area->id, 'name' => "F{$i}"]);
            HousekeepingPlanTask::create([
                'housekeeping_plan_id' => $plan->id,
                'housekeeping_facility_id' => $facility->id,
                'name' => "T{$i}",
                'status' => 'Done',
            ]);
        }

        $this->assertLessThan(20, $this->queryCount(ViewHousekeepingPlan::class, ['record' => $plan->getKey()]));
    }

    public function test_view_maintenance_history_does_not_n_plus_one(): void
    {
        Gate::before(fn () => true);
        $user = User::factory()->create(['status' => true]);
        $this->actingAs($user);

        $company = Company::create(['code' => 'C', 'name' => 'Co']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'a', 'name' => 'A']);
        $type = EquipmentType::create(['code' => 'T', 'name' => 'T']);
        $equipment = Equipment::create(['area_id' => $area->id, 'equipment_type_id' => $type->id, 'tag_number' => 'E1']);

        $workOrder = WorkOrder::create([
            'work_order_number' => 'WO-1',
            'equipment_id' => $equipment->id,
            'issued_by' => $user->id,
            'classification' => 'Preventive',
            'start_at' => '2026-01-01 08:00:00',
            'status' => 'Completed',
        ]);

        for ($i = 0; $i < 30; $i++) {
            $activity = Activity::create([
                'equipment_type_id' => $type->id,
                'name' => "A{$i}",
                'type' => 'maintenance',
                'maintenance_classification' => 'preventive',
                'interval' => 'monthly',
                'answer_type' => 'qualitative',
                'status' => true,
            ]);
            WorkOrderActivity::create([
                'work_order_id' => $workOrder->id,
                'activity_id' => $activity->id,
                'executed' => true,
            ]);
        }

        $history = MaintenanceHistory::create([
            'equipment_id' => $equipment->id,
            'work_order_id' => $workOrder->id,
            'classification' => 'Preventive',
            'description' => 'd',
            'maintenance_date' => '2026-01-01 09:00:00',
            'performed_by' => $user->id,
            'status' => 'Completed',
        ]);

        $this->assertLessThan(20, $this->queryCount(ViewMaintenanceHistory::class, ['record' => $history->getKey()]));
    }
}
