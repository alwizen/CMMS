<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('housekeeping_areas', function (Blueprint $table) {
            $table->foreignId('supervisor_id')
                ->nullable()
                ->after('company_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('housekeeping_plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supervisor_id');
        });
    }

    public function down(): void
    {
        Schema::table('housekeeping_plans', function (Blueprint $table) {
            $table->foreignId('supervisor_id')
                ->nullable()
                ->after('plan_date')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('housekeeping_areas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supervisor_id');
        });
    }
};
