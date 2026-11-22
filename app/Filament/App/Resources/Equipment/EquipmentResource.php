<?php

namespace App\Filament\App\Resources\Equipment;

use App\Filament\App\Resources\Equipment\Pages\ListEquipment;
use App\Filament\App\Resources\Equipment\Pages\ViewEquipment;
use App\Filament\App\Resources\Equipment\RelationManagers\MaintenanceHistoriesRelationManager;
use App\Filament\App\Resources\Equipment\Tables\EquipmentTable;
use App\Filament\Resources\Equipment\Schemas\EquipmentInfolist;
use App\Models\Equipment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class EquipmentResource extends Resource
{
    protected static ?string $model = Equipment::class;

    protected static ?string $navigationLabel = 'Equipment';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = 'Aset';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return EquipmentTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EquipmentInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            MaintenanceHistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEquipment::route('/'),
            'view' => ViewEquipment::route('/{record}'),
        ];
    }
}
