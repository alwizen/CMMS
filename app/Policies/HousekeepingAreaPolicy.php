<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\HousekeepingArea;
use Illuminate\Auth\Access\HandlesAuthorization;

class HousekeepingAreaPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:HousekeepingArea');
    }

    public function view(AuthUser $authUser, HousekeepingArea $housekeepingArea): bool
    {
        return $authUser->can('View:HousekeepingArea');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:HousekeepingArea');
    }

    public function update(AuthUser $authUser, HousekeepingArea $housekeepingArea): bool
    {
        return $authUser->can('Update:HousekeepingArea');
    }

    public function delete(AuthUser $authUser, HousekeepingArea $housekeepingArea): bool
    {
        return $authUser->can('Delete:HousekeepingArea');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:HousekeepingArea');
    }

    public function restore(AuthUser $authUser, HousekeepingArea $housekeepingArea): bool
    {
        return $authUser->can('Restore:HousekeepingArea');
    }

    public function forceDelete(AuthUser $authUser, HousekeepingArea $housekeepingArea): bool
    {
        return $authUser->can('ForceDelete:HousekeepingArea');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:HousekeepingArea');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:HousekeepingArea');
    }

    public function replicate(AuthUser $authUser, HousekeepingArea $housekeepingArea): bool
    {
        return $authUser->can('Replicate:HousekeepingArea');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:HousekeepingArea');
    }

}