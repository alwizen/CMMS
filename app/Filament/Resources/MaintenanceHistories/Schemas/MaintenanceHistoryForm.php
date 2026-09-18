<?php

namespace App\Filament\Resources\MaintenanceHistories\Schemas;

use App\Models\Equipment;
use App\Models\WorkOrder;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class MaintenanceHistoryForm
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
                Textarea::make('description')
                    ->label('Description')
                    ->required()
                    ->placeholder('Describe the maintenance performed...'),
                TextInput::make('maintenance_date')
                    ->label('Maintenance Date')
                    ->type('datetime-local')
                    ->required(),
                Select::make('performed_by')
                    ->label('Performed By')
                    ->options(User::pluck('name', 'id'))
                    ->required()
                    ->placeholder('Select technician'),
                Textarea::make('findings')
                    ->label('Findings')
                    ->nullable()
                    ->placeholder('Enter findings from maintenance...'),
                Textarea::make('actions_taken')
                    ->label('Actions Taken')
                    ->nullable()
                    ->placeholder('Describe actions taken...'),
                TextInput::make('status')
                    ->label('Status')
                    ->required()
                    ->placeholder('e.g., Completed, In Progress'),
                TextInput::make('duration_minutes')
                    ->label('Duration (minutes)')
                    ->numeric()
                    ->nullable()
                    ->placeholder('e.g., 120'),
                Textarea::make('notes')
                    ->label('Notes')
                    ->nullable()
                    ->placeholder('Enter additional notes...'),
            ]);
    }
}
