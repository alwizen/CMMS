<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\HousekeepingRequest;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class HousekeepingRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:HousekeepingRequest');
    }

    public function view(AuthUser $authUser, HousekeepingRequest $housekeepingRequest): bool
    {
        return $authUser->can('View:HousekeepingRequest');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:HousekeepingRequest');
    }

    public function update(AuthUser $authUser, HousekeepingRequest $housekeepingRequest): bool
    {
        return $authUser->can('Update:HousekeepingRequest');
    }

    public function delete(AuthUser $authUser, HousekeepingRequest $housekeepingRequest): bool
    {
        return $authUser->can('Delete:HousekeepingRequest');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:HousekeepingRequest');
    }

    public function restore(AuthUser $authUser, HousekeepingRequest $housekeepingRequest): bool
    {
        return $authUser->can('Restore:HousekeepingRequest');
    }

    public function forceDelete(AuthUser $authUser, HousekeepingRequest $housekeepingRequest): bool
    {
        return $authUser->can('ForceDelete:HousekeepingRequest');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:HousekeepingRequest');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:HousekeepingRequest');
    }

    public function replicate(AuthUser $authUser, HousekeepingRequest $housekeepingRequest): bool
    {
        return $authUser->can('Replicate:HousekeepingRequest');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:HousekeepingRequest');
    }
}
