<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('work_order_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('work_order_id');
            $table->unsignedBigInteger('activity_id');
            $table->text('reference')->nullable();
            $table->string('pre_inspection', 100)->nullable();
            $table->string('follow_up', 100)->nullable();
            $table->string('final_result', 100)->nullable();
            $table->string('unit', 30)->nullable();
            $table->boolean('executed')->default(false);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->foreign('work_order_id')->references('id')->on('work_orders')->onDelete('cascade');
            $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_activities');
    }
};
