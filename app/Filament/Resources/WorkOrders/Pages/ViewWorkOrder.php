<?php

namespace App\Filament\Resources\WorkOrders\Pages;

use App\Filament\Resources\WorkOrders\Actions\ReviewWorkOrderAction;
use App\Filament\Resources\WorkOrders\Schemas\WorkOrderInfolist;
use App\Filament\Resources\WorkOrders\WorkOrderResource;
use App\Models\WorkOrder;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ViewWorkOrder extends ViewRecord
{
    protected static string $resource = WorkOrderResource::class;

    public function resolveRecord(int|string $key): WorkOrder
    {
        return WorkOrder::query()
            ->with([
                'equipment.equipmentType',
                'equipment.area',
                'issuedBy',
                'technicianCoordinator',
                'workOrderActivities.activity',
                'workOrderWorkers.user',
            ])
            ->findOrFail($key);
    }

    public function infolist(Schema $schema): Schema
    {
        return WorkOrderInfolist::configure($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('report')
                ->label('Generate Laporan')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->visible(fn (): bool => $this->record->status === WorkOrder::STATUSES['Completed'])
                ->url(fn (): string => route('work-orders.report.pdf', $this->record))
                ->openUrlInNewTab(),
            ReviewWorkOrderAction::approve(),
            ReviewWorkOrderAction::reject(),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
