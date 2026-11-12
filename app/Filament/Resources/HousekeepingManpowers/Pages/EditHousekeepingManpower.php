<?php

namespace App\Filament\Resources\HousekeepingManpowers\Pages;

use App\Filament\Resources\HousekeepingManpowers\HousekeepingManpowerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHousekeepingManpower extends EditRecord
{
    protected static string $resource = HousekeepingManpowerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
