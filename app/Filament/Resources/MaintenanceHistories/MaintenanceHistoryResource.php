<?php

namespace App\Filament\Resources\MaintenanceHistories;

use App\Filament\Resources\MaintenanceHistories\Pages\CreateMaintenanceHistory;
use App\Filament\Resources\MaintenanceHistories\Pages\EditMaintenanceHistory;
use App\Filament\Resources\MaintenanceHistories\Pages\ListMaintenanceHistories;
use App\Filament\Resources\MaintenanceHistories\Schemas\MaintenanceHistoryForm;
use App\Filament\Resources\MaintenanceHistories\Tables\MaintenanceHistoriesTable;
use App\Models\MaintenanceHistory;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaintenanceHistories::route('/'),
            'create' => CreateMaintenanceHistory::route('/create'),
            'edit' => EditMaintenanceHistory::route('/{record}/edit'),
        ];
    }
}
