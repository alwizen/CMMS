<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropColumn('equipment_type');
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->foreignId('equipment_type_id')
                ->nullable()
                ->constrained('equipment_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropForeign(['equipment_type_id']);
            $table->dropColumn('equipment_type_id');
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->string('equipment_type', 100)->nullable();
        });
    }
};
