<?php

namespace App\Filament\Resources\Equipment\Pages;

use App\Filament\Imports\EquipmentImporter;
use App\Filament\Resources\Equipment\EquipmentResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListEquipment extends ListRecords
{
    protected static string $resource = EquipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(EquipmentImporter::class),
            CreateAction::make(),
        ];
    }
}
