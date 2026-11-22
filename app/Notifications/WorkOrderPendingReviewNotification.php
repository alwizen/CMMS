<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkOrderPendingReviewNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public WorkOrder $workOrder) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $wo = $this->workOrder->loadMissing('equipment');

        return (new MailMessage)
            ->subject('WO menunggu review: '.($wo->work_order_number ?? '#'.$wo->id))
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Work order '.($wo->work_order_number ?? '#'.$wo->id).' untuk equipment '.($wo->equipment?->tag_number ?? '-').' sudah diselesaikan teknisi.')
            ->line('Silakan periksa hasil pekerjaan lalu lakukan Approve atau Reject.');
    }

    public function toArray(object $notifiable): array
    {
        $wo = $this->workOrder;

        $message = sprintf(
            'WO %s (%s) selesai dikerjakan dan menunggu review SPV MOS.',
            $wo->work_order_number ?? "#{$wo->id}",
            $wo->equipment?->tag_number ?? '-'
        );

        return [
            'format' => 'filament',
            'duration' => 'persistent',
            'type' => 'work_order_pending_review',
            'work_order_id' => $wo->id,
            'work_order_number' => $wo->work_order_number,
            'equipment_id' => $wo->equipment_id,
            'status' => $wo->status,
            'title' => 'WO Menunggu Review',
            'message' => $message,
            'body' => $message,
        ];
    }
}
