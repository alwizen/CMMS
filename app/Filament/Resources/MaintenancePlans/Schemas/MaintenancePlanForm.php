<?php

namespace App\Filament\Resources\MaintenancePlans\Schemas;

use App\Models\Equipment;
use App\Models\EquipmentType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MaintenancePlanForm
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
                            ->placeholder('Select equipment type')
                            ->reactive()
                            ->afterStateUpdated(fn ($set) => $set('equipment_id', null)),
                        Select::make('equipment_id')
                            ->label('Equipment (Tag Number)')
                            ->options(fn ($get) => Equipment::where('equipment_type_id', $get('equipment_type_id'))->pluck('tag_number', 'id'))
                            ->required()
                            ->placeholder('Select equipment')
                            ->visible(fn ($get) => filled($get('equipment_type_id'))),
                        Select::make('maintenance_classification')
                            ->label('Maintenance Classification')
                            ->options([
                                'Preventive' => 'Preventive',
                                'Corrective' => 'Corrective',
                            ])
                            ->required(),
                        Select::make('interval')
                            ->label('Maintenance Interval')
                            ->options([
                               'Daily' => 'Daily',
                                'Weekly' => 'Weekly',
                                'Monthly' => 'Monthly',
                                'Quarterly' => 'Quarterly',
                                'Yearly' => 'Yearly',
                            ])
                            ->required(),
                       
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'Active' => 'Active',
                                'Inactive' => 'Inactive',
                                'Suspended' => 'Suspended',
                                'Completed' => 'Completed',
                            ])
                            ->required()
                            ->default('Active'),
                    ])
                    ->columns(2),

                Section::make('Schedule')
                    ->schema([
                        TextInput::make('start_date')
                            ->label('Start Date')
                            ->type('date')
                            ->required(),
                        TextInput::make('end_date')
                            ->label('End Date')
                            ->type('date')
                            ->nullable(),
                    ])
                    ->columns(2),

                Section::make('Personnel & Details')
                    ->schema([
                        Select::make('technician_coordinator_id')
                            ->label('Technician Coordinator')
                            ->options(\App\Models\User::pluck('name', 'id'))
                            ->nullable()
                            ->placeholder('Select coordinator'),
                        Textarea::make('description')
                            ->label('Description')
                            ->nullable()
                            ->placeholder('Enter plan description...'),
                    ])
                    ->columns(2),
            ]);
    }
}
