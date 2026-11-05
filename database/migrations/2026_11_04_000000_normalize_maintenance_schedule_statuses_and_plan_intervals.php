<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Normalisasi data lama agar konsisten dengan konstanta status & interval
     * yang dipakai aplikasi (MaintenanceSchedule::STATUSES, Activity::INTERVALS).
     */
    public function up(): void
    {
        $statusMap = [
            'open' => 'Scheduled',
            'pending' => 'Scheduled',
            'scheduled' => 'Scheduled',
            'in progress' => 'In Progress',
            'in_progress' => 'In Progress',
            'done' => 'Completed',
            'completed' => 'Completed',
            'delayed' => 'Delayed',
            'rescheduled' => 'Rescheduled',
            'cancelled' => 'Cancelled',
            'canceled' => 'Cancelled',
        ];

        DB::table('maintenance_schedules')
            ->select('id', 'status')
            ->orderBy('id')
            ->each(function ($schedule) use ($statusMap): void {
                $current = strtolower(trim((string) $schedule->status));
                $normalized = $statusMap[$current] ?? null;

                if ($normalized !== null && $normalized !== $schedule->status) {
                    DB::table('maintenance_schedules')
                        ->where('id', $schedule->id)
                        ->update(['status' => $normalized]);
                }
            });

        DB::table('maintenance_plans')
            ->select('id', 'interval')
            ->orderBy('id')
            ->each(function ($plan): void {
                if (blank($plan->interval)) {
                    return;
                }

                $normalized = strtolower(str_replace([' ', '-'], '_', trim((string) $plan->interval)));

                if ($normalized !== $plan->interval) {
                    DB::table('maintenance_plans')
                        ->where('id', $plan->id)
                        ->update(['interval' => $normalized]);
                }
            });
    }

    public function down(): void
    {
        // Normalisasi bersifat satu arah; tidak ada rollback data.
    }
};
