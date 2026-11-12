<?php

namespace App\Filament\Resources\HousekeepingPlans;

use App\Filament\Resources\HousekeepingPlans\Pages\CreateHousekeepingPlan;
use App\Filament\Resources\HousekeepingPlans\Pages\EditHousekeepingPlan;
use App\Filament\Resources\HousekeepingPlans\Pages\ListHousekeepingPlans;
use App\Filament\Resources\HousekeepingPlans\Pages\ViewHousekeepingPlan;
use App\Filament\Resources\HousekeepingPlans\RelationManagers\ManpowersRelationManager;
use App\Filament\Resources\HousekeepingPlans\RelationManagers\TasksRelationManager;
use App\Filament\Resources\HousekeepingPlans\Schemas\HousekeepingPlanForm;
use App\Filament\Resources\HousekeepingPlans\Schemas\HousekeepingPlanInfolist;
use App\Filament\Resources\HousekeepingPlans\Tables\HousekeepingPlansTable;
use App\Models\HousekeepingPlan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class HousekeepingPlanResource extends Resource
{
    protected static ?string $model = HousekeepingPlan::class;

    protected static ?string $navigationLabel = 'Plan Harian';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Housekeeping';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return HousekeepingPlanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HousekeepingPlansTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return HousekeepingPlanInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            TasksRelationManager::class,
            ManpowersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHousekeepingPlans::route('/'),
            'create' => CreateHousekeepingPlan::route('/create'),
            'view' => ViewHousekeepingPlan::route('/{record}'),
            'edit' => EditHousekeepingPlan::route('/{record}/edit'),
        ];
    }
}
