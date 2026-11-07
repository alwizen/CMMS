<?php

namespace App\Notifications;

use App\Filament\Resources\MaintenanceRequests\MaintenanceRequestResource;
use App\Models\MaintenanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaintenanceRequestPendingNotification extends Notification implements ShouldQueue
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
            ->subject('Request menunggu approval: '.$request->request_number)
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Ada maintenance request baru untuk equipment '.($request->equipment?->tag_number ?? '-').'.')
            ->line('Deskripsi: '.$request->description)
            ->action('Buka Request', MaintenanceRequestResource::getUrl('view', ['record' => $request]))
            ->line('Silakan tinjau lalu lakukan approve atau reject.');
    }

    public function toArray(object $notifiable): array
    {
        $request = $this->request;

        return [
            'format' => 'filament',
            'duration' => 'persistent',
            'type' => 'maintenance_request_pending',
            'request_id' => $request->id,
            'request_number' => $request->request_number,
            'equipment_id' => $request->equipment_id,
            'title' => 'Request Menunggu Approval',
            'message' => sprintf(
                'Request %s (%s) menunggu approval.',
                $request->request_number ?? "#{$request->id}",
                $request->equipment?->tag_number ?? '-'
            ),
            'body' => sprintf(
                '%s (%s)',
                $request->request_number ?? "#{$request->id}",
                $request->equipment?->tag_number ?? '-'
            ),
        ];
    }
}
