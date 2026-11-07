<?php

namespace App\Filament\Resources\MaintenanceHistories;

use App\Filament\Resources\MaintenanceHistories\Pages\CreateMaintenanceHistory;
use App\Filament\Resources\MaintenanceHistories\Pages\EditMaintenanceHistory;
use App\Filament\Resources\MaintenanceHistories\Pages\ListMaintenanceHistories;
use App\Filament\Resources\MaintenanceHistories\Pages\ViewMaintenanceHistory;
use App\Filament\Resources\MaintenanceHistories\Schemas\MaintenanceHistoryForm;
use App\Filament\Resources\MaintenanceHistories\Schemas\MaintenanceHistoryInfolist;
use App\Filament\Resources\MaintenanceHistories\Tables\MaintenanceHistoriesTable;
use App\Models\MaintenanceHistory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class MaintenanceHistoryResource extends Resource
{
    protected static ?string $model = MaintenanceHistory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Riwayat & Monitoring';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return MaintenanceHistoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaintenanceHistoriesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MaintenanceHistoryInfolist::configure($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['workOrder.maintenanceRequest']);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaintenanceHistories::route('/'),
            'create' => CreateMaintenanceHistory::route('/create'),
            'view' => ViewMaintenanceHistory::route('/{record}'),
            'edit' => EditMaintenanceHistory::route('/{record}/edit'),
        ];
    }
}
