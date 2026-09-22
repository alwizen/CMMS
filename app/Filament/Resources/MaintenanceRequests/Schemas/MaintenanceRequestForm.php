<?php

namespace App\Filament\Resources\MaintenanceRequests\Schemas;

use App\Models\Equipment;
use App\Models\EquipmentType;
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
                        TextInput::make('request_number')
                            ->label('Request Number')
                            ->disabled()
                            ->dehydrated()
                            ->placeholder('Auto-generated'),
                        Select::make('reported_by')
                            ->label('Reported By')
                            ->options(User::pluck('name', 'id'))
                            ->required()
                            ->placeholder('Select reporter'),
                        Select::make('operation_status')
                            ->label('Operation Status')
                            ->options([
                                'Running' => 'Running',
                                'Stopped' => 'Stopped',
                                'Standby' => 'Standby',
                                'Faulty' => 'Faulty',
                            ])
                            ->required()
                            ->placeholder('Select operation status'),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'Open' => 'Open',
                                'Assigned' => 'Assigned',
                                'In Progress' => 'In Progress',
                                'Completed' => 'Completed',
                                'Rejected' => 'Rejected',
                            ])
                            ->default('Open')
                            ->required()
                            ->placeholder('Select status'),
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
