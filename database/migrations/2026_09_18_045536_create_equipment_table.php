<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();

            $table->foreignId('area_id')
                ->constrained('areas')
                ->restrictOnDelete();

            $table->string('tag_number', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();

            $table->string('equipment_type', 100)->nullable();

            $table->string('manufacturer', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('serial_number', 100)->nullable();

            $table->date('installation_date')->nullable();

            $table->string('operational_unit', 20)->nullable();

            $table->string('photo')->nullable();

            $table->string('status', 30)->default('active');
            $table->string('criticality', 20)->default('medium');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
