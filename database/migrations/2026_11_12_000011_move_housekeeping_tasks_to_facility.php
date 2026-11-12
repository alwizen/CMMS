<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('housekeeping_tasks', function (Blueprint $table) {
            $table->foreignId('housekeeping_facility_id')
                ->nullable()
                ->after('id')
                ->constrained('housekeeping_facilities')
                ->nullOnDelete();
        });

        Schema::table('housekeeping_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('housekeeping_area_id');
        });

        Schema::table('housekeeping_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });
    }

    public function down(): void
    {
        Schema::table('housekeeping_tasks', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();
        });

        Schema::table('housekeeping_tasks', function (Blueprint $table) {
            $table->foreignId('housekeeping_area_id')
                ->nullable()
                ->after('company_id')
                ->constrained('housekeeping_areas')
                ->nullOnDelete();
        });

        Schema::table('housekeeping_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('housekeeping_facility_id');
        });
    }
};
