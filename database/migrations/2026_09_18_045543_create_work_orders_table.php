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
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('work_order_number', 50)->unique();
            $table->unsignedBigInteger('equipment_id');
            $table->unsignedBigInteger('maintenance_plan_id')->nullable();
            $table->unsignedBigInteger('maintenance_schedule_id')->nullable();
            $table->unsignedBigInteger('maintenance_request_id')->nullable();
            $table->unsignedBigInteger('issued_by');
            $table->unsignedBigInteger('technician_coordinator_id')->nullable();
            $table->string('classification', 30);
            $table->string('interval', 30)->nullable();
            $table->dateTime('start_at');
            $table->dateTime('finish_at')->nullable();
            $table->text('note')->nullable();
            $table->string('status', 30);
            $table->timestamps();
            $table->foreign('equipment_id')->references('id')->on('equipment')->onDelete('cascade');
            $table->foreign('maintenance_plan_id')->references('id')->on('maintenance_plans')->onDelete('set null');
            $table->foreign('maintenance_schedule_id')->references('id')->on('maintenance_schedules')->onDelete('set null');
            $table->foreign('maintenance_request_id')->references('id')->on('maintenance_requests')->onDelete('set null');
            $table->foreign('issued_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('technician_coordinator_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
