<?php

namespace App\Filament\App\Resources\HousekeepingPlans\RelationManagers;

use App\Filament\Resources\HousekeepingPlans\RelationManagers\FacilitiesRelationManager as AdminFacilitiesRelationManager;
use Illuminate\Database\Eloquent\Model;

class FacilitiesRelationManager extends AdminFacilitiesRelationManager
{
    protected static bool $isLazy = false;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }
}
