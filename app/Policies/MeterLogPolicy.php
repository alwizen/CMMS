<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MeterLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class MeterLogPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MeterLog');
    }

    public function view(AuthUser $authUser, MeterLog $meterLog): bool
    {
        return $authUser->can('View:MeterLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MeterLog');
    }

    public function update(AuthUser $authUser, MeterLog $meterLog): bool
    {
        return $authUser->can('Update:MeterLog');
    }

    public function delete(AuthUser $authUser, MeterLog $meterLog): bool
    {
        return $authUser->can('Delete:MeterLog');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MeterLog');
    }

    public function restore(AuthUser $authUser, MeterLog $meterLog): bool
    {
        return $authUser->can('Restore:MeterLog');
    }

    public function forceDelete(AuthUser $authUser, MeterLog $meterLog): bool
    {
        return $authUser->can('ForceDelete:MeterLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MeterLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MeterLog');
    }

    public function replicate(AuthUser $authUser, MeterLog $meterLog): bool
    {
        return $authUser->can('Replicate:MeterLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MeterLog');
    }

}