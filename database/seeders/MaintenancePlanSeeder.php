<?php

namespace Database\Seeders;

use App\Models\MaintenancePlan;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MaintenancePlanSeeder extends Seeder
{
    public function run(): void
    {
        $equipments = Equipment::all();
        $users = User::where('email', '!=', 'admin@admin.com')->get();

        $classifications = ['Preventive', 'Predictive', 'Condition-Based'];
        $intervals = ['Daily', 'Weekly', 'Monthly', 'Quarterly', 'Bi-Annually', 'Yearly'];
        $statuses = ['Active', 'Inactive', 'Suspended', 'Completed'];

        $descriptions = [
            'Regular preventive maintenance plan',
            'Condition-based monitoring schedule',
            'Predictive maintenance program',
            'Routine inspection and lubrication',
            'Equipment performance monitoring',
            'Safety inspection schedule',
            'Efficiency optimization plan',
            'Component replacement schedule',
            'System health check program',
            'Preventive care routine',
        ];

        // Create 15 maintenance plans
        foreach (range(1, 15) as $i) {
            $startDate = now()->subMonths(rand(0, 6));
            $endDate = $startDate->copy()->addMonths(12);

            MaintenancePlan::create([
                'equipment_id' => $equipments->random()->id,
                'maintenance_classification' => $classifications[array_rand($classifications)],
                'interval' => $intervals[array_rand($intervals)],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'created_by' => $users->random()->id,
                'technician_coordinator_id' => $users->random()->id,
                'description' => $descriptions[array_rand($descriptions)] . ' - Plan ' . $i,
                'status' => $statuses[array_rand($statuses)],
            ]);
        }
    }
}
