<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill teknisi pelaksana untuk WO lama.
 *
 * Sebelum fitur ini ada, performed by (technician_coordinator_id) tidak
 * dicatat pada tabel work_order_workers, sehingga halaman detail WO
 * menampilkan daftar pelaksana kosong.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $workOrders = DB::table('work_orders')
            ->whereNotNull('technician_coordinator_id')
            ->orderBy('id')
            ->get(['id', 'technician_coordinator_id', 'status', 'start_at', 'finish_at', 'created_at']);

        foreach ($workOrders as $workOrder) {
            $startedAt = $workOrder->start_at ?? $workOrder->created_at ?? $now;

            $finishedAt = in_array($workOrder->status, ['Completed', 'Cancelled'], true)
                ? ($workOrder->finish_at ?? $startedAt)
                : null;

            // Data lama bisa punya finish_at sebelum start_at; jaga konsistensi.
            if ($finishedAt !== null && $startedAt > $finishedAt) {
                $startedAt = $finishedAt;
            }

            // Baris sudah ada tetapi waktu mulai/selesai belum konsisten.
            $existing = DB::table('work_order_workers')
                ->where('work_order_id', $workOrder->id)
                ->where('user_id', $workOrder->technician_coordinator_id)
                ->first(['id', 'started_at', 'finished_at']);

            if ($existing === null) {
                DB::table('work_order_workers')->insert([
                    'work_order_id' => $workOrder->id,
                    'user_id' => $workOrder->technician_coordinator_id,
                    'role' => 'Teknisi',
                    'started_at' => $startedAt,
                    'finished_at' => $finishedAt,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                continue;
            }

            $updates = ['updated_at' => $now];

            if ($existing->finished_at === null && $finishedAt !== null) {
                $updates['finished_at'] = $finishedAt;
            }

            $effectiveFinished = $updates['finished_at'] ?? $existing->finished_at;

            // Waktu mulai tidak boleh setelah waktu selesai.
            if (
                $effectiveFinished !== null
                && $existing->started_at !== null
                && $existing->started_at > $effectiveFinished
            ) {
                $updates['started_at'] = $startedAt;
            }

            if (count($updates) > 1) {
                DB::table('work_order_workers')
                    ->where('id', $existing->id)
                    ->update($updates);
            }
        }
    }

    public function down(): void
    {
        // Backfill tidak dibatalkan agar jejak pelaksana tetap tersimpan.
    }
};
