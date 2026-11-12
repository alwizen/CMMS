<?php

namespace App\Filament\Resources\HousekeepingAreas\Pages;

use App\Filament\Resources\HousekeepingAreas\HousekeepingAreaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHousekeepingAreas extends ListRecords
{
    protected static string $resource = HousekeepingAreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
