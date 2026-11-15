<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\HousekeepingPlan;
use Illuminate\Auth\Access\HandlesAuthorization;

class HousekeepingPlanPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:HousekeepingPlan');
    }

    public function view(AuthUser $authUser, HousekeepingPlan $housekeepingPlan): bool
    {
        return $authUser->can('View:HousekeepingPlan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:HousekeepingPlan');
    }

    public function update(AuthUser $authUser, HousekeepingPlan $housekeepingPlan): bool
    {
        return $authUser->can('Update:HousekeepingPlan');
    }

    public function delete(AuthUser $authUser, HousekeepingPlan $housekeepingPlan): bool
    {
        return $authUser->can('Delete:HousekeepingPlan');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:HousekeepingPlan');
    }

    public function restore(AuthUser $authUser, HousekeepingPlan $housekeepingPlan): bool
    {
        return $authUser->can('Restore:HousekeepingPlan');
    }

    public function forceDelete(AuthUser $authUser, HousekeepingPlan $housekeepingPlan): bool
    {
        return $authUser->can('ForceDelete:HousekeepingPlan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:HousekeepingPlan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:HousekeepingPlan');
    }

    public function replicate(AuthUser $authUser, HousekeepingPlan $housekeepingPlan): bool
    {
        return $authUser->can('Replicate:HousekeepingPlan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:HousekeepingPlan');
    }

}