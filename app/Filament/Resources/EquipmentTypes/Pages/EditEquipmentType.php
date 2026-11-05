<?php

namespace App\Filament\Resources\EquipmentTypes\Pages;

use App\Filament\Resources\EquipmentTypes\EquipmentTypeResource;
use App\Support\Code;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEquipmentType extends EditRecord
{
    protected static string $resource = EquipmentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (blank($data['code'] ?? null)) {
            $data['code'] = Code::fromName($data['name'] ?? null);
        }

        return $data;
    }
}
