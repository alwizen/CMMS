<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Status `On Hold` dihapus dari alur Work Order.
 *
 * WO yang masih tertunda dipindahkan ke `In Progress` karena pekerjaan sudah
 * dimulai dan performed by sudah tercatat.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('work_orders')
            ->where('status', 'On Hold')
            ->update([
                'status' => 'In Progress',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Tidak dikembalikan: status On Hold sudah tidak dipakai.
    }
};
