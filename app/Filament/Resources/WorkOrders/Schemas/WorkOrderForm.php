<?php

namespace App\Filament\Resources\WorkOrders\Schemas;

use App\Models\Equipment;
use App\Models\MaintenancePlan;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WorkOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Information')
                    ->schema([
                        TextInput::make('work_order_number')
                            ->label('Work Order Number')
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('equipment_id')
                            ->label('Equipment')
                            ->options(Equipment::pluck('name', 'id'))
                            ->required()
                            ->placeholder('Select equipment'),
                        TextInput::make('classification')
                            ->label('Classification')
                            ->required()
                            ->placeholder('e.g., Preventive, Corrective'),
                        TextInput::make('interval')
                            ->label('Interval')
                            ->nullable()
                            ->placeholder('e.g., Monthly, Quarterly'),
                        TextInput::make('status')
                            ->label('Status')
                            ->required()
                            ->placeholder('e.g., Pending, In Progress, Completed'),
                    ])
                    ->columns(2),

                Section::make('Related Records')
                    ->schema([
                        Select::make('maintenance_plan_id')
                            ->label('Maintenance Plan')
                            ->options(MaintenancePlan::pluck('id', 'id'))
                            ->nullable()
                            ->placeholder('Select maintenance plan'),
                        Select::make('maintenance_schedule_id')
                            ->label('Maintenance Schedule')
                            ->options(MaintenanceSchedule::pluck('id', 'id'))
                            ->nullable()
                            ->placeholder('Select maintenance schedule'),
                        Select::make('maintenance_request_id')
                            ->label('Maintenance Request')
                            ->options(MaintenanceRequest::pluck('request_number', 'id'))
                            ->nullable()
                            ->placeholder('Select maintenance request'),
                    ])
                    ->columns(2),

                Section::make('Personnel & Schedule')
                    ->schema([
                        Select::make('issued_by')
                            ->label('Issued By')
                            ->options(User::pluck('name', 'id'))
                            ->required()
                            ->placeholder('Select issuer'),
                        Select::make('technician_coordinator_id')
                            ->label('Technician Coordinator')
                            ->options(User::pluck('name', 'id'))
                            ->nullable()
                            ->placeholder('Select coordinator'),
                        TextInput::make('start_at')
                            ->label('Start At')
                            ->type('datetime-local')
                            ->required(),
                        TextInput::make('finish_at')
                            ->label('Finish At')
                            ->type('datetime-local')
                            ->nullable(),
                        Textarea::make('note')
                            ->label('Note')
                            ->nullable()
                            ->placeholder('Enter work order notes...'),
                    ])
                    ->columns(2),
            ]);
    }
}
