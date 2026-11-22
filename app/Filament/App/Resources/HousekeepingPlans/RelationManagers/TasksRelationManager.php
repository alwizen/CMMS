<?php

namespace App\Filament\App\Resources\HousekeepingPlans\RelationManagers;

use App\Filament\Resources\HousekeepingPlans\RelationManagers\TasksRelationManager as AdminTasksRelationManager;
use Illuminate\Database\Eloquent\Model;

class TasksRelationManager extends AdminTasksRelationManager
{
    protected static bool $isLazy = false;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }
}
