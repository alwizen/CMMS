<?php

namespace App\Notifications;

use App\Models\MaintenanceRequest;
use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaintenanceRequestApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public MaintenanceRequest $request,
        public ?WorkOrder $workOrder = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->request->loadMissing('equipment');

        return (new MailMessage)
            ->subject('Request disetujui: '.$request->request_number)
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Maintenance request '.$request->request_number.' untuk equipment '.($request->equipment?->tag_number ?? '-').' telah disetujui.')
            ->when($this->workOrder, fn (MailMessage $mail) => $mail->line('Work order dibuat: '.$this->workOrder->work_order_number.'.'))
            ->line('Silakan lanjutkan pekerjaan sesuai checklist dan perbarui status work order setelah selesai.');
    }

    public function toArray(object $notifiable): array
    {
        $request = $this->request;

        $message = sprintf(
            'Request %s (%s) telah disetujui.',
            $request->request_number ?? "#{$request->id}",
            $request->equipment?->tag_number ?? '-'
        );

        if ($this->workOrder) {
            $message .= ' WO '.($this->workOrder->work_order_number ?? '#'.$this->workOrder->id).' siap dikerjakan.';
        }

        return [
            'format' => 'filament',
            'duration' => 'persistent',
            'type' => 'maintenance_request_approved',
            'request_id' => $request->id,
            'request_number' => $request->request_number,
            'work_order_id' => $this->workOrder?->id,
            'work_order_number' => $this->workOrder?->work_order_number,
            'equipment_id' => $request->equipment_id,
            'title' => 'Request Disetujui',
            'message' => $message,
            'body' => $message,
        ];
    }
}
