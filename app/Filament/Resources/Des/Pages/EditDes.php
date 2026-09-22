<?php

namespace App\Filament\Resources\Des\Pages;

use App\Filament\Resources\Des\DesResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDes extends EditRecord
{
    protected static string $resource = DesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
