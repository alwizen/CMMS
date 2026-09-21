<?php

namespace App\Filament\Resources\MaintenanceHistories\Schemas;

use App\Models\Equipment;
use App\Models\WorkOrder;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MaintenanceHistoryForm
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
                        Select::make('work_order_id')
                            ->label('Work Order')
                            ->options(WorkOrder::pluck('work_order_number', 'id'))
                            ->nullable()
                            ->placeholder('Select work order'),
                        TextInput::make('maintenance_type')
                            ->label('Maintenance Type')
                            ->required()
                            ->placeholder('e.g., Preventive Maintenance, Inspection'),
                        TextInput::make('classification')
                            ->label('Classification')
                            ->required()
                            ->placeholder('e.g., Preventive, Corrective'),
                        TextInput::make('status')
                            ->label('Status')
                            ->required()
                            ->placeholder('e.g., Completed, In Progress'),
                    ])
                    ->columns(2),

                Section::make('Maintenance Details')
                    ->schema([
                        TextInput::make('maintenance_date')
                            ->label('Maintenance Date')
                            ->type('datetime-local')
                            ->required(),
                        Select::make('performed_by')
                            ->label('Performed By')
                            ->options(User::pluck('name', 'id'))
                            ->required()
                            ->placeholder('Select technician'),
                        TextInput::make('duration_minutes')
                            ->label('Duration (minutes)')
                            ->numeric()
                            ->nullable()
                            ->placeholder('e.g., 120'),
                        Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->placeholder('Describe the maintenance performed...'),
                    ])
                    ->columns(2),

                Section::make('Findings & Notes')
                    ->schema([
                        Textarea::make('findings')
                            ->label('Findings')
                            ->nullable()
                            ->placeholder('Enter findings from maintenance...'),
                        Textarea::make('actions_taken')
                            ->label('Actions Taken')
                            ->nullable()
                            ->placeholder('Describe actions taken...'),
                        Textarea::make('notes')
                            ->label('Notes')
                            ->nullable()
                            ->placeholder('Enter additional notes...'),
                    ])
                    ->columns(2),
            ]);
    }
}
