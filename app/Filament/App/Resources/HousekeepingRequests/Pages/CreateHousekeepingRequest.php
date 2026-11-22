<?php

namespace App\Filament\App\Resources\HousekeepingRequests\Pages;

use App\Filament\App\Resources\HousekeepingRequests\HousekeepingRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHousekeepingRequest extends CreateRecord
{
    protected static string $resource = HousekeepingRequestResource::class;

    protected function getRedirectUrl(): string
    {
        return HousekeepingRequestResource::getUrl('view', ['record' => $this->record]);
    }
}
