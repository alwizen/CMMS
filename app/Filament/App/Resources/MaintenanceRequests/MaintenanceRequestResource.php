<?php

namespace App\Filament\App\Resources\MaintenanceRequests;

use App\Filament\App\Resources\MaintenanceRequests\Pages\CreateMaintenanceRequest;
use App\Filament\App\Resources\MaintenanceRequests\Pages\EditMaintenanceRequest;
use App\Filament\App\Resources\MaintenanceRequests\Pages\ListMaintenanceRequests;
use App\Filament\App\Resources\MaintenanceRequests\Pages\ViewMaintenanceRequest;
use App\Filament\App\Resources\MaintenanceRequests\Schemas\MaintenanceRequestForm;
use App\Filament\App\Resources\MaintenanceRequests\Tables\MaintenanceRequestsTable;
use App\Filament\Resources\MaintenanceRequests\Schemas\MaintenanceRequestInfolist;
use App\Models\MaintenanceRequest;
use App\Support\UserVisibility;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class MaintenanceRequestResource extends Resource
{
    protected static ?string $model = MaintenanceRequest::class;

    protected static ?string $navigationLabel = 'Request';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|UnitEnum|null $navigationGroup = 'Pekerjaan';

    protected static ?int $navigationSort = 2;

    public static function canView($record): bool
    {
        return parent::canView($record)
            && UserVisibility::canAccessRequest($record, auth()->user());
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();

        if (! parent::canEdit($record) || ! $record instanceof MaintenanceRequest) {
            return false;
        }

        if (! UserVisibility::canAccessRequest($record, $user)) {
            return false;
        }

        return $record->reported_by === $user?->id
            && $record->status === MaintenanceRequest::STATUSES['Open'];
    }

    public static function getEloquentQuery(): Builder
    {
        return UserVisibility::scopeRequests(parent::getEloquentQuery(), auth()->user());
    }

    public static function form(Schema $schema): Schema
    {
        return MaintenanceRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaintenanceRequestsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MaintenanceRequestInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaintenanceRequests::route('/'),
            'create' => CreateMaintenanceRequest::route('/create'),
            'view' => ViewMaintenanceRequest::route('/{record}'),
            'edit' => EditMaintenanceRequest::route('/{record}/edit'),
        ];
    }
}
