<?php

namespace App\Filament\Resources\HousekeepingAreas\Pages;

use App\Filament\Resources\HousekeepingAreas\HousekeepingAreaResource;
use App\Support\Code;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHousekeepingArea extends EditRecord
{
    protected static string $resource = HousekeepingAreaResource::class;

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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
