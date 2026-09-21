<?php

namespace App\Filament\Resources\MeterLogs\Schemas;

use App\Models\Equipment;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MeterLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Meter Log Information')
                    ->schema([
                        Select::make('equipment_id')
                            ->label('Equipment')
                            ->options(Equipment::pluck('name', 'id'))
                            ->required()
                            ->placeholder('Select equipment'),
                        TextInput::make('reading_date')
                            ->label('Reading Date')
                            ->type('date')
                            ->required(),
                        TextInput::make('value')
                            ->label('Value')
                            ->numeric()
                            ->required()
                            ->placeholder('e.g., 1200.50'),
                        Select::make('recorded_by')
                            ->label('Recorded By')
                            ->options(User::pluck('name', 'id'))
                            ->required()
                            ->placeholder('Select recorder'),
                    ])
                    ->columns(2),
            ]);
    }
}
