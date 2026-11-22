<?php

namespace App\Filament\App\Resources\HousekeepingRequests\Pages;

use App\Filament\App\Resources\HousekeepingRequests\HousekeepingRequestResource;
use App\Filament\Resources\HousekeepingRequests\Schemas\HousekeepingRequestInfolist;
use App\Models\HousekeepingRequest;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewHousekeepingRequest extends ViewRecord
{
    protected static string $resource = HousekeepingRequestResource::class;

    public function resolveRecord(int|string $key): HousekeepingRequest
    {
        return HousekeepingRequestResource::getEloquentQuery()
            ->with(['area', 'facility', 'requestedBy', 'approvedBy', 'plan'])
            ->findOrFail($key);
    }

    public function infolist(Schema $schema): Schema
    {
        return HousekeepingRequestInfolist::configure($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => HousekeepingRequestResource::canEdit($this->record)),
        ];
    }
}
