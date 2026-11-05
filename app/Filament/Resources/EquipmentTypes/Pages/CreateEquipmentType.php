<?php

namespace App\Filament\Resources\EquipmentTypes\Pages;

use App\Filament\Resources\EquipmentTypes\EquipmentTypeResource;
use App\Support\Code;
use Filament\Resources\Pages\CreateRecord;

class CreateEquipmentType extends CreateRecord
{
    protected static string $resource = EquipmentTypeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (blank($data['code'] ?? null)) {
            $data['code'] = Code::fromName($data['name'] ?? null);
        }

        return $data;
    }
}
