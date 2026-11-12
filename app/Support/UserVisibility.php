<?php

namespace App\Support;

use App\Models\MaintenancePlan;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Builder;

class UserVisibility
{
    /**
     * Role yang boleh melihat seluruh data lintas akun.
     *
     * @var list<string>
     */
    public const FULL_ACCESS_ROLES = [
        'super_admin',
        'pengawas',
        'spv_mos',
    ];

    public static function hasFullAccess(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(static::FULL_ACCESS_ROLES);
    }

    /**
     * Work order yang terkait dengan user: sebagai koordinator, pemberi tugas,
     * atau anggota tim pelaksana.
     */
    public static function scopeWorkOrders(Builder $query, ?User $user): Builder
    {
        if (! $user || static::hasFullAccess($user)) {
            return $query;
        }

        $userId = $user->id;

        return $query->where(function (Builder $q) use ($userId) {
            $q->where('technician_coordinator_id', $userId)
                ->orWhere('issued_by', $userId)
                ->orWhereHas('workOrderWorkers', fn (Builder $w) => $w->where('user_id', $userId));
        });
    }

    /**
     * Jadwal terkait user: koordinator plan, atau punya WO yang terkait user.
     */
    public static function scopeSchedules(Builder $query, ?User $user): Builder
    {
        if (! $user || static::hasFullAccess($user)) {
            return $query;
        }

        $userId = $user->id;

        return $query->where(function (Builder $q) use ($userId, $user) {
            $q->whereHas('maintenancePlan', fn (Builder $plan) => $plan->where('technician_coordinator_id', $userId))
                ->orWhereHas('workOrders', fn (Builder $wo) => static::scopeWorkOrders($wo, $user));
        });
    }

    /**
     * Plan terkait user: sebagai pembuat atau koordinator teknisi.
     */
    public static function scopePlans(Builder $query, ?User $user): Builder
    {
        if (! $user || static::hasFullAccess($user)) {
            return $query;
        }

        $userId = $user->id;

        return $query->where(function (Builder $q) use ($userId) {
            $q->where('technician_coordinator_id', $userId)
                ->orWhere('created_by', $userId);
        });
    }

    /**
     * Request terkait user: sebagai pelapor atau approver.
     */
    public static function scopeRequests(Builder $query, ?User $user): Builder
    {
        if (! $user || static::hasFullAccess($user)) {
            return $query;
        }

        $userId = $user->id;

        return $query->where(function (Builder $q) use ($userId) {
            $q->where('reported_by', $userId)
                ->orWhere('approved_by', $userId);
        });
    }

    /**
     * Pemeriksaan akses per record untuk work order.
     */
    public static function canAccessWorkOrder(WorkOrder $workOrder, ?User $user): bool
    {
        if (! $user || static::hasFullAccess($user)) {
            return true;
        }

        return $workOrder->technician_coordinator_id === $user->id
            || $workOrder->issued_by === $user->id
            || $workOrder->workOrderWorkers()->where('user_id', $user->id)->exists();
    }

    /**
     * Pemeriksaan akses per record untuk maintenance request.
     */
    public static function canAccessRequest(MaintenanceRequest $maintenanceRequest, ?User $user): bool
    {
        if (! $user || static::hasFullAccess($user)) {
            return true;
        }

        return $maintenanceRequest->reported_by === $user->id
            || $maintenanceRequest->approved_by === $user->id;
    }

    /**
     * Pemeriksaan akses per record untuk maintenance plan.
     */
    public static function canAccessPlan(MaintenancePlan $plan, ?User $user): bool
    {
        if (! $user || static::hasFullAccess($user)) {
            return true;
        }

        return $plan->technician_coordinator_id === $user->id
            || $plan->created_by === $user->id;
    }

    /**
     * Pemeriksaan akses per record untuk maintenance schedule.
     */
    public static function canAccessSchedule(MaintenanceSchedule $schedule, ?User $user): bool
    {
        if (! $user || static::hasFullAccess($user)) {
            return true;
        }

        if ($schedule->maintenancePlan?->technician_coordinator_id === $user->id) {
            return true;
        }

        return $schedule->workOrders()
            ->where(function (Builder $q) use ($user) {
                $q->where('technician_coordinator_id', $user->id)
                    ->orWhere('issued_by', $user->id)
                    ->orWhereHas('workOrderWorkers', fn (Builder $w) => $w->where('user_id', $user->id));
            })
            ->exists();
    }
}
