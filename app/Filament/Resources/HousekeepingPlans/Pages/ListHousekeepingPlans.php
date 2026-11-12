<?php

namespace App\Filament\Resources\HousekeepingPlans\Pages;

use App\Filament\Resources\HousekeepingPlans\HousekeepingPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHousekeepingPlans extends ListRecords
{
    protected static string $resource = HousekeepingPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
