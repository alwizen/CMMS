<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\EquipmentType;
use Illuminate\Database\Seeder;

class ActivitySeeder extends Seeder
{
    /**
     * Master checklist (Activity) per equipment type.
     * Nilai type/classification/answer_type/interval mengikuti konstanta di App\Models\Activity
     * agar cocok dengan pilihan pada form dan Generate WO.
     *
     * @var array<int, array{type: string, name: string, activity_type: string, classification: string, interval: string, answer_type: string, reference: string, optimum?: float, minimum?: float, maximum?: float, unit?: string}>
     */
    public const ACTIVITIES = [
        // Pump
        ['type' => 'PUMP', 'name' => 'Visual Check Pompa', 'activity_type' => 'visual_check', 'classification' => 'preventive', 'interval' => 'daily', 'answer_type' => 'qualitative', 'reference' => 'Cek kebocoran, getaran, dan suara tidak normal'],
        ['type' => 'PUMP', 'name' => 'Vibration Check', 'activity_type' => 'measurement', 'classification' => 'preventive', 'interval' => 'weekly', 'answer_type' => 'quantitative', 'reference' => 'Ukur level getaran bearing', 'optimum' => 2.8, 'maximum' => 5.0, 'unit' => 'mm/s'],
        ['type' => 'PUMP', 'name' => 'Oil Change', 'activity_type' => 'maintenance', 'classification' => 'preventive', 'interval' => 'monthly', 'answer_type' => 'qualitative', 'reference' => 'Ganti oli dan filter sesuai manual'],
        ['type' => 'PUMP', 'name' => 'Bearing Lubrication', 'activity_type' => 'lubrication', 'classification' => 'preventive', 'interval' => 'quarterly', 'answer_type' => 'qualitative', 'reference' => 'Pelumasan bearing pompa'],
        ['type' => 'PUMP', 'name' => 'Overhaul Tahunan', 'activity_type' => 'maintenance', 'classification' => 'preventive', 'interval' => 'yearly', 'answer_type' => 'qualitative', 'reference' => 'Overhaul menyeluruh pompa'],

        // Compressor
        ['type' => 'COMPRESSOR', 'name' => 'Drain Condensate', 'activity_type' => 'maintenance', 'classification' => 'preventive', 'interval' => 'daily', 'answer_type' => 'qualitative', 'reference' => 'Buang kondensat dari tangki udara'],
        ['type' => 'COMPRESSOR', 'name' => 'Oil Level Check', 'activity_type' => 'inspection', 'classification' => 'preventive', 'interval' => 'weekly', 'answer_type' => 'qualitative', 'reference' => 'Periksa level oli kompresor'],
        ['type' => 'COMPRESSOR', 'name' => 'Air Filter Cleaning', 'activity_type' => 'cleaning', 'classification' => 'preventive', 'interval' => 'monthly', 'answer_type' => 'qualitative', 'reference' => 'Bersihkan filter udara'],
        ['type' => 'COMPRESSOR', 'name' => 'Pressure Relief Valve Check', 'activity_type' => 'testing', 'classification' => 'preventive', 'interval' => 'quarterly', 'answer_type' => 'quantitative', 'reference' => 'Test valve relief', 'optimum' => 8.0, 'maximum' => 8.5, 'unit' => 'bar'],

        // Tank
        ['type' => 'TANK', 'name' => 'Visual Inspection Tangki', 'activity_type' => 'inspection', 'classification' => 'preventive', 'interval' => 'weekly', 'answer_type' => 'qualitative', 'reference' => 'Inspeksi visual korosi dan kebocoran'],
        ['type' => 'TANK', 'name' => 'Water Drain', 'activity_type' => 'maintenance', 'classification' => 'preventive', 'interval' => 'monthly', 'answer_type' => 'qualitative', 'reference' => 'Buang air endapan pada tangki'],
        ['type' => 'TANK', 'name' => 'Pressure Test', 'activity_type' => 'testing', 'classification' => 'preventive', 'interval' => 'quarterly', 'answer_type' => 'quantitative', 'reference' => 'Test tekanan tangki', 'optimum' => 2.5, 'maximum' => 3.0, 'unit' => 'bar'],
        ['type' => 'TANK', 'name' => 'Internal Cleaning', 'activity_type' => 'cleaning', 'classification' => 'preventive', 'interval' => 'yearly', 'answer_type' => 'qualitative', 'reference' => 'Pembersihan internal dan removal endapan'],

        // Panel
        ['type' => 'PANEL', 'name' => 'Panel Cleaning', 'activity_type' => 'cleaning', 'classification' => 'preventive', 'interval' => 'monthly', 'answer_type' => 'qualitative', 'reference' => 'Bersihkan debu dan debris panel'],
        ['type' => 'PANEL', 'name' => 'Electrical Check', 'activity_type' => 'measurement', 'classification' => 'preventive', 'interval' => 'quarterly', 'answer_type' => 'quantitative', 'reference' => 'Ukur tegangan dan arus', 'optimum' => 380.0, 'minimum' => 360.0, 'maximum' => 400.0, 'unit' => 'V'],
        ['type' => 'PANEL', 'name' => 'Circuit Breaker Test', 'activity_type' => 'testing', 'classification' => 'preventive', 'interval' => 'yearly', 'answer_type' => 'qualitative', 'reference' => 'Test circuit breaker'],

        // Valve
        ['type' => 'VALVE', 'name' => 'Valve Operation Test', 'activity_type' => 'testing', 'classification' => 'preventive', 'interval' => 'monthly', 'answer_type' => 'qualitative', 'reference' => 'Test operasi buka/tutup valve'],
        ['type' => 'VALVE', 'name' => 'Seal Inspection', 'activity_type' => 'inspection', 'classification' => 'preventive', 'interval' => 'quarterly', 'answer_type' => 'qualitative', 'reference' => 'Inspeksi kebocoran seal'],

        // Motor
        ['type' => 'MOTOR', 'name' => 'Bearing Lubrication', 'activity_type' => 'lubrication', 'classification' => 'preventive', 'interval' => 'quarterly', 'answer_type' => 'qualitative', 'reference' => 'Pelumasan bearing motor'],
        ['type' => 'MOTOR', 'name' => 'Insulation Resistance Test', 'activity_type' => 'testing', 'classification' => 'preventive', 'interval' => 'yearly', 'answer_type' => 'quantitative', 'reference' => 'Test resistansi isolasi', 'minimum' => 1.0, 'unit' => 'MΩ'],

        // Fire Pump
        ['type' => 'FIRE-PUMP', 'name' => 'Visual Check Fire Pump', 'activity_type' => 'visual_check', 'classification' => 'preventive', 'interval' => 'monthly', 'answer_type' => 'qualitative', 'reference' => 'Cek kondisi fisik dan panel fire pump'],
        ['type' => 'FIRE-PUMP', 'name' => 'System Pressure Test', 'activity_type' => 'testing', 'classification' => 'preventive', 'interval' => 'quarterly', 'answer_type' => 'quantitative', 'reference' => 'Test tekanan sistem', 'optimum' => 7.0, 'maximum' => 8.0, 'unit' => 'bar'],
        ['type' => 'FIRE-PUMP', 'name' => 'Flow Rate Check', 'activity_type' => 'testing', 'classification' => 'preventive', 'interval' => 'semi_annual', 'answer_type' => 'quantitative', 'reference' => 'Ukur laju aliran', 'optimum' => 500.0, 'maximum' => 550.0, 'unit' => 'lpm'],

        // Genset
        ['type' => 'GENSET', 'name' => 'Battery Check', 'activity_type' => 'inspection', 'classification' => 'preventive', 'interval' => 'monthly', 'answer_type' => 'qualitative', 'reference' => 'Periksa kondisi baterai genset'],
        ['type' => 'GENSET', 'name' => 'Load Test', 'activity_type' => 'testing', 'classification' => 'preventive', 'interval' => 'quarterly', 'answer_type' => 'qualitative', 'reference' => 'Test genset dengan beban'],
        ['type' => 'GENSET', 'name' => 'Engine Oil Change', 'activity_type' => 'maintenance', 'classification' => 'preventive', 'interval' => 'yearly', 'answer_type' => 'qualitative', 'reference' => 'Ganti oli dan filter mesin'],
    ];

    public function run(): void
    {
        $types = EquipmentType::query()->pluck('id', 'code');

        foreach (self::ACTIVITIES as $activity) {
            $typeId = $types[$activity['type']] ?? null;

            if (! $typeId) {
                continue;
            }

            Activity::firstOrCreate(
                [
                    'equipment_type_id' => $typeId,
                    'name' => $activity['name'],
                ],
                [
                    'type' => $activity['activity_type'],
                    'maintenance_classification' => $activity['classification'],
                    'interval' => $activity['interval'],
                    'answer_type' => $activity['answer_type'],
                    'reference' => $activity['reference'],
                    'optimum' => $activity['optimum'] ?? null,
                    'minimum' => $activity['minimum'] ?? null,
                    'maximum' => $activity['maximum'] ?? null,
                    'unit' => $activity['unit'] ?? null,
                    'status' => true,
                ],
            );
        }

        $this->command?->info('Checklist (activity) siap: '.count(self::ACTIVITIES).' item');
    }
}
