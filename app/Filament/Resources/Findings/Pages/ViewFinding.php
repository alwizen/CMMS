<?php

namespace App\Filament\Resources\Findings\Pages;

use App\Filament\Resources\Findings\FindingResource;
use App\Filament\Resources\Findings\Schemas\FindingInfolist;
use App\Filament\Resources\WorkOrders\Actions\SubmitFindingRequestAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewFinding extends ViewRecord
{
    protected static string $resource = FindingResource::class;

    public function infolist(Schema $schema): Schema
    {
        return FindingInfolist::configure($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            SubmitFindingRequestAction::make(),
        ];
    }
}
