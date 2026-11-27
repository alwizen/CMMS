<?php

namespace App\Support;

use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderFinding;
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
     * Request terkait user: sebagai pelapor, approver, atau milik fungsi
     * equipment yang sama (petugas MOS satu fungsi bisa saling melihat).
     */
    public static function scopeRequests(Builder $query, ?User $user): Builder
    {
        if (! $user || static::hasFullAccess($user)) {
            return $query;
        }

        $userId = $user->id;
        $fungsi = $user->fungsi;

        return $query->where(function (Builder $q) use ($userId, $fungsi) {
            $q->where('reported_by', $userId)
                ->orWhere('approved_by', $userId)
                // Request dari WO yang terkait user (performed by / pemberi tugas / pelaksana).
                ->orWhereHas('workOrders', function (Builder $wo) use ($userId, $fungsi): void {
                    $wo->where('technician_coordinator_id', $userId)
                        ->orWhere('issued_by', $userId)
                        ->orWhereHas('workOrderWorkers', fn (Builder $worker) => $worker->where('user_id', $userId));

                    if (filled($fungsi)) {
                        $wo->orWhere('fungsi', $fungsi);
                    }
                });

            if (filled($fungsi)) {
                $q->orWhereHas(
                    'equipment.equipmentType',
                    fn (Builder $type) => $type->where('fungsi', $fungsi),
                );
            }
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
     * Temuan yang berasal dari WO yang dapat diakses user.
     */
    public static function scopeFindings(Builder $query, ?User $user): Builder
    {
        if (! $user || static::hasFullAccess($user)) {
            return $query;
        }

        return $query->whereHas(
            'workOrder',
            fn (Builder $workOrder) => static::scopeWorkOrders($workOrder, $user),
        );
    }

    /**
     * Pemeriksaan akses per record untuk temuan.
     */
    public static function canAccessFinding(WorkOrderFinding $finding, ?User $user): bool
    {
        if (! $user || static::hasFullAccess($user)) {
            return true;
        }

        return $finding->workOrder !== null
            && static::canAccessWorkOrder($finding->workOrder, $user);
    }

    /**
     * Pemeriksaan akses per record untuk maintenance request.
     */
    public static function canAccessRequest(MaintenanceRequest $maintenanceRequest, ?User $user): bool
    {
        if (! $user || static::hasFullAccess($user)) {
            return true;
        }

        if ($maintenanceRequest->reported_by === $user->id
            || $maintenanceRequest->approved_by === $user->id) {
            return true;
        }

        if (filled($user->fungsi) && $maintenanceRequest->equipment?->equipmentType?->fungsi === $user->fungsi) {
            return true;
        }

        // Request terlihat oleh petugas yang memegang WO turunannya.
        return $maintenanceRequest->workOrders()
            ->where(function (Builder $wo) use ($user): void {
                $wo->where('technician_coordinator_id', $user->id)
                    ->orWhere('issued_by', $user->id)
                    ->orWhereHas('workOrderWorkers', fn (Builder $worker) => $worker->where('user_id', $user->id));

                if (filled($user->fungsi)) {
                    $wo->orWhere('fungsi', $user->fungsi);
                }
            })
            ->exists();
    }
}
