<?php

namespace App\Filament\App\Resources\HousekeepingPlans\Pages;

use App\Filament\App\Resources\HousekeepingPlans\HousekeepingPlanResource;
use Filament\Resources\Pages\ListRecords;

class ListHousekeepingPlans extends ListRecords
{
    protected static string $resource = HousekeepingPlanResource::class;

    public function getTitle(): string
    {
        return 'Plan Harian';
    }
}
