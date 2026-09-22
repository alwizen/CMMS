<?php

namespace App\Filament\Resources\Des\Pages;

use App\Filament\Resources\Des\DesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDes extends ListRecords
{
    protected static string $resource = DesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
