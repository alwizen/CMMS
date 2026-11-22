<?php

namespace App\Filament\App\Resources\WorkOrders;

use App\Filament\App\Resources\WorkOrders\Pages\ListWorkOrders;
use App\Filament\App\Resources\WorkOrders\Pages\ViewWorkOrder;
use App\Filament\App\Resources\WorkOrders\RelationManagers\ActivitiesRelationManager;
use App\Filament\App\Resources\WorkOrders\Tables\WorkOrdersTable;
use App\Filament\Resources\WorkOrders\Schemas\WorkOrderInfolist;
use App\Models\WorkOrder;
use App\Support\UserVisibility;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class WorkOrderResource extends Resource
{
    protected static ?string $model = WorkOrder::class;

    protected static ?string $navigationLabel = 'Work Order';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Pekerjaan';

    protected static ?int $navigationSort = 1;

    /**
     * Izin menu/aksi mengikuti policy (Shield). Scoping record tetap
     * membatasi data ke WO milik user/fungsinya.
     */
    public static function canView($record): bool
    {
        return parent::canView($record)
            && UserVisibility::canAccessWorkOrder($record, auth()->user());
    }

    public static function getEloquentQuery(): Builder
    {
        return UserVisibility::scopeWorkOrders(parent::getEloquentQuery(), auth()->user());
    }

    public static function table(Table $table): Table
    {
        return WorkOrdersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WorkOrderInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            ActivitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkOrders::route('/'),
            'view' => ViewWorkOrder::route('/{record}'),
        ];
    }
}
