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
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('equipment_id');
            $table->string('request_number', 50)->unique();
            $table->unsignedBigInteger('reported_by');
            $table->string('operation_status', 30);
            $table->text('description');
            $table->date('damage_date');
            $table->time('damage_time')->nullable();
            $table->text('equipment_condition')->nullable();
            $table->text('impact')->nullable();
            $table->text('early_action')->nullable();
            $table->string('status', 30);
            $table->timestamps();
            $table->foreign('equipment_id')->references('id')->on('equipment')->onDelete('cascade');
            $table->foreign('reported_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};
