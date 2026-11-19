<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('housekeeping_areas', function (Blueprint $table) {
            $table->string('kpi_class', 20)
                ->default('outdoor')
                ->after('sla_days');
        });
    }

    public function down(): void
    {
        Schema::table('housekeeping_areas', function (Blueprint $table) {
            $table->dropColumn('kpi_class');
        });
    }
};
