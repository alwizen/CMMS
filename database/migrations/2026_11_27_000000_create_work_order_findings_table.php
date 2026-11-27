<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Temuan hasil checklist WO. Dibuat otomatis saat hasil inspeksi bermasalah
 * (kualitatif Poor/Not Working atau kuantitatif di luar batas), lalu dapat
 * ditindaklanjuti menjadi maintenance request perbaikan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_order_findings', function (Blueprint $table) {
            $table->id();
            $table->string('finding_number', 50)->unique();

            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->foreignId('work_order_activity_id')->nullable()->unique()
                ->constrained('work_order_activities')->nullOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained('equipment')->nullOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained('activities')->nullOnDelete();

            $table->string('answer_type', 20)->default('qualitative');
            $table->string('result', 100)->nullable();
            $table->string('severity', 20)->default('Medium');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('Open');

            $table->foreignId('maintenance_request_id')->nullable()
                ->constrained('maintenance_requests')->nullOnDelete();
            $table->foreignId('resolution_work_order_id')->nullable()
                ->constrained('work_orders')->nullOnDelete();

            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('photos')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'severity']);
            $table->index('maintenance_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_findings');
    }
};
