<?php

namespace App\Filament\Resources\Areas\Pages;

use App\Filament\Resources\Areas\AreaResource;
use App\Filament\Resources\Equipment\EquipmentResource;
use Filament\Resources\Pages\ManageRelatedRecords;

class ManageAreaEquipment extends ManageRelatedRecords
{
    protected static string $resource = AreaResource::class;

    protected static ?string $relatedResource = EquipmentResource::class;

    protected static string $relationship = 'equipment';

    protected static ?string $navigationLabel = 'Equipment';
}
