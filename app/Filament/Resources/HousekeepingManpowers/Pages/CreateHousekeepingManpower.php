<?php

namespace App\Filament\Resources\HousekeepingManpowers\Pages;

use App\Filament\Resources\HousekeepingManpowers\HousekeepingManpowerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHousekeepingManpower extends CreateRecord
{
    protected static string $resource = HousekeepingManpowerResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
