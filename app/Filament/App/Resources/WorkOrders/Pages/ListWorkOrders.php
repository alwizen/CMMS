<?php

namespace App\Filament\App\Resources\WorkOrders\Pages;

use App\Filament\App\Resources\WorkOrders\WorkOrderResource;
use Filament\Resources\Pages\ListRecords;

class ListWorkOrders extends ListRecords
{
    protected static string $resource = WorkOrderResource::class;

    public function getTitle(): string
    {
        return 'Work Order';
    }
}
