<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_types', function (Blueprint $table) {
            $table->string('fungsi', 30)->nullable()->after('name');

            $table->index('fungsi');
        });
    }

    public function down(): void
    {
        Schema::table('equipment_types', function (Blueprint $table) {
            $table->dropIndex(['fungsi']);
            $table->dropColumn('fungsi');
        });
    }
};
