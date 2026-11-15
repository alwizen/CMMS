<?php

namespace App\Filament\Resources\HousekeepingTasks\Pages;

use App\Filament\Imports\HousekeepingTaskImporter;
use App\Filament\Resources\HousekeepingTasks\HousekeepingTaskResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListHousekeepingTasks extends ListRecords
{
    protected static string $resource = HousekeepingTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(HousekeepingTaskImporter::class)
                ->label('Import'),
            CreateAction::make(),
        ];
    }
}
