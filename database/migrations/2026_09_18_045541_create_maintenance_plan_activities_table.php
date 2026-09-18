<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maintenance_plan_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maintenance_plan_id');
            $table->unsignedBigInteger('activity_id');
            $table->unsignedSmallInteger('sort_order');
            $table->timestamps();
            $table->foreign('maintenance_plan_id')->references('id')->on('maintenance_plans')->onDelete('cascade');
            $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_plan_activities');
    }
};
