<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Pisahkan hak akses housekeeping:
 * - SPV GOS (`spv_gos`) mengelola Plan Harian (buat/ubah/hapus).
 * - Patra-jasa (`patra-jasa`) hanya mengeksekusi checklist & foto.
 */
return new class extends Migration
{
    public function up(): void
    {
        $planManagement = [
            'Create:HousekeepingPlan',
            'Update:HousekeepingPlan',
            'Delete:HousekeepingPlan',
            'DeleteAny:HousekeepingPlan',
            'ForceDelete:HousekeepingPlan',
            'ForceDeleteAny:HousekeepingPlan',
            'Reorder:HousekeepingPlan',
            'Replicate:HousekeepingPlan',
            'Restore:HousekeepingPlan',
            'RestoreAny:HousekeepingPlan',
        ];

        $permissions = Permission::query()
            ->whereIn('name', $planManagement)
            ->get();

        if ($permissions->isEmpty()) {
            return;
        }

        $executor = Role::query()->where('name', 'patra-jasa')->first();
        $supervisor = Role::query()->where('name', 'spv_gos')->first();

        // Patra-jasa tidak lagi boleh mengelola Plan Harian.
        $executor?->revokePermissionTo($permissions->pluck('name')->all());

        // SPV GOS perlu izin mengelola Plan Harian (bila Shield belum memberikannya).
        $supervisor?->givePermissionTo($permissions->pluck('name')->all());
    }

    public function down(): void
    {
        // Tidak dikembalikan: pemisahan peran bersifat permanen.
    }
};
