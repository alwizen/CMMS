<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@admin.com',
            'password' => bcrypt('password'),
            'employee_number' => 'ADM-001',
            'phone' => '081234567890',
            'status' => true,
        ]);

        User::create([
            'name' => 'Technician 1',
            'email' => 'tech1@ft-tegal.com',
            'password' => bcrypt('password'),
            'employee_number' => 'TECH-001',
            'phone' => '081234567891',
            'status' => true,
        ]);

        User::create([
            'name' => 'Technician 2',
            'email' => 'tech2@ft-tegal.com',
            'password' => bcrypt('password'),
            'employee_number' => 'TECH-002',
            'phone' => '081234567892',
            'status' => true,
        ]);

        User::create([
            'name' => 'Supervisor',
            'email' => 'supervisor@ft-tegal.com',
            'password' => bcrypt('password'),
            'employee_number' => 'SUP-001',
            'phone' => '081234567893',
            'status' => true,
        ]);
    }
}
