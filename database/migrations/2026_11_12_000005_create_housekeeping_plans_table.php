<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housekeeping_plans', function (Blueprint $table) {
            $table->id();

            $table->string('plan_number', 50)->nullable()->unique();

            $table->foreignId('housekeeping_area_id')
                ->constrained('housekeeping_areas')
                ->restrictOnDelete();

            $table->date('plan_date');

            $table->foreignId('supervisor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('status', 20)->default('Draft');
            $table->text('note')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('housekeeping_plans');
    }
};
