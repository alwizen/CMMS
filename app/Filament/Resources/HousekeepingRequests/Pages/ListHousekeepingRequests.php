<?php

namespace App\Filament\Resources\HousekeepingRequests\Pages;

use App\Filament\Resources\HousekeepingRequests\HousekeepingRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHousekeepingRequests extends ListRecords
{
    protected static string $resource = HousekeepingRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
