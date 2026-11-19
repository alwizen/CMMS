<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('housekeeping_plan_tasks', 'housekeeping_plan_facility_id')) {
            Schema::table('housekeeping_plan_tasks', function (Blueprint $table) {
                $table->foreignId('housekeeping_plan_facility_id')
                    ->nullable()
                    ->after('housekeeping_facility_id')
                    ->constrained('housekeeping_plan_facilities')
                    ->nullOnDelete();
            });
        }

        $tasks = DB::table('housekeeping_plan_tasks')
            ->whereNotNull('housekeeping_facility_id')
            ->select('id', 'housekeeping_plan_id', 'housekeeping_facility_id')
            ->get();

        foreach ($tasks as $task) {
            $planFacilityId = DB::table('housekeeping_plan_facilities')
                ->where('housekeeping_plan_id', $task->housekeeping_plan_id)
                ->where('housekeeping_facility_id', $task->housekeeping_facility_id)
                ->value('id');

            if ($planFacilityId) {
                DB::table('housekeeping_plan_tasks')
                    ->where('id', $task->id)
                    ->update(['housekeeping_plan_facility_id' => $planFacilityId]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('housekeeping_plan_tasks', 'housekeeping_plan_facility_id')) {
            return;
        }

        Schema::table('housekeeping_plan_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('housekeeping_plan_facility_id');
        });
    }
};
