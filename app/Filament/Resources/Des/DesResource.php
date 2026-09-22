<?php

namespace App\Filament\Resources\Des;

use App\Filament\Resources\Des\Pages\CreateDes;
use App\Filament\Resources\Des\Pages\EditDes;
use App\Filament\Resources\Des\Pages\ListDes;
use App\Filament\Resources\Des\Schemas\DesForm;
use App\Filament\Resources\Des\Tables\DesTable;
use App\Models\Des;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DesResource extends Resource
{
    protected static ?string $model = Des::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return DesForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDes::route('/'),
            'create' => CreateDes::route('/create'),
            'edit' => EditDes::route('/{record}/edit'),
        ];
    }
}
