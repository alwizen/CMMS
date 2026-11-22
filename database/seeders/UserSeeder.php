<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * User dasar untuk testing maintenance.
     * Password semua user: "password".
     *
     * @var array<int, array{name: string, email: string, employee_number: string, role: string, fungsi: ?string}>
     */
    public const USERS = [
        ['name' => 'Admin User', 'email' => 'admin@admin.com', 'employee_number' => 'ADM-001', 'role' => 'super_admin', 'fungsi' => null],
        ['name' => 'SPV MOS', 'email' => 'spv@demo.com', 'employee_number' => 'SPV-001', 'role' => 'spv_mos', 'fungsi' => null],
        ['name' => 'SPV GOS', 'email' => 'spvgos@demo.com', 'employee_number' => 'SPV-GOS-001', 'role' => 'spv_gos', 'fungsi' => null],
        ['name' => 'SPV HSSE', 'email' => 'spvhsse@demo.com', 'employee_number' => 'SPV-HSSE-001', 'role' => 'spv_hsse', 'fungsi' => null],
        ['name' => 'Pengawas Lapangan', 'email' => 'pengawas@demo.com', 'employee_number' => 'PGW-001', 'role' => 'pengawas', 'fungsi' => null],
        ['name' => 'Teknisi MOS LTSA', 'email' => 'ltsa@demo.com', 'employee_number' => 'TAD-001', 'role' => 'mos', 'fungsi' => 'MOS LTSA'],
        ['name' => 'Teknisi MOS PM Reborn', 'email' => 'pmreborn@demo.com', 'employee_number' => 'TAD-002', 'role' => 'mos', 'fungsi' => 'MOS PM Reborn'],
        ['name' => 'Teknisi MOS TAD', 'email' => 'tad@demo.com', 'employee_number' => 'TAD-003', 'role' => 'mos', 'fungsi' => 'MOS TAD'],
        ['name' => 'Petugas HSSE', 'email' => 'hsse@demo.com', 'employee_number' => 'HSSE-001', 'role' => 'mos', 'fungsi' => 'HSSE'],
        ['name' => 'Petugas Housekeeping', 'email' => 'petugas@demo.com', 'employee_number' => 'HK-001', 'role' => 'patra-jasa', 'fungsi' => null],
    ];

    public function run(): void
    {
        foreach (self::USERS as $definition) {
            $user = User::firstOrCreate(
                ['email' => $definition['email']],
                [
                    'name' => $definition['name'],
                    'password' => bcrypt('password'),
                    'employee_number' => $definition['employee_number'],
                    'phone' => '0812345678'.str_pad((string) random_int(0, 99), 2, '0', STR_PAD_LEFT),
                    'fungsi' => $definition['fungsi'],
                    'status' => true,
                ],
            );

            $user->forceFill([
                'name' => $definition['name'],
                'employee_number' => $definition['employee_number'],
                'fungsi' => $definition['fungsi'],
                'status' => true,
            ])->save();

            $user->syncRoles([$definition['role']]);
        }

        $this->command?->info('User siap: '.implode(', ', array_column(self::USERS, 'email')));
    }
}
