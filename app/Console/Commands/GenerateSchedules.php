<?php

namespace App\Console\Commands;

use App\Models\MaintenancePlan;
use App\Models\MaintenanceSchedule;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateSchedules extends Command
{
    protected $signature = 'maintenance:generate-schedules {--months=12 : Horizon ke depan (bulan) jika plan tanpa end_date}';

    protected $description = 'Generate maintenance schedules dari maintenance plans yang aktif';

    public function handle(): int
    {
        $horizonMonths = (int) $this->option('months');
        $created = 0;

        $plans = MaintenancePlan::query()
            ->where('status', 'Active')
            ->get();

        foreach ($plans as $plan) {
            $dates = $this->datesForPlan($plan, $horizonMonths);

            foreach ($dates as $date) {
                $exists = MaintenanceSchedule::where('maintenance_plan_id', $plan->id)
                    ->whereDate('scheduled_date', $date->toDateString())
                    ->exists();

                if ($exists) {
                    continue;
                }

                MaintenanceSchedule::create([
                    'maintenance_plan_id' => $plan->id,
                    'equipment_id' => $plan->equipment_id,
                    'scheduled_date' => $date->toDateString(),
                    'status' => 'Scheduled',
                    'description' => $plan->description,
                ]);

                $created++;
            }
        }

        $this->info("Schedules created: {$created}");

        return self::SUCCESS;
    }

    /**
     * @return Carbon[]
     */
    protected function datesForPlan(MaintenancePlan $plan, int $horizonMonths): array
    {
        $start = Carbon::parse($plan->start_date);
        $end = $plan->end_date ? Carbon::parse($plan->end_date) : $start->copy()->addMonths($horizonMonths);

        if ($end->lte($start)) {
            return [];
        }

        $step = $this->stepForInterval($plan->interval);

        if ($step === null) {
            return [$start->copy()];
        }

        $dates = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $dates[] = $cursor->copy();
            $cursor = $step($cursor);
        }

        return $dates;
    }

    /**
     * @return (\Closure(Carbon): Carbon)|null
     */
    protected function stepForInterval(?string $interval): ?\Closure
    {
        return match ($interval) {
            'daily' => fn (Carbon $d) => $d->copy()->addDay(),
            'weekly' => fn (Carbon $d) => $d->copy()->addWeek(),
            'monthly' => fn (Carbon $d) => $d->copy()->addMonth(),
            'quarterly' => fn (Carbon $d) => $d->copy()->addMonths(3),
            'semi_annual' => fn (Carbon $d) => $d->copy()->addMonths(6),
            'yearly' => fn (Carbon $d) => $d->copy()->addYear(),
            default => null,
        };
    }
}
