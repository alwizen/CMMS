<?php

use App\Support\FieldPanelPermissions;
use Illuminate\Database\Migrations\Migration;

/**
 * Permission panel lapangan dikelola Shield. Migrasi ini memastikan permission
 * yang dipakai resource/page `/app` tersedia dan diberikan ke role lapangan.
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
