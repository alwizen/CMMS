<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasPhotoColumns = Schema::hasColumn('housekeeping_plan_tasks', 'photo_before')
            || Schema::hasColumn('housekeeping_plan_tasks', 'photo_after');

        if (! $hasPhotoColumns) {
            return;
        }

        $pairs = DB::table('housekeeping_plan_tasks')
            ->select('housekeeping_plan_id', 'housekeeping_facility_id')
            ->distinct()
            ->get();

        foreach ($pairs as $pair) {
            $source = DB::table('housekeeping_plan_tasks')
                ->where('housekeeping_plan_id', $pair->housekeeping_plan_id)
                ->where(function ($query) use ($pair): void {
                    if ($pair->housekeeping_facility_id === null) {
                        $query->whereNull('housekeeping_facility_id');

                        return;
                    }

                    $query->where('housekeeping_facility_id', $pair->housekeeping_facility_id);
                })
                ->where(function ($query): void {
                    $query->whereNotNull('photo_before')
                        ->orWhereNotNull('photo_after');
                })
                ->orderBy('id')
                ->first();

            DB::table('housekeeping_plan_facilities')->insertOrIgnore([
                'housekeeping_plan_id' => $pair->housekeeping_plan_id,
                'housekeeping_facility_id' => $pair->housekeeping_facility_id,
                'photo_before' => $source?->photo_before,
                'photo_after' => $source?->photo_after,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('housekeeping_plan_tasks', function (Blueprint $table) {
            $table->dropColumn(['photo_before', 'photo_after']);
        });
    }

    public function down(): void
    {
        Schema::table('housekeeping_plan_tasks', function (Blueprint $table) {
            $table->json('photo_before')->nullable();
            $table->json('photo_after')->nullable();
        });
    }
};
