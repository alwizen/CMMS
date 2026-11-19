<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Petugas housekeeping (user dengan role patra-jasa) yang ditugaskan ke plan.
        Schema::create('housekeeping_plan_users', function (Blueprint $table) {
            $table->id();

            $table->foreignId('housekeeping_plan_id')
                ->constrained('housekeeping_plans')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['housekeeping_plan_id', 'user_id'],
                'hk_plan_user_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('housekeeping_plan_users');
    }
};
