<?php

namespace App\Notifications;

use App\Models\MaintenanceSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UpcomingScheduleNotification extends Notification
{
    use Queueable;

    public function __construct(public MaintenanceSchedule $schedule) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $schedule = $this->schedule;

        return [
            'type' => 'upcoming_schedule',
            'schedule_id' => $schedule->id,
            'equipment_id' => $schedule->equipment_id,
            'scheduled_date' => $schedule->scheduled_date?->format('Y-m-d'),
            'title' => 'Jadwal H-3',
            'message' => sprintf(
                'Schedule #%s (%s) jatuh pada %s.',
                $schedule->id,
                $schedule->equipment?->tag_number ?? '-',
                $schedule->scheduled_date?->format('d M Y') ?? '-'
            ),
        ];
    }
}
