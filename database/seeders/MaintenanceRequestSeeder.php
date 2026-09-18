<?php

namespace Database\Seeders;

use App\Models\MaintenanceRequest;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MaintenanceRequestSeeder extends Seeder
{
    public function run(): void
    {
        $equipments = Equipment::all();
        $users = User::where('email', '!=', 'admin@admin.com')->get();

        $statuses = ['Open', 'Assigned', 'In Progress', 'Completed', 'Rejected'];
        $operationStatuses = ['Running', 'Stopped', 'Standby', 'Faulty'];
        $equipmentConditions = ['Good', 'Fair', 'Poor', 'Critical'];
        $impacts = ['Low', 'Medium', 'High', 'Critical'];

        $descriptions = [
            'Equipment making unusual noise',
            'Temperature readings above normal',
            'Vibration detected',
            'Oil leak detected',
            'Bearing noise',
            'Loss of pressure',
            'Electrical fault',
            'Control system malfunction',
            'Seal failure',
            'Lubrication issue',
            'Power supply problem',
            'Sensor malfunction',
            'Valve stuck',
            'Filter clogged',
            'Motor overheating',
        ];

        $earlyActions = [
            'Check fluid level',
            'Inspect for leaks',
            'Clean filter',
            'Lubricate bearings',
            'Check electrical connections',
            'Verify sensor readings',
            'Inspect seals',
            'Check alignment',
            'Review pressure gauge',
            'Monitor temperature',
        ];

        // Create 20 maintenance requests
        foreach (range(1, 20) as $i) {
            $damageDate = now()->subDays(rand(1, 30));

            MaintenanceRequest::create([
                'equipment_id' => $equipments->random()->id,
                'request_number' => 'MR-' . date('Ymd') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'reported_by' => $users->random()->id,
                'operation_status' => $operationStatuses[array_rand($operationStatuses)],
                'description' => $descriptions[array_rand($descriptions)],
                'damage_date' => $damageDate,
                'damage_time' => $damageDate->copy()->setTime(rand(6, 18), rand(0, 59)),
                'equipment_condition' => $equipmentConditions[array_rand($equipmentConditions)],
                'impact' => $impacts[array_rand($impacts)],
                'early_action' => $earlyActions[array_rand($earlyActions)],
                'status' => $statuses[array_rand($statuses)],
            ]);
        }
    }
}
