<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('housekeeping_areas', function (Blueprint $table) {
            $table->unsignedSmallInteger('sla_days')
                ->default(1)
                ->after('area_size');
        });
    }

    public function down(): void
    {
        Schema::table('housekeeping_areas', function (Blueprint $table) {
            $table->dropColumn('sla_days');
        });
    }
};
