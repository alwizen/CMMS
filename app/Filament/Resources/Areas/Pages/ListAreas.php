<?php

namespace App\Filament\Resources\Areas\Pages;

use App\Filament\Imports\AreaImporter;
use App\Filament\Resources\Areas\AreaResource;
use Filament\Actions;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListAreas extends ListRecords
{
    protected static string $resource = AreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(AreaImporter::class),
            Actions\CreateAction::make(),
        ];
    }
}
