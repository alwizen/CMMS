<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Work order yang selesai dikerjakan teknisi perlu direview SPV MOS sebelum
 * dianggap tuntas (membuat maintenance history & menutup request).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->string('approval_status', 20)->default('Pending')->after('status');
            $table->text('approval_notes')->nullable()->after('approval_status');
            $table->foreignId('approved_by')->nullable()->after('approval_notes')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['approval_status', 'approval_notes', 'approved_at']);
        });
    }
};
