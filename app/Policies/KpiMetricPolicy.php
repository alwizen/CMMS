<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\KpiMetric;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class KpiMetricPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:KpiMetric');
    }

    public function view(AuthUser $authUser, KpiMetric $kpiMetric): bool
    {
        return $authUser->can('View:KpiMetric');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:KpiMetric');
    }

    public function update(AuthUser $authUser, KpiMetric $kpiMetric): bool
    {
        return $authUser->can('Update:KpiMetric');
    }

    public function delete(AuthUser $authUser, KpiMetric $kpiMetric): bool
    {
        return $authUser->can('Delete:KpiMetric');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:KpiMetric');
    }

    public function restore(AuthUser $authUser, KpiMetric $kpiMetric): bool
    {
        return $authUser->can('Restore:KpiMetric');
    }

    public function forceDelete(AuthUser $authUser, KpiMetric $kpiMetric): bool
    {
        return $authUser->can('ForceDelete:KpiMetric');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:KpiMetric');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:KpiMetric');
    }

    public function replicate(AuthUser $authUser, KpiMetric $kpiMetric): bool
    {
        return $authUser->can('Replicate:KpiMetric');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:KpiMetric');
    }
}
