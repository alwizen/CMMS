<?php

namespace App\Filament\Resources\Activities\Schemas;

use App\Models\EquipmentType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ActivityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Information')
                    ->schema([
                        Select::make('equipment_type_id')
                            ->label('Equipment Type')
                            ->options(EquipmentType::pluck('name', 'id'))
                            ->required()
                            ->placeholder('Select equipment type'),
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->placeholder('e.g., Pump Oil Change'),
                        Select::make('type')
                            ->label('Type')
                            ->required()
                            ->options([
                                'maintenance' => 'Maintenance',
                                'inspection' => 'Inspection',
                                'testing' => 'Testing',
                            ])
                            ->placeholder('Select type'),
                        Select::make('maintenance_classification')
                            ->label('Maintenance Classification')
                            ->required()
                            ->options([
                                'preventive' => 'Preventive',
                                'corrective' => 'Corrective',
                                'predictive' => 'Predictive',
                                'condition_based' => 'Condition-Based',
                            ])
                            ->placeholder('Select classification'),
                        Select::make('interval')
                            ->label('Interval')
                            ->nullable()
                            ->options([
                                'daily' => 'Daily',
                                'weekly' => 'Weekly',
                                'monthly' => 'Monthly',
                                'quarterly' => 'Quarterly',
                                'semi_annual' => 'Semi-Annual',
                                'yearly' => 'Yearly',
                            ])
                            ->placeholder('Select interval'),
                        TextInput::make('answer_type')
                            ->label('Answer Type')
                            ->required()
                            ->placeholder('e.g., Qualitative, Quantitative'),
                        Textarea::make('reference')
                            ->label('Reference')
                            ->nullable()
                            ->placeholder('Enter reference information...'),
                    ])
                    ->columns(2),

                Section::make('Threshold Values')
                    ->schema([
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
                    ])
                    ->columns(2),

                Section::make('Status')
                    ->schema([
                        Toggle::make('status')
                            ->label('Active')
                            ->default(true),
                    ]),
            ]);
    }
}
