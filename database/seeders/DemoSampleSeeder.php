<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\MaintenancePlan;
use App\Models\MaintenancePlanActivity;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderActivity;
use Illuminate\Database\Seeder;

class DemoSampleSeeder extends Seeder
{
    public function run(): void
    {
        Activity::query()->delete();
        MaintenancePlanActivity::query()->delete();
        WorkOrderActivity::query()->delete();
        MaintenanceSchedule::query()->delete();
        WorkOrder::query()->delete();
        \App\Models\MaintenanceHistory::query()->delete();

        $user = User::firstOrFail();
        $pompaType = EquipmentType::where('name', 'pompa')->firstOrFail();

        $equipment = Equipment::with('equipmentType')->where('equipment_type_id', $pompaType->id)->first()
            ?? Equipment::firstOrFail();

        $activities = [
            ['name' => 'Cek Fisik Kondisi Pompa', 'type' => 'visual_check', 'maintenance_classification' => 'preventive', 'interval' => 'daily', 'answer_type' => 'Qualitative', 'minimum' => null, 'maximum' => null, 'optimum' => null, 'unit' => null],
            ['name' => 'Ukur Vibrasi Bearing', 'type' => 'measurement', 'maintenance_classification' => 'predictive', 'interval' => 'weekly', 'answer_type' => 'Quantitative', 'minimum' => 2.8, 'maximum' => 7.1, 'optimum' => 4.5, 'unit' => 'mm/s'],
            ['name' => 'Greasing Bearing Pompa', 'type' => 'lubrication', 'maintenance_classification' => 'preventive', 'interval' => 'monthly', 'answer_type' => 'Qualitative', 'minimum' => null, 'maximum' => null, 'optimum' => null, 'unit' => null],
            ['name' => 'Ukur Temperatur Winding Motor', 'type' => 'measurement', 'maintenance_classification' => 'condition_based', 'interval' => 'monthly', 'answer_type' => 'Quantitative', 'minimum' => 60, 'maximum' => 85, 'optimum' => 65, 'unit' => '°C'],
        ];

        $createdActivities = [];
        foreach ($activities as $data) {
            $createdActivities[] = Activity::create([
                'equipment_type_id' => $pompaType->id,
                'reference' => 'Demo',
                'status' => true,
                ...$data,
            ]);
        }

        $plan = MaintenancePlan::create([
            'equipment_id' => $equipment->id,
            'maintenance_classification' => 'preventive',
            'interval' => 'monthly',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'created_by' => $user->id,
            'technician_coordinator_id' => $user->id,
            'description' => 'Demo: Plan preventif pompa (template kegiatan)',
            'status' => 'active',
        ]);

        foreach ($createdActivities as $idx => $act) {
            MaintenancePlanActivity::create([
                'maintenance_plan_id' => $plan->id,
                'activity_id' => $act->id,
                'sort_order' => $idx + 1,
            ]);
        }

        $schedule = MaintenanceSchedule::create([
            'maintenance_plan_id' => $plan->id,
            'equipment_id' => $equipment->id,
            'scheduled_date' => now()->addWeek()->toDateString(),
            'status' => 'Scheduled',
            'description' => 'Demo: Jadwal dari plan di atas',
        ]);

        $wo = WorkOrder::create([
            'equipment_id' => $equipment->id,
            'maintenance_plan_id' => $plan->id,
            'maintenance_schedule_id' => $schedule->id,
            'maintenance_request_id' => null,
            'issued_by' => $user->id,
            'technician_coordinator_id' => $user->id,
            'classification' => 'preventive',
            'interval' => 'monthly',
            'start_at' => now(),
            'note' => 'Demo: WO dari Plan → Schedule',
            'status' => 'In Progress',
        ]);

        foreach ($createdActivities as $act) {
            WorkOrderActivity::create([
                'work_order_id' => $wo->id,
                'activity_id' => $act->id,
                'reference' => $act->reference,
                'unit' => $act->unit,
                'pre_inspection' => 'Awal: sesuai checklist',
                'final_result' => $act->answer_type === 'Quantitative' ? (string) $act->optimum : 'OK',
                'executed' => true,
                'note' => 'Demo: hasil isi teknisi',
            ]);
        }

        \App\Models\MaintenanceRequest::firstOrCreate(
            ['description' => 'Demo: Kebocoran seal pompa'],
            [
                'equipment_id' => $equipment->id,
                'reported_by' => $user->id,
                'operation_status' => 'running_with_defect',
                'damage_date' => now()->toDateString(),
                'damage_time' => now()->format('H:i:s'),
                'equipment_condition' => 'Terdengar noise berlebih.',
                'impact' => 'Risiko kebocoran.',
                'early_action' => 'Kurangi beban.',
                'status' => 'pending',
            ]
        );
    }
}
