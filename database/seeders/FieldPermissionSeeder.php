<?php

namespace Database\Seeders;

use App\Support\FieldPanelPermissions;
use Illuminate\Database\Seeder;

class FieldPermissionSeeder extends Seeder
{
    public function run(): void
    {
        FieldPanelPermissions::sync();

        $this->command?->info('Permission panel lapangan tersinkron.');
    }
}
