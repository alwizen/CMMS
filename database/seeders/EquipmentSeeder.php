<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Equipment;
use App\Models\EquipmentType;
use Illuminate\Database\Seeder;

class EquipmentSeeder extends Seeder
{
    /**
     * @var array<int, array{tag_number: string, area: string, type: string, description: string, manufacturer: string, model: string, criticality: string}>
     */
    public const EQUIPMENT = [
        ['tag_number' => 'P-101', 'area' => 'LOAD', 'type' => 'PUMP', 'description' => 'Main loading pump', 'manufacturer' => 'GRUNDFOS', 'model' => 'CR32', 'criticality' => 'critical'],
        ['tag_number' => 'P-102', 'area' => 'LOAD', 'type' => 'PUMP', 'description' => 'Standby loading pump', 'manufacturer' => 'GRUNDFOS', 'model' => 'CR32', 'criticality' => 'high'],
        ['tag_number' => 'C-01', 'area' => 'MCC', 'type' => 'COMPRESSOR', 'description' => 'Main air compressor', 'manufacturer' => 'ATLAS COPCO', 'model' => 'GA-30', 'criticality' => 'critical'],
        ['tag_number' => 'TK-01', 'area' => 'STORAGE', 'type' => 'TANK', 'description' => 'Main storage tank', 'manufacturer' => 'VESTOIL', 'model' => 'STD-50000', 'criticality' => 'critical'],
        ['tag_number' => 'MOV-101', 'area' => 'UNLOAD', 'type' => 'VALVE', 'description' => 'Motor operated valve', 'manufacturer' => 'ASCO', 'model' => 'EF8320', 'criticality' => 'high'],
        ['tag_number' => 'MCC-01', 'area' => 'MCC', 'type' => 'PANEL', 'description' => 'Main control center panel', 'manufacturer' => 'SIEMENS', 'model' => 'SIRIUS', 'criticality' => 'critical'],
        ['tag_number' => 'M-01', 'area' => 'MCC', 'type' => 'MOTOR', 'description' => 'Motor penggerak pompa loading', 'manufacturer' => 'SIEMENS', 'model' => '1LE1', 'criticality' => 'high'],
        ['tag_number' => 'FP-01', 'area' => 'STORAGE', 'type' => 'FIRE-PUMP', 'description' => 'Fire pump utama', 'manufacturer' => 'GRUNDFOS', 'model' => 'NK', 'criticality' => 'critical'],
        ['tag_number' => 'G-01', 'area' => 'MCC', 'type' => 'GENSET', 'description' => 'Genset cadangan', 'manufacturer' => 'CUMMINS', 'model' => 'C200', 'criticality' => 'critical'],
    ];

    public function run(): void
    {
        foreach (self::EQUIPMENT as $item) {
            $area = Area::where('code', $item['area'])->firstOrFail();
            $type = EquipmentType::where('code', $item['type'])->firstOrFail();

            Equipment::firstOrCreate(
                ['tag_number' => $item['tag_number']],
                [
                    'area_id' => $area->id,
                    'equipment_type_id' => $type->id,
                    'description' => $item['description'],
                    'manufacturer' => $item['manufacturer'],
                    'model' => $item['model'],
                    'serial_number' => 'SN-'.$item['tag_number'],
                    'installation_date' => now()->subYear(),
                    'operational_unit' => 'hour',
                    'status' => 'active',
                    'criticality' => $item['criticality'],
                ],
            );
        }

        $this->command?->info('Equipment siap: '.implode(', ', array_column(self::EQUIPMENT, 'tag_number')));
    }
}
