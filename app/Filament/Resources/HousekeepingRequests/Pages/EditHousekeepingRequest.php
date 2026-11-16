<?php

namespace App\Filament\Resources\HousekeepingRequests\Pages;

use App\Filament\Resources\HousekeepingRequests\Actions\ApproveHousekeepingRequestAction;
use App\Filament\Resources\HousekeepingRequests\HousekeepingRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditHousekeepingRequest extends EditRecord
{
    protected static string $resource = HousekeepingRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            ApproveHousekeepingRequestAction::make(),
            DeleteAction::make(),
        ];
    }
}
