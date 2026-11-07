<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();

            $table->string('document_number', 50)->nullable()->unique();
            $table->string('title', 150);
            $table->string('document_type', 30);

            $table->foreignId('equipment_id')
                ->nullable()
                ->constrained('equipment')
                ->nullOnDelete();

            $table->string('revision', 30)->nullable();
            $table->string('issuer', 100)->nullable();

            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();

            $table->string('file_path')->nullable();
            $table->text('description')->nullable();

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('document_type');
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
