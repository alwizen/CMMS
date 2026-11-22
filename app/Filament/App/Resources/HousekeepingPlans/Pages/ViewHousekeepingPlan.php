<?php

namespace App\Filament\App\Resources\HousekeepingPlans\Pages;

use App\Filament\App\Resources\HousekeepingPlans\HousekeepingPlanResource;
use App\Filament\Resources\HousekeepingPlans\Schemas\HousekeepingPlanInfolist;
use App\Models\HousekeepingPlan;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewHousekeepingPlan extends ViewRecord
{
    protected static string $resource = HousekeepingPlanResource::class;

    public function resolveRecord(int|string $key): HousekeepingPlan
    {
        return HousekeepingPlanResource::getEloquentQuery()
            ->with([
                'area.company',
                'area.supervisor',
                'createdBy',
                'users',
                'tasks' => fn ($query) => $query->orderBy('id'),
                'tasks.facility',
                'facilities.facility',
            ])
            ->findOrFail($key);
    }

    public function infolist(Schema $schema): Schema
    {
        return HousekeepingPlanInfolist::configure($schema);
    }
}
