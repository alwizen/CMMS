<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Override target/bobot metrik KPI. Definisi metrik tetap di kode
        // (App\Support\Kpi\KpiRegistry), tabel ini hanya menyimpan penyesuaian.
        Schema::create('kpi_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('source', 20);
            $table->string('key', 60);
            $table->decimal('target_value', 12, 2)->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['source', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_metrics');
    }
};
