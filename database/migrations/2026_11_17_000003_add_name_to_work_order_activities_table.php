<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nama item checklist manual (tanpa master Activity).
        // Diisi ketika aktivitas diketik sendiri saat approve maintenance request.
        Schema::table('work_order_activities', function (Blueprint $table) {
            $table->string('name', 150)->nullable()->after('activity_id');
        });
    }

    public function down(): void
    {
        Schema::table('work_order_activities', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
