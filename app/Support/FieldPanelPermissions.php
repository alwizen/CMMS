<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Sinkronisasi izin panel lapangan (`/app`) ke Shield.
 *
 * Hak akses menu/aksi tetap diatur lewat permission Shield. Class ini hanya
 * memastikan permission yang dipakai panel lapangan tersedia dan diberikan
 * ke role lapangan.
 */
class FieldPanelPermissions
{
    /**
     * @var list<string>
     */
    public const MOS = [
        'View:FieldDashboard',
        'View:MaintenanceCalendar',
        'ViewAny:WorkOrder',
        'View:WorkOrder',
        // Eksekusi WO (mulai/tunda/selesai & isi checklist) memakai izin update.
        'Update:WorkOrder',
        'ViewAny:Equipment',
        'View:Equipment',
        'ViewAny:MaintenanceRequest',
        'View:MaintenanceRequest',
        'Create:MaintenanceRequest',
        'Update:MaintenanceRequest',
        'ViewAny:MaintenanceHistory',
        'View:MaintenanceHistory',
        // Arsip dokumen hanya bisa dilihat/diunduh (tanpa create/update/delete).
        'ViewAny:Document',
        'View:Document',
    ];

    /**
     * @var list<string>
     */
    public const PATRA_JASA = [
        'View:FieldDashboard',
        'View:HousekeepingReport',
        'ViewAny:HousekeepingPlan',
        'View:HousekeepingPlan',
        'ViewAny:HousekeepingRequest',
        'View:HousekeepingRequest',
        'Create:HousekeepingRequest',
        'Update:HousekeepingRequest',
    ];

    /**
     * @var list<string>
     */
    public const SUPER_ADMIN = [
        'View:FieldDashboard',
    ];

    public static function sync(): void
    {
        $map = [
            'mos' => self::MOS,
            'patra-jasa' => self::PATRA_JASA,
            'super_admin' => self::SUPER_ADMIN,
        ];

        foreach ($map as $permissions) {
            foreach ($permissions as $permission) {
                Permission::findOrCreate($permission, 'web');
            }
        }

        foreach ($map as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');

            $role->givePermissionTo($permissions);
        }
    }
}
