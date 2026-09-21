<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Area;
use Illuminate\Database\Seeder;

class EquipmentSeeder extends Seeder
{
    public function run(): void
    {
        $loading = Area::where('code', 'LOAD')->first();
        $unloading = Area::where('code', 'UNLOAD')->first();
        $storage = Area::where('code', 'STORAGE')->first();
        $mcc = Area::where('code', 'MCC')->first();

        $pump = EquipmentType::where('code', 'PUMP')->first();
        $meter = EquipmentType::where('code', 'METER')->first();
        $valve = EquipmentType::where('code', 'VALVE')->first();
        $tank = EquipmentType::where('code', 'TANK')->first();
        $transmitter = EquipmentType::where('code', 'TRANSMITTER')->first();
        $panel = EquipmentType::where('code', 'PANEL')->first();

        Equipment::create([
            'area_id' => $loading->id,
            'equipment_type_id' => $pump->id,
            'tag_number' => 'P-101',
            'name' => 'Pump',
            'description' => 'Main loading pump',
            'manufacturer' => 'GRUNDFOS',
            'model' => 'CR32',
            'serial_number' => 'SN-P-101',
            'installation_date' => now()->subYear(),
            'operational_unit' => 'hour',
            'status' => 'active',
            'criticality' => 'critical',
        ]);

        Equipment::create([
            'area_id' => $unloading->id,
            'equipment_type_id' => $meter->id,
            'tag_number' => 'FM-101',
            'name' => 'Flow Meter',
            'description' => 'Flow measurement device',
            'manufacturer' => 'SIEMENS',
            'model' => 'SITRANS F M',
            'serial_number' => 'SN-FM-101',
            'installation_date' => now()->subMonths(6),
            'status' => 'active',
            'criticality' => 'high',
        ]);

        Equipment::create([
            'area_id' => $unloading->id,
            'equipment_type_id' => $valve->id,
            'tag_number' => 'MOV-101',
            'name' => 'Motor Operated Valve',
            'description' => 'Automated control valve',
            'manufacturer' => 'ASCO',
            'model' => 'EF8320',
            'serial_number' => 'SN-MOV-101',
            'installation_date' => now()->subMonths(8),
            'status' => 'active',
            'criticality' => 'high',
        ]);

        Equipment::create([
            'area_id' => $storage->id,
            'equipment_type_id' => $tank->id,
            'tag_number' => 'TK-01',
            'name' => 'Storage Tank',
            'description' => 'Main storage tank',
            'manufacturer' => 'VESTOIL',
            'model' => 'STD-50000',
            'serial_number' => 'SN-TK-01',
            'installation_date' => now()->subYears(2),
            'status' => 'active',
            'criticality' => 'critical',
        ]);

        Equipment::create([
            'area_id' => $storage->id,
            'equipment_type_id' => $tank->id,
            'tag_number' => 'TK-02',
            'name' => 'Storage Tank Secondary',
            'description' => 'Secondary storage tank',
            'manufacturer' => 'VESTOIL',
            'model' => 'STD-50000',
            'serial_number' => 'SN-TK-02',
            'installation_date' => now()->subYears(1),
            'status' => 'active',
            'criticality' => 'high',
        ]);

        Equipment::create([
            'area_id' => $storage->id,
            'equipment_type_id' => $transmitter->id,
            'tag_number' => 'LT-01',
            'name' => 'Level Transmitter',
            'description' => 'Tank level measurement',
            'manufacturer' => 'SIEMENS',
            'model' => 'SITRANS LVL',
            'serial_number' => 'SN-LT-01',
            'installation_date' => now()->subMonths(10),
            'status' => 'active',
            'criticality' => 'high',
        ]);

        Equipment::create([
            'area_id' => $mcc->id,
            'equipment_type_id' => $panel->id,
            'tag_number' => 'MCC-01',
            'name' => 'Main MCC Panel',
            'description' => 'Main control center panel',
            'manufacturer' => 'SIEMENS',
            'model' => 'SIRIUS',
            'serial_number' => 'SN-MCC-01',
            'installation_date' => now()->subYears(3),
            'status' => 'active',
            'criticality' => 'critical',
        ]);

        Equipment::create([
            'area_id' => $mcc->id,
            'equipment_type_id' => $panel->id,
            'tag_number' => 'MCC-02',
            'name' => 'Pump MCC Panel',
            'description' => 'Pump control panel',
            'manufacturer' => 'SIEMENS',
            'model' => 'SIRIUS',
            'serial_number' => 'SN-MCC-02',
            'installation_date' => now()->subYears(2),
            'status' => 'active',
            'criticality' => 'high',
        ]);
    }
}
