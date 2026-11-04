<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OverdueWorkOrderNotification extends Notification
{
    use Queueable;

    public function __construct(public WorkOrder $workOrder) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $wo = $this->workOrder;

        return [
            'type' => 'overdue_work_order',
            'work_order_id' => $wo->id,
            'work_order_number' => $wo->work_order_number,
            'equipment_id' => $wo->equipment_id,
            'status' => $wo->status,
            'title' => 'WO Overdue',
            'message' => sprintf(
                'WO %s (%s) status %s sudah melewati jadwal.',
                $wo->work_order_number ?? "#{$wo->id}",
                $wo->equipment?->tag_number ?? '-',
                $wo->status
            ),
        ];
    }
}
