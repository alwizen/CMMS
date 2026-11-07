<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('prefix', 120)->unique();
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
        });

        foreach (['work_orders' => 'maintenance_schedule_id'] as $table => $column) {
            $duplicates = DB::table($table)
                ->select($column)
                ->whereNotNull($column)
                ->groupBy($column)
                ->havingRaw('count(*) > 1')
                ->pluck($column);

            if ($duplicates->isNotEmpty()) {
                DB::table($table)
                    ->whereIn($column, $duplicates)
                    ->orderByDesc('id')
                    ->update([$column => null]);
            }
        }

        Schema::table('work_orders', function (Blueprint $table) {
            $table->unique('maintenance_schedule_id');
        });

        Schema::table('work_order_activities', function (Blueprint $table) {
            $table->dropForeign(['activity_id']);
        });

        Schema::table('work_order_activities', function (Blueprint $table) {
            $table->unsignedBigInteger('activity_id')->nullable()->change();
            $table->foreign('activity_id')->references('id')->on('activities')->nullOnDelete();
        });

        Schema::table('maintenance_plan_activities', function (Blueprint $table) {
            $table->dropForeign(['activity_id']);
        });

        Schema::table('maintenance_plan_activities', function (Blueprint $table) {
            $table->foreign('activity_id')->references('id')->on('activities')->restrictOnDelete();
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['equipment_type_id']);
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->foreign('equipment_type_id')->references('id')->on('equipment_types')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['equipment_type_id']);
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->foreign('equipment_type_id')->references('id')->on('equipment_types')->cascadeOnDelete();
        });

        Schema::table('maintenance_plan_activities', function (Blueprint $table) {
            $table->dropForeign(['activity_id']);
        });

        Schema::table('maintenance_plan_activities', function (Blueprint $table) {
            $table->foreign('activity_id')->references('id')->on('activities')->cascadeOnDelete();
        });

        Schema::table('work_order_activities', function (Blueprint $table) {
            $table->dropForeign(['activity_id']);
        });

        Schema::table('work_order_activities', function (Blueprint $table) {
            $table->unsignedBigInteger('activity_id')->nullable(false)->change();
            $table->foreign('activity_id')->references('id')->on('activities')->cascadeOnDelete();
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropUnique(['maintenance_schedule_id']);
        });

        Schema::dropIfExists('document_sequences');
    }
};
