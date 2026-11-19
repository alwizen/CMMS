<?php

namespace App\Support;

use App\Models\MaintenanceRequest;
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
     * Work order yang terkait dengan user: sebagai koordinator (performed by),
     * pemberi tugas, anggota tim pelaksana, atau milik FUNGSI yang sama
     * (WO shared pool yang belum/sudah dikerjakan).
     */
    public static function scopeWorkOrders(Builder $query, ?User $user): Builder
    {
        if (! $user || static::hasFullAccess($user)) {
            return $query;
        }

        $userId = $user->id;
        $fungsi = $user->fungsi;

        return $query->where(function (Builder $q) use ($userId, $fungsi) {
            $q->where('technician_coordinator_id', $userId)
                ->orWhere('issued_by', $userId)
                ->orWhereHas('workOrderWorkers', fn (Builder $w) => $w->where('user_id', $userId));

            if (filled($fungsi)) {
                $q->orWhere('fungsi', $fungsi);
            }
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
            || (filled($user->fungsi) && $workOrder->fungsi === $user->fungsi)
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
}
