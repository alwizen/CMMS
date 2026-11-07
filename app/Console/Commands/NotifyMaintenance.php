<?php

namespace App\Console\Commands;

use App\Models\MaintenanceSchedule;
use App\Models\WorkOrder;
use App\Notifications\OverdueWorkOrderNotification;
use App\Notifications\UpcomingScheduleNotification;
use App\Support\Notify;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class NotifyMaintenance extends Command
{
    protected $signature = 'maintenance:notify';

    protected $description = 'Kirim notifikasi database dan email: jadwal H-3 dan WO overdue, per user terkait';

    public function handle(): int
    {
        $today = Carbon::today();
        $h3 = $today->copy()->addDays(3)->toDateString();

        $schedules = MaintenanceSchedule::with('equipment')
            ->where('status', MaintenanceSchedule::STATUSES['Scheduled'])
            ->whereDate('scheduled_date', $h3)
            ->get();

        $overdue = WorkOrder::with('equipment')
            ->whereIn('status', ['Pending', 'In Progress', 'On Hold'])
            ->whereDate('start_at', '<', $today->toDateString())
            ->get();

        if ($schedules->isEmpty() && $overdue->isEmpty()) {
            $this->info('Tidak ada notifikasi.');

            return self::SUCCESS;
        }

        foreach ($schedules as $schedule) {
            $recipients = Notify::forSchedule($schedule);

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new UpcomingScheduleNotification($schedule));
            }
        }

        foreach ($overdue as $wo) {
            $recipients = Notify::forWorkOrder($wo);

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new OverdueWorkOrderNotification($wo));
            }
        }

        $this->info(sprintf('Notifikasi: %d schedule H-3, %d WO overdue.', $schedules->count(), $overdue->count()));

        return self::SUCCESS;
    }
}
