<?php

namespace App\Filament\Resources\HousekeepingRequests\Pages;

use App\Filament\Resources\HousekeepingRequests\HousekeepingRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHousekeepingRequest extends CreateRecord
{
    protected static string $resource = HousekeepingRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['requested_by'] = auth()->id();
        $data['status'] = $data['status'] ?? 'Open';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
