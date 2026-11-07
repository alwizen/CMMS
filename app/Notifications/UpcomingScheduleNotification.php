<?php

namespace App\Notifications;

use App\Models\MaintenanceSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UpcomingScheduleNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public MaintenanceSchedule $schedule) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $schedule = $this->schedule->loadMissing('equipment');

        return (new MailMessage)
            ->subject('Jadwal maintenance H-3: '.($schedule->equipment?->tag_number ?? '-'))
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Jadwal maintenance untuk equipment '.($schedule->equipment?->tag_number ?? '-').' jatuh pada '.$schedule->scheduled_date?->format('d M Y').'.')
            ->line('Pastikan teknisi dan material sudah siap.');
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
