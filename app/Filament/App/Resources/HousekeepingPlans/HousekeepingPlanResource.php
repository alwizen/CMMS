<?php

namespace App\Filament\App\Resources\HousekeepingPlans;

use App\Filament\App\Resources\HousekeepingPlans\Pages\ListHousekeepingPlans;
use App\Filament\App\Resources\HousekeepingPlans\Pages\ViewHousekeepingPlan;
use App\Filament\App\Resources\HousekeepingPlans\RelationManagers\TasksRelationManager;
use App\Filament\App\Resources\HousekeepingPlans\Tables\HousekeepingPlansTable;
use App\Filament\Resources\HousekeepingPlans\Schemas\HousekeepingPlanInfolist;
use App\Models\HousekeepingPlan;
use App\Support\HousekeepingVisibility;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class HousekeepingPlanResource extends Resource
{
    protected static ?string $model = HousekeepingPlan::class;

    protected static ?string $navigationLabel = 'Plan Harian';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Housekeeping';

    protected static ?int $navigationSort = 1;

    public static function canView($record): bool
    {
        return parent::canView($record)
            && HousekeepingVisibility::canViewPlan(auth()->user(), $record->getKey());
    }

    public static function getEloquentQuery(): Builder
    {
        return HousekeepingVisibility::scopePlans(parent::getEloquentQuery(), auth()->user());
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
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHousekeepingPlans::route('/'),
            'view' => ViewHousekeepingPlan::route('/{record}'),
        ];
    }
}
