<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\HousekeepingManpower;
use Illuminate\Auth\Access\HandlesAuthorization;

class HousekeepingManpowerPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:HousekeepingManpower');
    }

    public function view(AuthUser $authUser, HousekeepingManpower $housekeepingManpower): bool
    {
        return $authUser->can('View:HousekeepingManpower');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:HousekeepingManpower');
    }

    public function update(AuthUser $authUser, HousekeepingManpower $housekeepingManpower): bool
    {
        return $authUser->can('Update:HousekeepingManpower');
    }

    public function delete(AuthUser $authUser, HousekeepingManpower $housekeepingManpower): bool
    {
        return $authUser->can('Delete:HousekeepingManpower');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:HousekeepingManpower');
    }

    public function restore(AuthUser $authUser, HousekeepingManpower $housekeepingManpower): bool
    {
        return $authUser->can('Restore:HousekeepingManpower');
    }

    public function forceDelete(AuthUser $authUser, HousekeepingManpower $housekeepingManpower): bool
    {
        return $authUser->can('ForceDelete:HousekeepingManpower');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:HousekeepingManpower');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:HousekeepingManpower');
    }

    public function replicate(AuthUser $authUser, HousekeepingManpower $housekeepingManpower): bool
    {
        return $authUser->can('Replicate:HousekeepingManpower');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:HousekeepingManpower');
    }

}