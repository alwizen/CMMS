<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Penanggung jawab equipment type bisa lebih dari satu user.
        Schema::create('equipment_type_responsible_users', function (Blueprint $table) {
            $table->id();

            $table->foreignId('equipment_type_id')
                ->constrained('equipment_types')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['equipment_type_id', 'user_id'],
                'eq_type_responsible_user_unique',
            );
        });

        // Pindahkan penanggung jawab tunggal yang sudah ada ke pivot.
        $now = now();

        DB::table('equipment_types')
            ->whereNotNull('responsible_user_id')
            ->orderBy('id')
            ->each(function (object $type) use ($now): void {
                DB::table('equipment_type_responsible_users')->insertOrIgnore([
                    'equipment_type_id' => $type->id,
                    'user_id' => $type->responsible_user_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_type_responsible_users');
    }
};
