<?php

namespace Database\Seeders;

use App\Models\EquipmentType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EquipmentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'PUMP', 'name' => 'Pump', 'description' => 'Pompa untuk distribusi fluida'],
            ['code' => 'TANK', 'name' => 'Storage Tank', 'description' => 'Tangki penyimpanan'],
            ['code' => 'COMPRESSOR', 'name' => 'Compressor', 'description' => 'Kompresor udara'],
            ['code' => 'GENSET', 'name' => 'Genset', 'description' => 'Generator Set'],
            ['code' => 'PANEL', 'name' => 'Panel', 'description' => 'Panel listrik kontrol'],
            ['code' => 'FIRE-PUMP', 'name' => 'Fire Pump', 'description' => 'Pompa pemadam kebakaran'],
            ['code' => 'MOTOR', 'name' => 'Motor', 'description' => 'Motor penggerak'],
            ['code' => 'VALVE', 'name' => 'Valve', 'description' => 'Katup/klep kontrol'],
            ['code' => 'METER', 'name' => 'Flow Meter', 'description' => 'Meter pengukur aliran'],
            ['code' => 'TRANSMITTER', 'name' => 'Level Transmitter', 'description' => 'Transmitter level'],
        ];

        foreach ($types as $type) {
            EquipmentType::create([
                'code' => $type['code'],
                'name' => $type['name'],
                'description' => $type['description'],
                'status' => true,
            ]);
        }
    }
}
