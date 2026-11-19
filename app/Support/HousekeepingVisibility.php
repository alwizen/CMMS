<?php

namespace App\Support;

use App\Models\HousekeepingPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Batas visibilitas Plan Harian housekeeping.
 *
 * - SPV GOS / super admin / pengawas: melihat seluruh plan (mereka yang
 *   membuat dan menyetujui plan lintas area).
 * - Patra-jasa: hanya plan pada area yang ia menjadi penanggung jawab
 *   (`housekeeping_areas.supervisor_id`) atau plan yang secara eksplisit
 *   menugaskan dia (`housekeeping_plan_users`).
 */
class HousekeepingVisibility
{
    /**
     * Role yang boleh melihat seluruh plan lintas area.
     *
     * @var list<string>
     */
    public const FULL_ACCESS_ROLES = [
        HousekeepingRole::SUPERVISOR,
        HousekeepingRole::ADMIN,
        'pengawas',
    ];

    public static function hasFullAccess(?User $user): bool
    {
        if (! $user || ! $user->status) {
            return false;
        }

        return $user->hasAnyRole(static::FULL_ACCESS_ROLES);
    }

    /**
     * Terapkan batas visibilitas ke query plan.
     */
    public static function scopePlans(Builder $query, ?User $user): Builder
    {
        if (! $user || ! $user->status) {
            return $query->whereRaw('1 = 0');
        }

        if (static::hasFullAccess($user)) {
            return $query;
        }

        $userId = $user->id;

        return $query->where(function (Builder $query) use ($userId): void {
            $query->whereHas('area', fn (Builder $area) => $area->where('supervisor_id', $userId))
                ->orWhereHas('users', fn (Builder $users) => $users->where('user_id', $userId));
        });
    }

    /**
     * Terapkan batas visibilitas ke query task/checklist plan.
     */
    public static function scopeTasks(Builder $query, ?User $user): Builder
    {
        if (! $user || ! $user->status) {
            return $query->whereRaw('1 = 0');
        }

        if (static::hasFullAccess($user)) {
            return $query;
        }

        $userId = $user->id;

        return $query->whereHas('plan', fn (Builder $plan) => $plan
            ->where(fn (Builder $plan) => $plan
                ->whereHas('area', fn (Builder $area) => $area->where('supervisor_id', $userId))
                ->orWhereHas('users', fn (Builder $users) => $users->where('user_id', $userId))));
    }

    /**
     * Apakah user boleh melihat plan tertentu.
     */
    public static function canViewPlan(?User $user, int $planId): bool
    {
        if (! $user || ! $user->status) {
            return false;
        }

        if (static::hasFullAccess($user)) {
            return true;
        }

        return static::scopePlans(
            HousekeepingPlan::query()->whereKey($planId),
            $user,
        )->exists();
    }
}
