<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fasilitas housekeeping dapat digambar polygon-nya sendiri. Batas area
     * (polygon area) dipakai sebagai acuan saat menggambar.
     */
    public function up(): void
    {
        Schema::table('housekeeping_facilities', function (Blueprint $table): void {
            if (! Schema::hasColumn('housekeeping_facilities', 'polygon')) {
                $table->json('polygon')->nullable()->after('description');
            }

            if (! Schema::hasColumn('housekeeping_facilities', 'area_size')) {
                $table->decimal('area_size', 14, 2)->nullable()->after('polygon');
            }

            if (! Schema::hasColumn('housekeeping_facilities', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('area_size');
            }

            if (! Schema::hasColumn('housekeeping_facilities', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('housekeeping_facilities', function (Blueprint $table): void {
            $table->dropColumn(['polygon', 'area_size', 'latitude', 'longitude']);
        });
    }
};
