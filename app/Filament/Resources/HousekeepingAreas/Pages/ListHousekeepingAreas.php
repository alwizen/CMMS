<?php

namespace App\Filament\Resources\HousekeepingAreas\Pages;

use App\Filament\Imports\HousekeepingAreaImporter;
use App\Filament\Resources\HousekeepingAreas\HousekeepingAreaResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListHousekeepingAreas extends ListRecords
{
    protected static string $resource = HousekeepingAreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(HousekeepingAreaImporter::class)
                ->label('Import'),
            CreateAction::make(),
        ];
    }
}
