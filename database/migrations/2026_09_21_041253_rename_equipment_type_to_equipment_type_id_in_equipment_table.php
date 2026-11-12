<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->foreignId('equipment_type_id')
                ->nullable()
                ->after('description')
                ->constrained('equipment_types')
                ->nullOnDelete();
        });

        $this->migrateLegacyTypes();

        Schema::table('equipment', function (Blueprint $table) {
            $table->dropColumn('equipment_type');
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->string('equipment_type', 100)->nullable()->after('description');
        });

        $this->restoreLegacyTypes();

        Schema::table('equipment', function (Blueprint $table) {
            $table->dropForeign(['equipment_type_id']);
            $table->dropColumn('equipment_type_id');
        });
    }

    /**
     * Pindahkan nilai teks equipment_type lama ke tabel equipment_types
     * dan tautkan equipment_type_id, agar data tidak hilang saat upgrade.
     */
    protected function migrateLegacyTypes(): void
    {
        if (! Schema::hasColumn('equipment', 'equipment_type')) {
            return;
        }

        $legacyTypes = DB::table('equipment')
            ->whereNotNull('equipment_type')
            ->where('equipment_type', '!=', '')
            ->distinct()
            ->pluck('equipment_type');

        foreach ($legacyTypes as $name) {
            $typeId = DB::table('equipment_types')->where('name', $name)->value('id');

            if (! $typeId) {
                $typeId = DB::table('equipment_types')->insertGetId([
                    'code' => $this->uniqueCode($name),
                    'name' => $name,
                    'status' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('equipment')
                ->where('equipment_type', $name)
                ->update(['equipment_type_id' => $typeId]);
        }
    }

    /**
     * Kembalikan nilai teks dari relasi saat rollback.
     */
    protected function restoreLegacyTypes(): void
    {
        DB::table('equipment')
            ->whereNotNull('equipment_type_id')
            ->orderBy('id')
            ->each(function ($row): void {
                $name = DB::table('equipment_types')->where('id', $row->equipment_type_id)->value('name');

                if ($name !== null) {
                    DB::table('equipment')
                        ->where('id', $row->id)
                        ->update(['equipment_type' => $name]);
                }
            });
    }

    protected function uniqueCode(string $name): string
    {
        $base = Str::slug($name) ?: 'type';
        $code = $base;
        $suffix = 1;

        while (DB::table('equipment_types')->where('code', $code)->exists()) {
            $code = $base.'-'.$suffix++;
        }

        return $code;
    }
};
