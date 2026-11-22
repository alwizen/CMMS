<?php

namespace App\Filament\App\Resources\HousekeepingRequests;

use App\Filament\App\Resources\HousekeepingRequests\Pages\CreateHousekeepingRequest;
use App\Filament\App\Resources\HousekeepingRequests\Pages\EditHousekeepingRequest;
use App\Filament\App\Resources\HousekeepingRequests\Pages\ListHousekeepingRequests;
use App\Filament\App\Resources\HousekeepingRequests\Pages\ViewHousekeepingRequest;
use App\Filament\App\Resources\HousekeepingRequests\Tables\HousekeepingRequestsTable;
use App\Filament\Resources\HousekeepingRequests\Schemas\HousekeepingRequestForm;
use App\Filament\Resources\HousekeepingRequests\Schemas\HousekeepingRequestInfolist;
use App\Models\HousekeepingRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class HousekeepingRequestResource extends Resource
{
    protected static ?string $model = HousekeepingRequest::class;

    protected static ?string $navigationLabel = 'Request Housekeeping';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Housekeeping';

    protected static ?int $navigationSort = 2;

    public static function canView($record): bool
    {
        $user = auth()->user();

        if (! parent::canView($record) || ! $record instanceof HousekeepingRequest) {
            return false;
        }

        return $user->hasRole('super_admin') || $record->requested_by === $user->id;
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();

        if (! parent::canEdit($record) || ! $record instanceof HousekeepingRequest) {
            return false;
        }

        return $record->isOpen()
            && ($record->requested_by === $user?->id || $user->hasRole('super_admin'));
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('super_admin')) {
            return $query;
        }

        return $query->where('requested_by', $user->id);
    }

    public static function form(Schema $schema): Schema
    {
        return HousekeepingRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HousekeepingRequestsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return HousekeepingRequestInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHousekeepingRequests::route('/'),
            'create' => CreateHousekeepingRequest::route('/create'),
            'view' => ViewHousekeepingRequest::route('/{record}'),
            'edit' => EditHousekeepingRequest::route('/{record}/edit'),
        ];
    }
}
