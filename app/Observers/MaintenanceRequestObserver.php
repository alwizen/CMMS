<?php

namespace App\Observers;

use App\Models\MaintenanceRequest;
use App\Notifications\MaintenanceRequestPendingNotification;
use App\Support\Notify;
use Illuminate\Support\Facades\Notification;

class MaintenanceRequestObserver
{
    public function created(MaintenanceRequest $request): void
    {
        if ($request->status !== MaintenanceRequest::STATUSES['Open']) {
            return;
        }

        $recipients = Notify::supervisors();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new MaintenanceRequestPendingNotification($request));
    }
}
