<?php

namespace App\Console\Commands;

use App\Models\MaintenancePlanActivity;
use App\Models\MaintenanceSchedule;
use App\Models\WorkOrder;
use App\Models\WorkOrderActivity;
use App\Support\DocumentNumber;
use App\Support\SystemUser;
use App\Support\Workday;
use App\Support\WorkOrderSettings;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateWorkOrders extends Command
{
    protected $signature = 'maintenance:generate-work-orders
        {--days= : Override lead days (1-5)}
        {--dry-run : Tampilkan rencana tanpa menyimpan}';

    protected $description = 'Buat Work Order otomatis dari jadwal maintenance untuk N hari kerja ke depan';

    public function handle(): int
    {
        if (! WorkOrderSettings::autoGenerate()) {
            $this->info('Pembuatan WO otomatis dinonaktifkan di Site Setting.');

            return self::SUCCESS;
        }

        $leadDays = $this->option('days') !== null
            ? max(WorkOrderSettings::LEAD_DAYS_MIN, min(WorkOrderSettings::LEAD_DAYS_MAX, (int) $this->option('days')))
            : WorkOrderSettings::leadDays();

        $skipWeekend = WorkOrderSettings::skipWeekend();
        $backfillDays = WorkOrderSettings::backfillDays();
        $dryRun = (bool) $this->option('dry-run');

        $today = Carbon::today();
        $windowEnd = Workday::nextWorkday($today->copy()->addDays($leadDays), $skipWeekend);
        $backfillFrom = $today->copy()->subDays($backfillDays);

        $schedules = MaintenanceSchedule::query()
            ->whereIn('status', [
                MaintenanceSchedule::STATUSES['Scheduled'],
                MaintenanceSchedule::STATUSES['Rescheduled'],
                MaintenanceSchedule::STATUSES['Delayed'],
            ])
            ->whereDoesntHave('workOrders')
            ->whereDate('scheduled_date', '<=', $windowEnd)
            ->whereDate('scheduled_date', '>=', $backfillFrom)
            ->orderBy('scheduled_date')
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($schedules as $schedule) {
            $scheduled = Carbon::parse($schedule->scheduled_date);
            $isOverdue = $scheduled->lt($today);

            $executionDay = $isOverdue
                ? Workday::nextWorkday($today, $skipWeekend)
                : Workday::nextWorkday($scheduled, $skipWeekend);

            if ($executionDay->gt($windowEnd) && ! $isOverdue) {
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $this->line(sprintf(
                    '[dry-run] %s %s → %s%s',
                    $schedule->schedule_number ?? "#{$schedule->id}",
                    $scheduled->toDateString(),
                    $executionDay->toDateString(),
                    $isOverdue ? ' (terlewat)' : '',
                ));
                $created++;

                continue;
            }

            DB::transaction(function () use ($schedule, $executionDay, $isOverdue): void {
                $this->createWorkOrderFromSchedule($schedule, $executionDay, $isOverdue);
            });

            $created++;
        }

        $this->info(sprintf(
            '%s%d WO dibuat, %d dilewati (di luar window %d hari).',
            $dryRun ? '[dry-run] ' : '',
            $created,
            $skipped,
            $leadDays,
        ));

        return self::SUCCESS;
    }

    protected function createWorkOrderFromSchedule(MaintenanceSchedule $schedule, Carbon $executionDay, bool $isOverdue): WorkOrder
    {
        $schedule->loadMissing('maintenancePlan');
        $plan = $schedule->maintenancePlan;

        $startAt = $executionDay->copy()->setTimeFromTimeString(WorkOrderSettings::startTime());

        $workOrder = WorkOrder::create([
            'work_order_number' => DocumentNumber::generate('work_order', WorkOrder::class, 'work_order_number'),
            'equipment_id' => $schedule->equipment_id,
            'maintenance_plan_id' => $plan?->id,
            'maintenance_schedule_id' => $schedule->id,
            'issued_by' => SystemUser::id(),
            'technician_coordinator_id' => $plan?->technician_coordinator_id,
            'classification' => 'Preventive',
            'interval' => $plan?->interval,
            'start_at' => $startAt,
            'note' => trim('Dibuat otomatis dari jadwal '.($schedule->schedule_number ?? '#'.$schedule->id)
                .($isOverdue ? ' (jadwal terlewat, dijadwalkan ulang)' : '')),
            'status' => 'In Progress',
        ]);

        $planActivities = MaintenancePlanActivity::query()
            ->where('maintenance_plan_id', $schedule->maintenance_plan_id)
            ->with('activity')
            ->orderBy('sort_order')
            ->get();

        foreach ($planActivities as $planActivity) {
            $activity = $planActivity->activity;

            if ($activity === null) {
                continue;
            }

            WorkOrderActivity::create([
                'work_order_id' => $workOrder->id,
                'activity_id' => $activity->id,
                'reference' => $activity->reference,
                'unit' => $activity->unit,
                'executed' => false,
            ]);
        }

        if ($isOverdue) {
            $schedule->update(['status' => MaintenanceSchedule::STATUSES['Delayed']]);
        }

        return $workOrder;
    }
}
