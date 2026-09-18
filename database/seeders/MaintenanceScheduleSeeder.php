<?php

namespace Database\Seeders;

use App\Models\MaintenanceSchedule;
use App\Models\MaintenancePlan;
use App\Models\Equipment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MaintenanceScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $plans = MaintenancePlan::all();
        $equipments = Equipment::all();

        $statuses = ['Scheduled', 'In Progress', 'Completed', 'Delayed', 'Cancelled', 'Rescheduled'];

        // Create 25 maintenance schedules
        foreach (range(1, 25) as $i) {
            $plan = $plans->isNotEmpty() ? $plans->random() : null;
            $equipment = $equipments->random();

            $scheduledDate = now()->addDays(rand(1, 90));
            $rescheduledFrom = rand(0, 1) ? now()->subDays(rand(1, 30)) : null;

            MaintenanceSchedule::create([
                'maintenance_plan_id' => $plan?->id,
                'equipment_id' => $equipment->id,
                'scheduled_date' => $scheduledDate,
                'status' => $statuses[array_rand($statuses)],
                'description' => 'Scheduled maintenance for ' . $equipment->name . ' - Schedule ' . $i,
                'rescheduled_from' => $rescheduledFrom,
            ]);
        }
    }
}
