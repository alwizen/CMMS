<?php

namespace App\Filament\Resources\Findings;

use App\Filament\Resources\Findings\Pages\ListFindings;
use App\Filament\Resources\Findings\Pages\ViewFinding;
use App\Filament\Resources\Findings\Schemas\FindingInfolist;
use App\Filament\Resources\Findings\Tables\FindingsTable;
use App\Models\WorkOrderFinding;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FindingResource extends Resource
{
    protected static ?string $model = WorkOrderFinding::class;

    protected static ?string $navigationLabel = 'Temuan';

    protected static ?string $modelLabel = 'Temuan';

    protected static ?string $pluralModelLabel = 'Temuan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|UnitEnum|null $navigationGroup = 'Maintenance';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        // Temuan dibuat otomatis dari hasil checklist WO.
        return false;
    }

    public static function table(Table $table): Table
    {
        return FindingsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FindingInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFindings::route('/'),
            'view' => ViewFinding::route('/{record}'),
        ];
    }
}
