<?php

namespace App\Support;

use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class Notify
{
    /** Supervisor maintenance (Work Order / maintenance request). */
    public const SUPERVISOR_ROLE = 'spv_mos';

    /** Supervisor housekeeping (Plan Harian GOS / General Office Services). */
    public const HOUSEKEEPING_SUPERVISOR_ROLE = 'spv_gos';

    public const FALLBACK_ROLE = 'super_admin';

    /**
     * Supervisor yang menerima notifikasi request baru (maintenance).
     *
     * Jika role supervisor belum dibuat atau belum ada pemegangnya,
     * notifikasi dialihkan ke super admin agar tidak ada request yang terlewat.
     *
     * @return Collection<int, User>
     */
    public static function supervisors(): Collection
    {
        return static::forRole(self::SUPERVISOR_ROLE);
    }

    /**
     * Supervisor housekeeping (SPV GOS) yang menerima notifikasi Plan Harian.
     *
     * @return Collection<int, User>
     */
    public static function housekeepingSupervisors(): Collection
    {
        return static::forRole(self::HOUSEKEEPING_SUPERVISOR_ROLE);
    }

    /**
     * @return Collection<int, User>
     */
    protected static function forRole(string $role): Collection
    {
        $users = static::usersWithRole($role);

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
}
