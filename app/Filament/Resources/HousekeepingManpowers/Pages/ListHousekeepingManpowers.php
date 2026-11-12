<?php

namespace App\Filament\Resources\HousekeepingManpowers\Pages;

use App\Filament\Imports\HousekeepingManpowerImporter;
use App\Filament\Resources\HousekeepingManpowers\HousekeepingManpowerResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListHousekeepingManpowers extends ListRecords
{
    protected static string $resource = HousekeepingManpowerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(HousekeepingManpowerImporter::class),
            CreateAction::make(),
        ];
    }
}
