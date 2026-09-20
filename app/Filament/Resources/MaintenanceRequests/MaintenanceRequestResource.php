<?php

namespace App\Filament\Resources\MaintenanceRequests;

use App\Filament\Resources\MaintenanceRequests\Pages\CreateMaintenanceRequest;
use App\Filament\Resources\MaintenanceRequests\Pages\EditMaintenanceRequest;
use App\Filament\Resources\MaintenanceRequests\Pages\ListMaintenanceRequests;
use App\Filament\Resources\MaintenanceRequests\Schemas\MaintenanceRequestForm;
use App\Filament\Resources\MaintenanceRequests\Tables\MaintenanceRequestsTable;
use App\Models\MaintenanceRequest;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MaintenanceRequestResource extends Resource
{
    protected static ?string $model = MaintenanceRequest::class;

    protected static ?string $navigationLabel = 'Maintenance Requests (Corrective)';
    
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|UnitEnum|null $navigationGroup = 'Maintenance';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return MaintenanceRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaintenanceRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaintenanceRequests::route('/'),
            'create' => CreateMaintenanceRequest::route('/create'),
            'edit' => EditMaintenanceRequest::route('/{record}/edit'),
        ];
    }
}
