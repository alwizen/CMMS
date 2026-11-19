<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::firstOrCreate(
            ['code' => 'FT-TGL'],
            [
                'name' => 'FT Tegal',
                'description' => 'Fasilitas Teknik Tegal',
                'is_active' => true,
            ],
        );
    }
}
