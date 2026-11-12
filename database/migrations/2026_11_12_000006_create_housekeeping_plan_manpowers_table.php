<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housekeeping_plan_manpowers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('housekeeping_plan_id')
                ->constrained('housekeeping_plans')
                ->cascadeOnDelete();

            $table->foreignId('housekeeping_manpower_id')
                ->constrained('housekeeping_manpowers')
                ->cascadeOnDelete();

            $table->text('note')->nullable();

            $table->timestamps();

            $table->unique(
                ['housekeeping_plan_id', 'housekeeping_manpower_id'],
                'hk_plan_manpower_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('housekeeping_plan_manpowers');
    }
};
