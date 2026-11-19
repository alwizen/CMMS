<?php

namespace App\Support;

use App\Models\User;

/**
 * Pembagian peran housekeeping.
 *
 * - SPV GOS (`spv_gos`): membuat Plan Harian dan menyetujui/menolak laporan
 *   harian. Tidak mengeksekusi checklist.
 * - Patra-jasa (`patra-jasa`): mengeksekusi checklist dan upload foto
 *   before/after. Tidak membuat plan maupun menyetujui laporan.
 */
class HousekeepingRole
{
    public const SUPERVISOR = 'spv_gos';

    public const EXECUTOR = 'patra-jasa';

    public const ADMIN = 'super_admin';

    /**
     * Boleh membuat/mengubah struktur Plan Harian (checklist, manpower).
     */
    public static function canManagePlan(?User $user): bool
    {
        return static::hasAny($user, [self::SUPERVISOR, self::ADMIN]);
    }

    /**
     * Boleh mengeksekusi checklist (mulai, selesai, upload foto).
     */
    public static function canExecute(?User $user): bool
    {
        return static::hasAny($user, [self::EXECUTOR, self::ADMIN]);
    }

    /**
     * Boleh memverifikasi hasil pekerjaan (approve/reject laporan).
     */
    public static function canVerify(?User $user): bool
    {
        return static::hasAny($user, [self::SUPERVISOR, self::ADMIN]);
    }

    public static function hasAny(?User $user, array $roles): bool
    {
        if (! $user || ! $user->status) {
            return false;
        }

        return $user->hasAnyRole($roles);
    }
}
