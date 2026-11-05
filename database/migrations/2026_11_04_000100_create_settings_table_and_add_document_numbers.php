<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::table('maintenance_plans', function (Blueprint $table) {
            $table->string('plan_number', 50)->nullable()->unique()->after('id');
        });

        Schema::table('maintenance_schedules', function (Blueprint $table) {
            $table->string('schedule_number', 50)->nullable()->unique()->after('id');
        });

        $this->backfill('maintenance_plans', 'plan_number', 'MP');
        $this->backfill('maintenance_schedules', 'schedule_number', 'MS');
    }

    public function down(): void
    {
        Schema::table('maintenance_plans', function (Blueprint $table) {
            $table->dropUnique(['plan_number']);
            $table->dropColumn('plan_number');
        });

        Schema::table('maintenance_schedules', function (Blueprint $table) {
            $table->dropUnique(['schedule_number']);
            $table->dropColumn('schedule_number');
        });

        Schema::dropIfExists('settings');
    }

    private function backfill(string $table, string $column, string $prefix): void
    {
        $records = DB::table($table)->select('id', 'created_at')->orderBy('id')->get();

        $sequences = [];

        foreach ($records as $record) {
            $date = $record->created_at ? Carbon::parse($record->created_at) : now();
            $period = $date->format('Ym');

            $sequences[$period] = ($sequences[$period] ?? 0) + 1;

            DB::table($table)
                ->where('id', $record->id)
                ->update([
                    $column => $prefix.'-'.$period.'-'.str_pad((string) $sequences[$period], 4, '0', STR_PAD_LEFT),
                ]);
        }
    }
};
