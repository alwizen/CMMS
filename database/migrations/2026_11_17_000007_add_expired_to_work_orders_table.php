<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            // Masa berlaku WO dalam hari sejak start_at (0/kosong = tanpa batas).
            $table->unsignedInteger('expired_days')->nullable()->after('finish_at');
            // Batas akhir eksekusi = start_at + expired_days.
            $table->dateTime('expired_at')->nullable()->after('expired_days');

            $table->index('expired_at');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropIndex(['expired_at']);
            $table->dropColumn(['expired_days', 'expired_at']);
        });
    }
};
