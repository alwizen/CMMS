<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MaintenanceHistory;
use Illuminate\Auth\Access\HandlesAuthorization;

class MaintenanceHistoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MaintenanceHistory');
    }

    public function view(AuthUser $authUser, MaintenanceHistory $maintenanceHistory): bool
    {
        return $authUser->can('View:MaintenanceHistory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MaintenanceHistory');
    }

    public function update(AuthUser $authUser, MaintenanceHistory $maintenanceHistory): bool
    {
        return $authUser->can('Update:MaintenanceHistory');
    }

    public function delete(AuthUser $authUser, MaintenanceHistory $maintenanceHistory): bool
    {
        return $authUser->can('Delete:MaintenanceHistory');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MaintenanceHistory');
    }

    public function restore(AuthUser $authUser, MaintenanceHistory $maintenanceHistory): bool
    {
        return $authUser->can('Restore:MaintenanceHistory');
    }

    public function forceDelete(AuthUser $authUser, MaintenanceHistory $maintenanceHistory): bool
    {
        return $authUser->can('ForceDelete:MaintenanceHistory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MaintenanceHistory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MaintenanceHistory');
    }

    public function replicate(AuthUser $authUser, MaintenanceHistory $maintenanceHistory): bool
    {
        return $authUser->can('Replicate:MaintenanceHistory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MaintenanceHistory');
    }

}