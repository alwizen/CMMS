<?php

use App\Support\FieldPanelPermissions;
use Illuminate\Database\Migrations\Migration;

/**
 * Pastikan permission resource Temuan tersedia dan diberikan ke role lapangan.
 */
return new class extends Migration
{
    public function up(): void
    {
        FieldPanelPermissions::sync();
    }

    public function down(): void
    {
        // Permission tidak dicabut: pengaturan hak akses bersifat permanen.
    }
};
