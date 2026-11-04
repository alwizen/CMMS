<?php

namespace App\Console\Commands;

use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\OverdueWorkOrderNotification;
use App\Notifications\UpcomingScheduleNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

class NotifyMaintenance extends Command
{
    protected $signature = 'maintenance:notify';

    protected $description = 'Kirim notifikasi database: jadwal H-3 dan WO overdue';

    public function handle(): int
    {
        $today = Carbon::today();
        $h3 = $today->copy()->addDays(3)->toDateString();

        $schedules = MaintenanceSchedule::with('equipment')
            ->where('status', 'Scheduled')
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

        $recipients = $this->recipients();

        foreach ($schedules as $schedule) {
            Notification::send($recipients, new UpcomingScheduleNotification($schedule));
        }

        foreach ($overdue as $wo) {
            Notification::send($recipients, new OverdueWorkOrderNotification($wo));
        }

        $this->info(sprintf('Notifikasi: %d schedule H-3, %d WO overdue.', $schedules->count(), $overdue->count()));

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, User>
     */
    protected function recipients()
    {
        $roles = Role::pluck('name')->toArray();
        $wanted = array_intersect(['super_admin', 'admin', 'planner'], $roles);

        if (! empty($wanted)) {
            $users = User::role($wanted)->get();

            if ($users->isNotEmpty()) {
                return $users;
            }
        }

        return User::limit(5)->get();
    }
}
