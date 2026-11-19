<?php

namespace Database\Seeders;

use App\Models\EquipmentType;
use Illuminate\Database\Seeder;

class EquipmentTypeSeeder extends Seeder
{
    /**
     * Master equipment type beserta fungsinya.
     * Fungsi dipakai sebagai penanggung jawab & filter pada Generate WO.
     *
     * @var array<int, array{code: string, name: string, fungsi: string, description: string}>
     */
    public const TYPES = [
        ['code' => 'PUMP', 'name' => 'Pump', 'fungsi' => 'MOS LTSA', 'description' => 'Pompa untuk distribusi fluida'],
        ['code' => 'COMPRESSOR', 'name' => 'Compressor', 'fungsi' => 'MOS PM Reborn', 'description' => 'Kompresor udara'],
        ['code' => 'TANK', 'name' => 'Storage Tank', 'fungsi' => 'MOS TAD', 'description' => 'Tangki penyimpanan'],
        ['code' => 'PANEL', 'name' => 'Panel', 'fungsi' => 'HSSE', 'description' => 'Panel listrik kontrol'],
        ['code' => 'VALVE', 'name' => 'Valve', 'fungsi' => 'MOS LTSA', 'description' => 'Katup/klep kontrol'],
        ['code' => 'MOTOR', 'name' => 'Motor', 'fungsi' => 'MOS PM Reborn', 'description' => 'Motor penggerak'],
        ['code' => 'FIRE-PUMP', 'name' => 'Fire Pump', 'fungsi' => 'HSSE', 'description' => 'Pompa pemadam kebakaran'],
        ['code' => 'GENSET', 'name' => 'Genset', 'fungsi' => 'MOS TAD', 'description' => 'Generator Set'],
    ];

    public function run(): void
    {
        foreach (self::TYPES as $type) {
            EquipmentType::firstOrCreate(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'fungsi' => $type['fungsi'],
                    'description' => $type['description'],
                    'status' => true,
                ],
            );
        }

        $this->command?->info('Equipment type siap: '.implode(', ', array_column(self::TYPES, 'code')));
    }
}
