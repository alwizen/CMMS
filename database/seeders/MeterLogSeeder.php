<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\MeterLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MeterLogSeeder extends Seeder
{
    public function run(): void
    {
        MeterLog::query()->delete();

        $equipment = Equipment::all();
        $user = User::first();

        if ($equipment->isEmpty() || ! $user) {
            return;
        }

        foreach ($equipment as $eq) {
            $baseHours = fake()->numberBetween(200, 1200);
            $readings = fake()->numberBetween(2, 6);
            $date = Carbon::now()->subMonths($readings)->startOfMonth();

            for ($i = 0; $i < $readings; $i++) {
                $date = $date->copy()->addMonth();
                $baseHours += fake()->numberBetween(40, 180);

                MeterLog::create([
                    'equipment_id' => $eq->id,
                    'reading_date' => $date->toDateString(),
                    'value' => round($baseHours + fake()->randomFloat(3, 0, 0.999), 3),
                    'recorded_by' => $user->id,
                ]);
            }
        }
    }
}
