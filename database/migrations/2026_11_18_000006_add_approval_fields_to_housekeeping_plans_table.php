<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('housekeeping_plans', function (Blueprint $table) {
            $table->string('approval_status', 20)
                ->default('Pending')
                ->after('status');

            $table->text('approval_notes')->nullable()->after('approval_status');

            $table->foreignId('approved_by')
                ->nullable()
                ->after('approval_notes')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });

        // Plan yang sudah ada sebelum fitur approval dianggap sudah disetujui
        // agar laporan historis tetap bisa digenerate.
        DB::table('housekeeping_plans')->update([
            'approval_status' => 'Approved',
            'approved_at' => DB::raw('created_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('housekeeping_plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['approval_status', 'approval_notes', 'approved_at']);
        });
    }
};
