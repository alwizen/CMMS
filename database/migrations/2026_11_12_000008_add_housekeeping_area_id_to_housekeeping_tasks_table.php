<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('housekeeping_tasks', function (Blueprint $table) {
            $table->foreignId('housekeeping_area_id')
                ->nullable()
                ->after('company_id')
                ->constrained('housekeeping_areas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('housekeeping_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('housekeeping_area_id');
        });
    }
};
