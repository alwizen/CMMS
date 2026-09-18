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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('equipment_type_id');
            $table->string('name', 150);
            $table->string('type', 50);
            $table->string('maintenance_classification', 30);
            $table->string('interval', 30)->nullable();
            $table->string('answer_type', 30);
            $table->text('reference')->nullable();
            $table->decimal('optimum', 12, 3)->nullable();
            $table->decimal('minimum', 12, 3)->nullable();
            $table->decimal('maximum', 12, 3)->nullable();
            $table->string('unit', 30)->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->foreign('equipment_type_id')->references('id')->on('equipment_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
