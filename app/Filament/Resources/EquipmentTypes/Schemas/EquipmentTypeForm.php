<?php

namespace App\Filament\Resources\EquipmentTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EquipmentTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->placeholder('e.g., PUMP, TANK, COMPRESSOR'),
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->placeholder('e.g., Pump, Storage Tank'),
                Textarea::make('description')
                    ->label('Description')
                    ->nullable()
                    ->placeholder('Enter equipment type description...'),
                Toggle::make('status')
                    ->label('Active')
                    ->default(true),
            ]);
    }
}
