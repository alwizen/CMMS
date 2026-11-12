<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housekeeping_facilities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('housekeeping_area_id')
                ->constrained('housekeeping_areas')
                ->cascadeOnDelete();

            $table->string('code', 20)->nullable();
            $table->string('name', 100);
            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('housekeeping_facilities');
    }
};
