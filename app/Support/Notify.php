<?php

namespace App\Support;

use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class Notify
{
    public const SUPERVISOR_ROLE = 'spv_mos';

    /**
     * Supervisor yang menerima notifikasi request baru.
     *
     * @return Collection<int, User>
     */
    public static function supervisors(): Collection
    {
        if (! Role::where('name', self::SUPERVISOR_ROLE)->exists()) {
            return collect();
        }

        return User::role(self::SUPERVISOR_ROLE)
            ->where('status', true)
            ->get();
    }

    /**
     * Penerima per Work Order: koordinator dan seluruh pelaksana, tanpa duplikat.
     *
     * @return Collection<int, User>
     */
    public static function forWorkOrder(WorkOrder $workOrder): Collection
    {
        $workOrder->loadMissing('workOrderWorkers.user', 'technicianCoordinator');

        $users = collect([$workOrder->technicianCoordinator])
            ->merge($workOrder->workOrderWorkers->map(fn ($worker) => $worker->user))
            ->filter()
            ->unique('id')
            ->values();

        return $users->filter(fn (User $user): bool => (bool) $user->status)->values();
    }

    /**
     * Penerima untuk jadwal H-3: koordinator plan dan penerima dari WO yang terkait jadwal.
     *
     * @return Collection<int, User>
     */
    public static function forSchedule(MaintenanceSchedule $schedule): Collection
    {
        $schedule->loadMissing('maintenancePlan.technicianCoordinator', 'workOrders');

        $users = collect([$schedule->maintenancePlan?->technicianCoordinator])
            ->filter();

        foreach ($schedule->workOrders as $workOrder) {
            $users = $users->merge(self::forWorkOrder($workOrder));
        }

        return $users
            ->filter(fn (User $user): bool => (bool) $user->status)
            ->unique('id')
            ->values();
    }
}
