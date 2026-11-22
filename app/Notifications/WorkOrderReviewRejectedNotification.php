<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkOrderReviewRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public WorkOrder $workOrder,
        public ?string $notes = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $wo = $this->workOrder->loadMissing('equipment');

        return (new MailMessage)
            ->subject('WO perlu diperbaiki: '.($wo->work_order_number ?? '#'.$wo->id))
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Review SPV MOS untuk WO '.($wo->work_order_number ?? '#'.$wo->id).' ('.($wo->equipment?->tag_number ?? '-').') ditolak.')
            ->when(filled($this->notes), fn (MailMessage $mail) => $mail->line('Catatan: '.$this->notes))
            ->line('Silakan perbaiki pekerjaan lalu tandai selesai kembali.');
    }

    public function toArray(object $notifiable): array
    {
        $wo = $this->workOrder;

        $message = sprintf(
            'WO %s (%s) ditolak SPV MOS%s.',
            $wo->work_order_number ?? "#{$wo->id}",
            $wo->equipment?->tag_number ?? '-',
            filled($this->notes) ? ': '.$this->notes : ''
        );

        return [
            'format' => 'filament',
            'duration' => 'persistent',
            'type' => 'work_order_review_rejected',
            'work_order_id' => $wo->id,
            'work_order_number' => $wo->work_order_number,
            'equipment_id' => $wo->equipment_id,
            'status' => $wo->status,
            'title' => 'WO Perlu Diperbaiki',
            'message' => $message,
            'body' => $message,
        ];
    }
}
