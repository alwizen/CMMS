<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Company;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('code', 'FT-TGL')->firstOrFail();

        $areas = [
            ['code' => 'LOAD', 'name' => 'Loading', 'description' => 'Loading area untuk distribusi'],
            ['code' => 'UNLOAD', 'name' => 'Unloading', 'description' => 'Unloading area'],
            ['code' => 'STORAGE', 'name' => 'Storage', 'description' => 'Area penyimpanan'],
            ['code' => 'MCC', 'name' => 'MCC', 'description' => 'Motor Control Center'],
        ];

        foreach ($areas as $area) {
            Area::firstOrCreate(
                ['company_id' => $company->id, 'code' => $area['code']],
                [
                    'name' => $area['name'],
                    'description' => $area['description'],
                    'is_active' => true,
                ],
            );
        }
    }
}
