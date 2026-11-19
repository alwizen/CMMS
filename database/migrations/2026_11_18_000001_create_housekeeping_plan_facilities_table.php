<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('housekeeping_plan_facilities')) {
            Schema::create('housekeeping_plan_facilities', function (Blueprint $table) {
                $table->id();

                $table->foreignId('housekeeping_plan_id')
                    ->constrained('housekeeping_plans')
                    ->cascadeOnDelete();

                $table->foreignId('housekeeping_facility_id')
                    ->nullable()
                    ->constrained('housekeeping_facilities')
                    ->nullOnDelete();

                $table->json('photo_before')->nullable();
                $table->json('photo_after')->nullable();

                $table->timestamps();

                $table->unique(['housekeeping_plan_id', 'housekeeping_facility_id'], 'hk_plan_facility_unique');
            });

            return;
        }

        // Tabel mungkin sudah dibuat manual / migrasi sebelumnya gagal di tengah.
        // Pastikan unique index (plan, facility) tetap ada karena kode memakai firstOrCreate.
        $hasUnique = collect(Schema::getIndexes('housekeeping_plan_facilities'))
            ->contains(fn (array $index): bool => ($index['unique'] ?? false)
                && $index['columns'] === ['housekeeping_plan_id', 'housekeeping_facility_id']);

        if (! $hasUnique) {
            Schema::table('housekeeping_plan_facilities', function (Blueprint $table) {
                $table->unique(['housekeeping_plan_id', 'housekeeping_facility_id'], 'hk_plan_facility_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('housekeeping_plan_facilities');
    }
};
