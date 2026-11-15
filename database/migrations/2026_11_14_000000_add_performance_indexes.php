<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_schedules', function (Blueprint $table) {
            $table->index('scheduled_date', 'maintenance_schedules_scheduled_date_index');
            $table->index(['status', 'scheduled_date'], 'maintenance_schedules_status_scheduled_date_index');
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->index('status', 'work_orders_status_index');
            $table->index('start_at', 'work_orders_start_at_index');
        });

        Schema::table('maintenance_plans', function (Blueprint $table) {
            $table->index('status', 'maintenance_plans_status_index');
        });

        Schema::table('housekeeping_plans', function (Blueprint $table) {
            $table->index('plan_date', 'housekeeping_plans_plan_date_index');
            $table->index('status', 'housekeeping_plans_status_index');
        });

        Schema::table('housekeeping_plan_tasks', function (Blueprint $table) {
            $table->index('status', 'housekeeping_plan_tasks_status_index');
            $table->index('created_at', 'housekeeping_plan_tasks_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_schedules', function (Blueprint $table) {
            $table->dropIndex('maintenance_schedules_scheduled_date_index');
            $table->dropIndex('maintenance_schedules_status_scheduled_date_index');
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropIndex('work_orders_status_index');
            $table->dropIndex('work_orders_start_at_index');
        });

        Schema::table('maintenance_plans', function (Blueprint $table) {
            $table->dropIndex('maintenance_plans_status_index');
        });

        Schema::table('housekeeping_plans', function (Blueprint $table) {
            $table->dropIndex('housekeeping_plans_plan_date_index');
            $table->dropIndex('housekeeping_plans_status_index');
        });

        Schema::table('housekeeping_plan_tasks', function (Blueprint $table) {
            $table->dropIndex('housekeeping_plan_tasks_status_index');
            $table->dropIndex('housekeeping_plan_tasks_created_at_index');
        });
    }
};
