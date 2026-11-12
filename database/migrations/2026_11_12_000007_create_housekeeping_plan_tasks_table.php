<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housekeeping_plan_tasks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('housekeeping_plan_id')
                ->constrained('housekeeping_plans')
                ->cascadeOnDelete();

            $table->foreignId('housekeeping_task_id')
                ->nullable()
                ->constrained('housekeeping_tasks')
                ->nullOnDelete();

            $table->foreignId('housekeeping_facility_id')
                ->nullable()
                ->constrained('housekeeping_facilities')
                ->nullOnDelete();

            $table->string('name', 150);
            $table->string('status', 20)->default('Pending');

            $table->json('photo_before')->nullable();
            $table->json('photo_after')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->text('note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('housekeeping_plan_tasks');
    }
};
