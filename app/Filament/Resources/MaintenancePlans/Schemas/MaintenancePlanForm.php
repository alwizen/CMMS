<?php

namespace App\Filament\Resources\MaintenancePlans\Schemas;

use App\Models\Equipment;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class MaintenancePlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('equipment_id')
                    ->label('Equipment')
                    ->options(Equipment::pluck('name', 'id'))
                    ->required()
                    ->placeholder('Select equipment'),
                TextInput::make('maintenance_classification')
                    ->label('Maintenance Classification')
                    ->required()
                    ->placeholder('e.g., Preventive, Corrective'),
                TextInput::make('interval')
                    ->label('Interval')
                    ->required()
                    ->placeholder('e.g., Daily, Monthly, Yearly'),
                TextInput::make('start_date')
                    ->label('Start Date')
                    ->type('date')
                    ->required(),
                TextInput::make('end_date')
                    ->label('End Date')
                    ->type('date')
                    ->nullable(),
                Select::make('created_by')
                    ->label('Created By')
                    ->options(User::pluck('name', 'id'))
                    ->required()
                    ->placeholder('Select creator'),
                Select::make('technician_coordinator_id')
                    ->label('Technician Coordinator')
                    ->options(User::pluck('name', 'id'))
                    ->nullable()
                    ->placeholder('Select coordinator'),
                Textarea::make('description')
                    ->label('Description')
                    ->nullable()
                    ->placeholder('Enter plan description...'),
                TextInput::make('status')
                    ->label('Status')
                    ->required()
                    ->placeholder('e.g., Active, Inactive'),
            ]);
    }
}
