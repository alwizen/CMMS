<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_order_activities', function (Blueprint $table) {
            $table->timestamp('executed_at')->nullable()->after('executed');
        });

        // Backfill: pakai updated_at untuk item yang sudah executed.
        DB::table('work_order_activities')
            ->where('executed', true)
            ->whereNull('executed_at')
            ->update(['executed_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('work_order_activities', function (Blueprint $table) {
            $table->dropColumn('executed_at');
        });
    }
};
