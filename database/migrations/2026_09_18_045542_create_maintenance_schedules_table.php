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
        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maintenance_plan_id');
            $table->unsignedBigInteger('equipment_id');
            $table->date('scheduled_date');
            $table->string('status', 30);
            $table->text('description')->nullable();
            $table->date('rescheduled_from')->nullable();
            $table->timestamps();
            $table->foreign('maintenance_plan_id')->references('id')->on('maintenance_plans')->onDelete('cascade');
            $table->foreign('equipment_id')->references('id')->on('equipment')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_schedules');
    }
};
