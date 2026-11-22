<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Dataset bersih untuk testing maintenance:
     * master (company, area, equipment type, equipment, checklist) + user.
     * Belum ada work order / request / downtime supaya bisa dites dari nol.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            FieldPermissionSeeder::class,
            UserSeeder::class,
            CompanySeeder::class,
            AreaSeeder::class,
            EquipmentTypeSeeder::class,
            EquipmentSeeder::class,
            ActivitySeeder::class,
            HousekeepingSeeder::class,
        ]);
    }
}
