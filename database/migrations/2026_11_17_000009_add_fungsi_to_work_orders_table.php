<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            // WO dimiliki oleh FUNGSI (shared pool), bukan satu user.
            $table->string('fungsi', 30)->nullable()->after('technician_coordinator_id');

            $table->index('fungsi');
        });

        // Backfill fungsi dari koordinator yang sudah ada (bila ada).
        DB::table('work_orders')
            ->whereNull('fungsi')
            ->whereNotNull('technician_coordinator_id')
            ->orderBy('id')
            ->each(function (object $workOrder): void {
                $fungsi = DB::table('users')
                    ->where('id', $workOrder->technician_coordinator_id)
                    ->value('fungsi');

                if ($fungsi) {
                    DB::table('work_orders')
                        ->where('id', $workOrder->id)
                        ->update(['fungsi' => $fungsi]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropIndex(['fungsi']);
            $table->dropColumn('fungsi');
        });
    }
};
