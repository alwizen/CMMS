<?php

namespace App\Filament\Resources\HousekeepingTasks;

use App\Filament\Resources\HousekeepingTasks\Pages\CreateHousekeepingTask;
use App\Filament\Resources\HousekeepingTasks\Pages\EditHousekeepingTask;
use App\Filament\Resources\HousekeepingTasks\Pages\ListHousekeepingTasks;
use App\Filament\Resources\HousekeepingTasks\Schemas\HousekeepingTaskForm;
use App\Filament\Resources\HousekeepingTasks\Tables\HousekeepingTasksTable;
use App\Models\HousekeepingTask;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class HousekeepingTaskResource extends Resource
{
    protected static ?string $model = HousekeepingTask::class;

    protected static ?string $navigationLabel = 'Master Checklist';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Housekeeping';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return HousekeepingTaskForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HousekeepingTasksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHousekeepingTasks::route('/'),
            'create' => CreateHousekeepingTask::route('/create'),
            'edit' => EditHousekeepingTask::route('/{record}/edit'),
        ];
    }
}
