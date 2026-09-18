<?php

namespace Database\Seeders;

use App\Models\MaintenanceHistory;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MaintenanceHistorySeeder extends Seeder
{
    public function run(): void
    {
        $equipments = Equipment::all();
        $users = User::where('email', '!=', 'admin@ft-tegal.com')->get();

        $maintenanceTypes = [
            'Preventive Maintenance',
            'Corrective Maintenance',
            'Inspection',
            'Cleaning',
            'Testing',
            'Repair',
        ];

        $findings = [
            'Equipment dalam kondisi baik, tidak ada masalah ditemukan.',
            'Ditemukan sedikit keausan pada bearing, tetap dalam batas toleransi.',
            'Perlu penjadwalan pemeliharaan berikutnya dalam 2 minggu.',
            'Semua parameter dalam rentang yang dapat diterima.',
            'Rekomendasi penggantian komponen dalam waktu dekat.',
            'Fungsi operasional normal, tidak ada anomali.',
        ];

        $actionsTaken = [
            'Pengecekan oli dan pelumasan selesai.',
            'Pembersihan dan penyetelan ulang dilakukan.',
            'Komponen diganti sesuai jadwal pemeliharaan.',
            'Perbaikan kecil dilakukan, tested dan disetujui.',
            'Inspeksi menyeluruh dilakukan, dokumentasi lengkap.',
            'Pengujian fungsional berhasil, semua kriteria terpenuhi.',
        ];

        foreach ($equipments as $equipment) {
            for ($i = 0; $i < 5; $i++) {
                MaintenanceHistory::create([
                    'equipment_id' => $equipment->id,
                    'maintenance_type' => $maintenanceTypes[array_rand($maintenanceTypes)],
                    'classification' => rand(0, 1) ? 'Preventive' : 'Corrective',
                    'description' => 'Pemeliharaan rutin untuk ' . $equipment->name,
                    'maintenance_date' => now()->subDays(rand(1, 90)),
                    'performed_by' => $users->random()->id,
                    'findings' => $findings[array_rand($findings)],
                    'actions_taken' => $actionsTaken[array_rand($actionsTaken)],
                    'status' => 'Completed',
                    'duration_minutes' => rand(30, 240),
                    'notes' => 'Pemeliharaan rutin berdasarkan jadwal yang dijadwalkan.',
                ]);
            }
        }
    }
}
