<?php

namespace App\Filament\Resources\EquipmentTypes\Pages;

use App\Filament\Imports\EquipmentTypeImporter;
use App\Filament\Resources\EquipmentTypes\EquipmentTypeResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListEquipmentTypes extends ListRecords
{
    protected static string $resource = EquipmentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(EquipmentTypeImporter::class),
            CreateAction::make(),
        ];
    }
}
