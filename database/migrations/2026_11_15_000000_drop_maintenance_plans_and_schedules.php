<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropForeign(['maintenance_schedule_id']);
            $table->dropForeign(['maintenance_plan_id']);
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropUnique(['maintenance_schedule_id']);
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn(['maintenance_schedule_id', 'maintenance_plan_id']);
        });

        Schema::dropIfExists('maintenance_plan_activities');
        Schema::dropIfExists('maintenance_schedules');
        Schema::dropIfExists('maintenance_plans');
    }

    public function down(): void
    {
        // Tidak ada rollback: fitur plan & schedule dihapus permanen.
    }
};
