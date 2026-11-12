<?php

namespace App\Filament\Resources\HousekeepingManpowers;

use App\Filament\Resources\HousekeepingManpowers\Pages\CreateHousekeepingManpower;
use App\Filament\Resources\HousekeepingManpowers\Pages\EditHousekeepingManpower;
use App\Filament\Resources\HousekeepingManpowers\Pages\ListHousekeepingManpowers;
use App\Filament\Resources\HousekeepingManpowers\Schemas\HousekeepingManpowerForm;
use App\Filament\Resources\HousekeepingManpowers\Tables\HousekeepingManpowersTable;
use App\Models\HousekeepingManpower;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class HousekeepingManpowerResource extends Resource
{
    protected static ?string $model = HousekeepingManpower::class;

    protected static ?string $navigationLabel = 'Manpower';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Housekeeping';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return HousekeepingManpowerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HousekeepingManpowersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHousekeepingManpowers::route('/'),
            'create' => CreateHousekeepingManpower::route('/create'),
            'edit' => EditHousekeepingManpower::route('/{record}/edit'),
        ];
    }
}
