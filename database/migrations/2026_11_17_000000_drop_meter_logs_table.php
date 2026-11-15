<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('meter_logs');
    }

    public function down(): void
    {
        // Fitur meter log dihapus permanen; tidak ada rollback.
    }
};
