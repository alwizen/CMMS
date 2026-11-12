<?php

namespace App\Filament\Resources\HousekeepingAreas;

use App\Filament\Resources\HousekeepingAreas\Pages\CreateHousekeepingArea;
use App\Filament\Resources\HousekeepingAreas\Pages\EditHousekeepingArea;
use App\Filament\Resources\HousekeepingAreas\Pages\ListHousekeepingAreas;
use App\Filament\Resources\HousekeepingAreas\RelationManagers\FacilitiesRelationManager;
use App\Filament\Resources\HousekeepingAreas\Schemas\HousekeepingAreaForm;
use App\Filament\Resources\HousekeepingAreas\Tables\HousekeepingAreasTable;
use App\Models\HousekeepingArea;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class HousekeepingAreaResource extends Resource
{
    protected static ?string $model = HousekeepingArea::class;

    protected static ?string $navigationLabel = 'Housekeeping Area';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Housekeeping';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return HousekeepingAreaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HousekeepingAreasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            FacilitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHousekeepingAreas::route('/'),
            'create' => CreateHousekeepingArea::route('/create'),
            'edit' => EditHousekeepingArea::route('/{record}/edit'),
        ];
    }
}
