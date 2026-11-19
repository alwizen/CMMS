<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // `maintenance_type` duplikat dengan `classification`; cukup simpan classification.
        Schema::table('maintenance_histories', function (Blueprint $table) {
            $table->dropColumn('maintenance_type');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_histories', function (Blueprint $table) {
            $table->string('maintenance_type', 50)->nullable()->after('work_order_number');
        });
    }
};
