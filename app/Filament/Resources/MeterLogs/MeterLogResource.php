<?php

namespace App\Filament\Resources\MeterLogs;

use App\Filament\Resources\MeterLogs\Pages\CreateMeterLog;
use App\Filament\Resources\MeterLogs\Pages\EditMeterLog;
use App\Filament\Resources\MeterLogs\Pages\ListMeterLogs;
use App\Filament\Resources\MeterLogs\Schemas\MeterLogForm;
use App\Filament\Resources\MeterLogs\Tables\MeterLogsTable;
use App\Models\MeterLog;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MeterLogResource extends Resource
{
    protected static ?string $model = MeterLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Riwayat & Monitoring';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return MeterLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MeterLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeterLogs::route('/'),
            'create' => CreateMeterLog::route('/create'),
            'edit' => EditMeterLog::route('/{record}/edit'),
        ];
    }
}
