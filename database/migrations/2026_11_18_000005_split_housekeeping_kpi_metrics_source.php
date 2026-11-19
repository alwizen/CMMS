<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $legacy = DB::table('kpi_metrics')
            ->where('source', 'housekeeping')
            ->get();

        foreach ($legacy as $row) {
            foreach (['housekeeping_indoor', 'housekeeping_outdoor'] as $source) {
                $exists = DB::table('kpi_metrics')
                    ->where('source', $source)
                    ->where('key', $row->key)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('kpi_metrics')->insert([
                    'source' => $source,
                    'key' => $row->key,
                    'target_value' => $row->target_value,
                    'weight' => $row->weight,
                    'is_active' => $row->is_active,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        DB::table('kpi_metrics')
            ->where('source', 'housekeeping')
            ->delete();
    }

    public function down(): void
    {
        // Tidak mengembalikan baris lama agar konfigurasi per kelas tetap utuh.
    }
};
