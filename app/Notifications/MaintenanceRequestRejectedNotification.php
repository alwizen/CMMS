<?php

namespace App\Notifications;

use App\Models\MaintenanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaintenanceRequestRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public MaintenanceRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->request->loadMissing('equipment');

        return (new MailMessage)
            ->subject('Request ditolak: '.$request->request_number)
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Maintenance request '.$request->request_number.' untuk equipment '.($request->equipment?->tag_number ?? '-').' tidak dilanjutkan.')
            ->line('Alasan: '.($request->approval_notes ?: '-'))
            ->line('Tidak perlu ada pekerjaan lanjutan untuk request ini.');
    }

    public function toArray(object $notifiable): array
    {
        $request = $this->request;

        $message = sprintf(
            'Request %s (%s) ditolak: %s',
            $request->request_number ?? "#{$request->id}",
            $request->equipment?->tag_number ?? '-',
            $request->approval_notes ?: 'tanpa catatan'
        );

        return [
            'format' => 'filament',
            'duration' => 'persistent',
            'type' => 'maintenance_request_rejected',
            'request_id' => $request->id,
            'request_number' => $request->request_number,
            'equipment_id' => $request->equipment_id,
            'reason' => $request->approval_notes,
            'title' => 'Request Ditolak',
            'message' => $message,
            'body' => $message,
        ];
    }
}
