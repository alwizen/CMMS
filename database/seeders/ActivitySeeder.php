<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\EquipmentType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $pump = EquipmentType::where('code', 'PUMP')->first();
        $tank = EquipmentType::where('code', 'TANK')->first();
        $compressor = EquipmentType::where('code', 'COMPRESSOR')->first();
        $genset = EquipmentType::where('code', 'GENSET')->first();
        $panel = EquipmentType::where('code', 'PANEL')->first();
        $firePump = EquipmentType::where('code', 'FIRE-PUMP')->first();
        $motor = EquipmentType::where('code', 'MOTOR')->first();
        $valve = EquipmentType::where('code', 'VALVE')->first();

        $activities = [
            // Pump Activities
            ['equipment_type_id' => $pump->id, 'name' => 'Oil Change', 'type' => 'Maintenance', 'maintenance_classification' => 'Preventive', 'interval' => 'Monthly', 'answer_type' => 'Qualitative', 'reference' => 'Ganti oli dan filter sesuai manual'],
            ['equipment_type_id' => $pump->id, 'name' => 'Vibration Check', 'type' => 'Inspection', 'maintenance_classification' => 'Preventive', 'interval' => 'Weekly', 'answer_type' => 'Quantitative', 'reference' => 'Ukur level getaran', 'optimum' => 2.8, 'maximum' => 5.0, 'unit' => 'mm/s'],
            ['equipment_type_id' => $pump->id, 'name' => 'Temperature Check', 'type' => 'Inspection', 'maintenance_classification' => 'Preventive', 'interval' => 'Weekly', 'answer_type' => 'Quantitative', 'reference' => 'Cek suhu bearing', 'optimum' => 50.0, 'maximum' => 80.0, 'unit' => '°C'],
            ['equipment_type_id' => $pump->id, 'name' => 'Bearing Replacement', 'type' => 'Replacement', 'maintenance_classification' => 'Corrective', 'interval' => 'As needed', 'answer_type' => 'Qualitative', 'reference' => 'Ganti bearing yang aus'],

            // Tank Activities
            ['equipment_type_id' => $tank->id, 'name' => 'Visual Inspection', 'type' => 'Inspection', 'maintenance_classification' => 'Preventive', 'interval' => 'Quarterly', 'answer_type' => 'Qualitative', 'reference' => 'Inspeksi visual korosi dan kebocoran'],
            ['equipment_type_id' => $tank->id, 'name' => 'Internal Cleaning', 'type' => 'Cleaning', 'maintenance_classification' => 'Preventive', 'interval' => 'Yearly', 'answer_type' => 'Qualitative', 'reference' => 'Pembersihan internal dan removal endapan'],
            ['equipment_type_id' => $tank->id, 'name' => 'Pressure Test', 'type' => 'Testing', 'maintenance_classification' => 'Preventive', 'interval' => 'Yearly', 'answer_type' => 'Quantitative', 'reference' => 'Test tekanan tangki', 'optimum' => 2.5, 'maximum' => 3.0, 'unit' => 'bar'],

            // Compressor Activities
            ['equipment_type_id' => $compressor->id, 'name' => 'Air Filter Cleaning', 'type' => 'Cleaning', 'maintenance_classification' => 'Preventive', 'interval' => 'Monthly', 'answer_type' => 'Qualitative', 'reference' => 'Bersihkan filter udara'],
            ['equipment_type_id' => $compressor->id, 'name' => 'Oil Level Check', 'type' => 'Inspection', 'maintenance_classification' => 'Preventive', 'interval' => 'Monthly', 'answer_type' => 'Qualitative', 'reference' => 'Periksa level oli'],
            ['equipment_type_id' => $compressor->id, 'name' => 'Pressure Relief Valve Check', 'type' => 'Testing', 'maintenance_classification' => 'Preventive', 'interval' => 'Quarterly', 'answer_type' => 'Quantitative', 'reference' => 'Test valve relief', 'optimum' => 8.0, 'maximum' => 8.5, 'unit' => 'bar'],

            // Genset Activities
            ['equipment_type_id' => $genset->id, 'name' => 'Engine Oil Change', 'type' => 'Maintenance', 'maintenance_classification' => 'Preventive', 'interval' => 'Every 250 hours', 'answer_type' => 'Qualitative', 'reference' => 'Ganti oli dan filter mesin'],
            ['equipment_type_id' => $genset->id, 'name' => 'Load Test', 'type' => 'Testing', 'maintenance_classification' => 'Preventive', 'interval' => 'Monthly', 'answer_type' => 'Qualitative', 'reference' => 'Test genset dengan beban'],
            ['equipment_type_id' => $genset->id, 'name' => 'Battery Check', 'type' => 'Inspection', 'maintenance_classification' => 'Preventive', 'interval' => 'Monthly', 'answer_type' => 'Qualitative', 'reference' => 'Periksa kondisi baterai'],
            ['equipment_type_id' => $genset->id, 'name' => 'Fuel Filter Change', 'type' => 'Maintenance', 'maintenance_classification' => 'Preventive', 'interval' => 'Every 500 hours', 'answer_type' => 'Qualitative', 'reference' => 'Ganti filter bahan bakar'],

            // Panel Activities
            ['equipment_type_id' => $panel->id, 'name' => 'Electrical Check', 'type' => 'Testing', 'maintenance_classification' => 'Preventive', 'interval' => 'Quarterly', 'answer_type' => 'Quantitative', 'reference' => 'Ukur tegangan dan arus', 'optimum' => 380.0, 'minimum' => 360.0, 'maximum' => 400.0, 'unit' => 'V'],
            ['equipment_type_id' => $panel->id, 'name' => 'Panel Cleaning', 'type' => 'Cleaning', 'maintenance_classification' => 'Preventive', 'interval' => 'Monthly', 'answer_type' => 'Qualitative', 'reference' => 'Bersihkan debu dan debris'],
            ['equipment_type_id' => $panel->id, 'name' => 'Circuit Breaker Test', 'type' => 'Testing', 'maintenance_classification' => 'Preventive', 'interval' => 'Yearly', 'answer_type' => 'Qualitative', 'reference' => 'Test circuit breaker'],

            // Fire Pump Activities
            ['equipment_type_id' => $firePump->id, 'name' => 'System Pressure Test', 'type' => 'Testing', 'maintenance_classification' => 'Preventive', 'interval' => 'Quarterly', 'answer_type' => 'Quantitative', 'reference' => 'Test tekanan sistem', 'optimum' => 7.0, 'maximum' => 8.0, 'unit' => 'bar'],
            ['equipment_type_id' => $firePump->id, 'name' => 'Flow Rate Check', 'type' => 'Testing', 'maintenance_classification' => 'Preventive', 'interval' => 'Semi-annually', 'answer_type' => 'Quantitative', 'reference' => 'Ukur laju aliran', 'optimum' => 500.0, 'maximum' => 550.0, 'unit' => 'lpm'],

            // Motor Activities
            ['equipment_type_id' => $motor->id, 'name' => 'Bearing Lubrication', 'type' => 'Maintenance', 'maintenance_classification' => 'Preventive', 'interval' => 'Quarterly', 'answer_type' => 'Qualitative', 'reference' => 'Pelumasan bearing'],
            ['equipment_type_id' => $motor->id, 'name' => 'Insulation Resistance Test', 'type' => 'Testing', 'maintenance_classification' => 'Preventive', 'interval' => 'Yearly', 'answer_type' => 'Quantitative', 'reference' => 'Test resistansi isolasi', 'minimum' => 1.0, 'unit' => 'MΩ'],

            // Valve Activities
            ['equipment_type_id' => $valve->id, 'name' => 'Valve Operation Test', 'type' => 'Testing', 'maintenance_classification' => 'Preventive', 'interval' => 'Monthly', 'answer_type' => 'Qualitative', 'reference' => 'Test operasi valve'],
            ['equipment_type_id' => $valve->id, 'name' => 'Seal Inspection', 'type' => 'Inspection', 'maintenance_classification' => 'Preventive', 'interval' => 'Quarterly', 'answer_type' => 'Qualitative', 'reference' => 'Inspeksi kebocoran seal'],
        ];

        foreach ($activities as $activity) {
            Activity::create($activity + ['status' => true]);
        }
    }
}
