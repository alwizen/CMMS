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

    public const FALLBACK_ROLE = 'super_admin';

    /**
     * Supervisor yang menerima notifikasi request baru.
     *
     * Jika role supervisor belum dibuat atau belum ada pemegangnya,
     * notifikasi dialihkan ke super admin agar tidak ada request yang terlewat.
     *
     * @return Collection<int, User>
     */
    public static function supervisors(): Collection
    {
        $users = static::usersWithRole(self::SUPERVISOR_ROLE);

        if ($users->isNotEmpty()) {
            return $users;
        }

        return static::usersWithRole(self::FALLBACK_ROLE);
    }

    /**
     * @return Collection<int, User>
     */
    protected static function usersWithRole(string $role): Collection
    {
        if (! Role::where('name', $role)->exists()) {
            return collect();
        }

        return User::role($role)
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
