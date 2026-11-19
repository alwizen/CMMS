<?php

namespace App\Filament\Resources\Equipment\Pages;

use App\Filament\Resources\Equipment\EquipmentResource;
use App\Filament\Resources\Equipment\Schemas\EquipmentInfolist;
use App\Models\Equipment;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewEquipment extends ViewRecord
{
    protected static string $resource = EquipmentResource::class;

    public function resolveRecord(int|string $key): Equipment
    {
        return Equipment::query()
            ->with(['equipmentType', 'area.company'])
            ->findOrFail($key);
    }

    public function infolist(Schema $schema): Schema
    {
        return EquipmentInfolist::configure($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
