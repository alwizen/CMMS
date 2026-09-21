<?php

namespace App\Filament\Resources\MaintenanceRequests\Schemas;

use App\Models\Equipment;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MaintenanceRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Information')
                    ->schema([
                        Select::make('equipment_id')
                            ->label('Equipment')
                            ->options(Equipment::pluck('name', 'id'))
                            ->required()
                            ->placeholder('Select equipment'),
                        TextInput::make('request_number')
                            ->label('Request Number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g., MR-20260918-001'),
                        Select::make('reported_by')
                            ->label('Reported By')
                            ->options(User::pluck('name', 'id'))
                            ->required()
                            ->placeholder('Select reporter'),
                        TextInput::make('operation_status')
                            ->label('Operation Status')
                            ->required()
                            ->placeholder('e.g., Running, Stopped'),
                        TextInput::make('status')
                            ->label('Status')
                            ->required()
                            ->placeholder('e.g., Pending, Approved, Completed'),
                    ])
                    ->columns(2),

                Section::make('Damage Details')
                    ->schema([
                        TextInput::make('damage_date')
                            ->label('Damage Date')
                            ->type('date')
                            ->required(),
                        TextInput::make('damage_time')
                            ->label('Damage Time')
                            ->type('time')
                            ->nullable(),
                        Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->placeholder('Describe the issue or maintenance request...'),
                        Textarea::make('equipment_condition')
                            ->label('Equipment Condition')
                            ->nullable()
                            ->placeholder('Describe current equipment condition...'),
                    ])
                    ->columns(2),

                Section::make('Impact & Actions')
                    ->schema([
                        Textarea::make('impact')
                            ->label('Impact')
                            ->nullable()
                            ->placeholder('Describe impact of the issue...'),
                        Textarea::make('early_action')
                            ->label('Early Action')
                            ->nullable()
                            ->placeholder('Describe temporary actions taken...'),
                    ])
                    ->columns(2),
            ]);
    }
}
