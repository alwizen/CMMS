<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housekeeping_requests', function (Blueprint $table) {
            $table->id();

            $table->string('request_number', 50)->nullable()->unique();

            $table->foreignId('housekeeping_area_id')
                ->constrained('housekeeping_areas')
                ->restrictOnDelete();

            $table->foreignId('housekeeping_facility_id')
                ->nullable()
                ->constrained('housekeeping_facilities')
                ->nullOnDelete();

            $table->date('request_date');
            $table->time('request_time')->nullable();
            $table->text('description');
            $table->string('priority', 20)->default('Normal');
            $table->string('status', 20)->default('Open');
            $table->json('photo')->nullable();

            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('approval_notes')->nullable();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();

            $table->foreignId('housekeeping_plan_id')
                ->nullable()
                ->constrained('housekeeping_plans')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('housekeeping_requests');
    }
};
