<?php

namespace App\Filament\App\Resources\HousekeepingRequests\Pages;

use App\Filament\App\Resources\HousekeepingRequests\HousekeepingRequestResource;
use App\Models\HousekeepingRequest;
use Filament\Resources\Pages\EditRecord;

class EditHousekeepingRequest extends EditRecord
{
    protected static string $resource = HousekeepingRequestResource::class;

    public function resolveRecord(int|string $key): HousekeepingRequest
    {
        return HousekeepingRequestResource::getEloquentQuery()->findOrFail($key);
    }

    protected function getRedirectUrl(): string
    {
        return HousekeepingRequestResource::getUrl('view', ['record' => $this->record]);
    }
}
