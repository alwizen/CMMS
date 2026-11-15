<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('maintenance_plans')
            ->select('id')
            ->orderBy('id')
            ->each(function ($plan): void {
                $interval = DB::table('maintenance_plan_activities')
                    ->join('activities', 'activities.id', '=', 'maintenance_plan_activities.activity_id')
                    ->where('maintenance_plan_activities.maintenance_plan_id', $plan->id)
                    ->orderBy('maintenance_plan_activities.sort_order')
                    ->orderBy('maintenance_plan_activities.id')
                    ->value('activities.interval');

                if (blank($interval)) {
                    return;
                }

                DB::table('maintenance_plans')
                    ->where('id', $plan->id)
                    ->update(['interval' => strtolower(trim((string) $interval))]);
            });
    }

    public function down(): void
    {
        // Backfill bersifat satu arah; tidak ada rollback data.
    }
};
