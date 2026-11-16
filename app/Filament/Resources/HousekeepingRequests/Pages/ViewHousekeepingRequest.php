<?php

namespace App\Filament\Resources\HousekeepingRequests\Pages;

use App\Filament\Resources\HousekeepingRequests\Actions\ApproveHousekeepingRequestAction;
use App\Filament\Resources\HousekeepingRequests\HousekeepingRequestResource;
use App\Filament\Resources\HousekeepingRequests\Schemas\HousekeepingRequestInfolist;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewHousekeepingRequest extends ViewRecord
{
    protected static string $resource = HousekeepingRequestResource::class;

    public function infolist(Schema $schema): Schema
    {
        return HousekeepingRequestInfolist::configure($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            ApproveHousekeepingRequestAction::make(),
            DeleteAction::make(),
        ];
    }
}
