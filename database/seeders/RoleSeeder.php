<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Role dasar yang dipakai aplikasi. Idempotent, aman dijalankan berulang.
     *
     * @var array<int, string>
     */
    public const ROLES = [
        'super_admin',
        'spv_mos',
        'pengawas',
        'ltsa_tad',
    ];

    public function run(): void
    {
        foreach (self::ROLES as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->command?->info('Role tersedia: '.implode(', ', self::ROLES));
    }
}
