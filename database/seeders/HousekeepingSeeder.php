<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingFacility;
use App\Models\HousekeepingManpower;
use App\Support\Code;
use Illuminate\Database\Seeder;

class HousekeepingSeeder extends Seeder
{
    /**
     * Area housekeeping beserta manpower-nya.
     * Idempotent: aman dijalankan berulang.
     *
     * @var array<int, array{name: string, sla_days: int, kpi_class: string, manpowers: array<int, string>}>
     */
    public const AREAS = [
        [
            'name' => 'Penerimaan',
            'sla_days' => 14,
            'kpi_class' => 'outdoor',
            'manpowers' => [
                'TARWO',
                'AWALUDIN',
                'FARHAN WIDIDO',
                'SHAHRUL AMIR M',
            ],
        ],
        [
            'name' => 'Penimbunan',
            'sla_days' => 14,
            'kpi_class' => 'outdoor',
            'manpowers' => [
                'SLAMET KAMBALI',
                'IMAM PAOZI',
                'QOLBI SABILI',
                'IMAM TRI PURWANTO',
            ],
        ],
        [
            'name' => 'Penyaluran',
            'sla_days' => 14,
            'kpi_class' => 'outdoor',
            'manpowers' => [
                'ANGGI PUTRA LUKI',
                'FAJAR ISMAIL',
                'UJANG MARGONO',
                'WINGGI PRIYANTO',
            ],
        ],
        [
            'name' => 'TPS Barata',
            'sla_days' => 14,
            'kpi_class' => 'outdoor',
            'manpowers' => [
                'ANDI SEPTIONO',
                'BIMA BUDIYASA',
                'DICKY PRAYOGI',
                'ALPIN ABI H',
                'AMAR BAYU',
            ],
        ],
        [
            'name' => 'Area Lainnya',
            'sla_days' => 14,
            'kpi_class' => 'outdoor',
            'manpowers' => [
                'A HOFUR',
                'WAHYU FEBIYANTO',
                'RASMAN',
            ],
        ],
    ];

    public function run(): void
    {
        $company = Company::where('code', 'FT-TGL')->first()
            ?? Company::firstOrFail();

        foreach (self::AREAS as $definition) {
            $area = HousekeepingArea::firstOrCreate(
                ['company_id' => $company->id, 'code' => Code::fromName($definition['name'])],
                [
                    'name' => $definition['name'],
                    'description' => 'Area housekeeping '.$definition['name'],
                    'sla_days' => $definition['sla_days'],
                    'kpi_class' => $definition['kpi_class'],
                    'is_active' => true,
                ],
            );

            foreach ($definition['manpowers'] as $name) {
                HousekeepingManpower::firstOrCreate(
                    ['housekeeping_area_id' => $area->id, 'name' => $name],
                    ['is_active' => true],
                );
            }
        }

        $this->command?->info(sprintf(
            'Housekeeping siap: %d area, %d manpower, %d fasilitas.',
            HousekeepingArea::count(),
            HousekeepingManpower::count(),
            HousekeepingFacility::count(),
        ));
    }
}
