<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Area;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $ftTegal = Company::where('code', 'FT-TGL')->first();

        Area::create([
            'company_id' => $ftTegal->id,
            'code' => 'LOAD',
            'name' => 'Loading',
            'description' => 'Loading area untuk distribusi',
            'is_active' => true,
        ]);

        Area::create([
            'company_id' => $ftTegal->id,
            'code' => 'UNLOAD',
            'name' => 'Unloading',
            'description' => 'Unloading area',
            'is_active' => true,
        ]);

        Area::create([
            'company_id' => $ftTegal->id,
            'code' => 'STORAGE',
            'name' => 'Storage',
            'description' => 'Area penyimpanan',
            'is_active' => true,
        ]);

        Area::create([
            'company_id' => $ftTegal->id,
            'code' => 'MCC',
            'name' => 'MCC',
            'description' => 'Motor Control Center',
            'is_active' => true,
        ]);
    }
}
