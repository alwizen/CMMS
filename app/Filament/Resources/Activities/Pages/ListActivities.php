<?php

namespace App\Filament\Resources\Activities\Pages;

use App\Filament\Imports\ActivityImporter;
use App\Filament\Resources\Activities\ActivityResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->label('Import CSV')
                ->importer(ActivityImporter::class),
            CreateAction::make(),
        ];
    }
}
