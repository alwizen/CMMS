<?php

namespace App\Filament\Resources\HousekeepingRequests;

use App\Filament\Resources\HousekeepingRequests\Pages\CreateHousekeepingRequest;
use App\Filament\Resources\HousekeepingRequests\Pages\EditHousekeepingRequest;
use App\Filament\Resources\HousekeepingRequests\Pages\ListHousekeepingRequests;
use App\Filament\Resources\HousekeepingRequests\Pages\ViewHousekeepingRequest;
use App\Filament\Resources\HousekeepingRequests\Schemas\HousekeepingRequestForm;
use App\Filament\Resources\HousekeepingRequests\Schemas\HousekeepingRequestInfolist;
use App\Filament\Resources\HousekeepingRequests\Tables\HousekeepingRequestsTable;
use App\Models\HousekeepingRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class HousekeepingRequestResource extends Resource
{
    protected static ?string $model = HousekeepingRequest::class;

    protected static ?string $navigationLabel = 'Housekeeping Request';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Housekeeping';

    protected static ?int $navigationSort = 5;

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
