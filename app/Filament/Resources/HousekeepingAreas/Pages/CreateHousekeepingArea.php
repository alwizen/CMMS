<?php

namespace App\Filament\Resources\HousekeepingAreas\Pages;

use App\Filament\Resources\HousekeepingAreas\HousekeepingAreaResource;
use App\Support\Code;
use Filament\Resources\Pages\CreateRecord;

class CreateHousekeepingArea extends CreateRecord
{
    protected static string $resource = HousekeepingAreaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (blank($data['code'] ?? null)) {
            $data['code'] = Code::fromName($data['name'] ?? null);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
