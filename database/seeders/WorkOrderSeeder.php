<?php

namespace Database\Seeders;

use App\Models\WorkOrder;
use App\Models\Equipment;
use App\Models\MaintenancePlan;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WorkOrderSeeder extends Seeder
{
    public function run(): void
    {
        $equipments = Equipment::all();
        $users = User::where('email', '!=', 'admin@admin.com')->get();
        $plans = MaintenancePlan::all();
        $schedules = MaintenanceSchedule::all();
        $requests = MaintenanceRequest::all();

        $classifications = ['Preventive', 'Corrective'];
        $statuses = ['Open', 'In Progress', 'On Hold', 'Completed', 'Cancelled'];
        $intervals = ['Daily', 'Weekly', 'Monthly', 'Quarterly', 'Yearly', 'As Needed'];

        // Create 30 work orders
        foreach (range(1, 30) as $i) {
            $equipment = $equipments->random();
            $plan = $plans->isNotEmpty() ? $plans->random() : null;
            $schedule = $schedules->isNotEmpty() ? $schedules->random() : null;
            $request = $requests->isNotEmpty() ? $requests->random() : null;

            $startDate = now()->subDays(rand(0, 60));
            $finishDate = $startDate->copy()->addDays(rand(1, 7));

            WorkOrder::create([
                'work_order_number' => 'WO-' . date('Ymd') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'equipment_id' => $equipment->id,
                'maintenance_plan_id' => $plan?->id,
                'maintenance_schedule_id' => $schedule?->id,
                'maintenance_request_id' => $request?->id,
                'issued_by' => $users->random()->id,
                'technician_coordinator_id' => $users->random()->id,
                'classification' => $classifications[array_rand($classifications)],
                'interval' => $intervals[array_rand($intervals)],
                'start_at' => $startDate,
                'finish_at' => $finishDate,
                'note' => 'Maintenance work order for ' . $equipment->name . ' - Task ' . $i,
                'status' => $statuses[array_rand($statuses)],
            ]);
        }
    }
}
