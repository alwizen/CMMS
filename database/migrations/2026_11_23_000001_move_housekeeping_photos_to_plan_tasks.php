<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foto before/after kembali menempel pada tiap item checklist
     * (`housekeeping_plan_tasks`), bukan lagi per fasilitas. Tabel
     * dokumentasi per fasilitas dihapus.
     *
     * Data foto lama sengaja tidak dipindahkan karena modul masih dalam
     * tahap percobaan.
     */
    public function up(): void
    {
        if (Schema::hasTable('housekeeping_plan_tasks')) {
            Schema::table('housekeeping_plan_tasks', function (Blueprint $table): void {
                if (! Schema::hasColumn('housekeeping_plan_tasks', 'photo_before')) {
                    $table->json('photo_before')->nullable()->after('status');
                }

                if (! Schema::hasColumn('housekeeping_plan_tasks', 'photo_after')) {
                    $table->json('photo_after')->nullable()->after('photo_before');
                }
            });

            if (Schema::hasColumn('housekeeping_plan_tasks', 'housekeeping_plan_facility_id')) {
                Schema::table('housekeeping_plan_tasks', function (Blueprint $table): void {
                    $table->dropConstrainedForeignId('housekeeping_plan_facility_id');
                });
            }
        }

        Schema::dropIfExists('housekeeping_plan_facilities');
    }

    public function down(): void
    {
        if (! Schema::hasTable('housekeeping_plan_facilities')) {
            Schema::create('housekeeping_plan_facilities', function (Blueprint $table): void {
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
        }

        if (Schema::hasTable('housekeeping_plan_tasks')) {
            if (! Schema::hasColumn('housekeeping_plan_tasks', 'housekeeping_plan_facility_id')) {
                Schema::table('housekeeping_plan_tasks', function (Blueprint $table): void {
                    $table->foreignId('housekeeping_plan_facility_id')
                        ->nullable()
                        ->after('housekeeping_facility_id')
                        ->constrained('housekeeping_plan_facilities')
                        ->nullOnDelete();
                });
            }

            Schema::table('housekeeping_plan_tasks', function (Blueprint $table): void {
                $table->dropColumn(['photo_before', 'photo_after']);
            });
        }
    }
};
