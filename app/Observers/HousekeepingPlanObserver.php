<?php

namespace App\Observers;

use App\Models\HousekeepingPlan;

/**
 * Notifikasi verifikasi SPV GOS dikirim oleh HousekeepingPlan ketika
 * pekerjaan selesai (`syncStatusFromTasks` -> `notifyPendingVerification`),
 * bukan saat plan dibuat. Observer ini hanya menangani pengajuan ulang
 * setelah plan ditolak.
 */
class HousekeepingPlanObserver
{
    public function updated(HousekeepingPlan $plan): void
    {
        if (! $plan->wasChanged('approval_status')) {
            return;
        }

        if ($plan->approval_status !== HousekeepingPlan::APPROVAL_PENDING) {
            return;
        }

        // Plan yang sebelumnya ditolak diajukan ulang: minta verifikasi lagi.
        if ($plan->status === HousekeepingPlan::STATUSES['Completed']) {
            $plan->notifyPendingVerification();
        }
    }
}
