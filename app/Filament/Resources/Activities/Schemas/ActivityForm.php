<?php

namespace App\Filament\Resources\Activities\Schemas;

use App\Models\EquipmentType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ActivityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('equipment_type_id')
                    ->label('Equipment Type')
                    ->options(EquipmentType::pluck('name', 'id'))
                    ->required()
                    ->placeholder('Select equipment type'),
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->placeholder('e.g., Pump Oil Change'),
                TextInput::make('type')
                    ->label('Type')
                    ->required()
                    ->placeholder('e.g., Maintenance, Inspection, Testing'),
                TextInput::make('maintenance_classification')
                    ->label('Maintenance Classification')
                    ->required()
                    ->placeholder('e.g., Preventive, Corrective'),
                TextInput::make('interval')
                    ->label('Interval')
                    ->nullable()
                    ->placeholder('e.g., Daily, Monthly, Yearly'),
                TextInput::make('answer_type')
                    ->label('Answer Type')
                    ->required()
                    ->placeholder('e.g., Qualitative, Quantitative'),
                Textarea::make('reference')
                    ->label('Reference')
                    ->nullable()
                    ->placeholder('Enter reference information...'),
                TextInput::make('optimum')
                    ->label('Optimum')
                    ->numeric()
                    ->nullable()
                    ->placeholder('e.g., 50.0'),
                TextInput::make('minimum')
                    ->label('Minimum')
                    ->numeric()
                    ->nullable()
                    ->placeholder('e.g., 40.0'),
                TextInput::make('maximum')
                    ->label('Maximum')
                    ->numeric()
                    ->nullable()
                    ->placeholder('e.g., 80.0'),
                TextInput::make('unit')
                    ->label('Unit')
                    ->nullable()
                    ->placeholder('e.g., °C, mm/s, V'),
                Toggle::make('status')
                    ->label('Active')
                    ->default(true),
            ]);
    }
}
