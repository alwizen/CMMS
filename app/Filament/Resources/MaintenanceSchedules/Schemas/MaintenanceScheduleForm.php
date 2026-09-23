<?php

namespace App\Filament\Resources\MaintenanceSchedules\Schemas;

use App\Models\MaintenancePlan;
use App\Models\Equipment;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MaintenanceScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Schedule Information')
                    ->schema([
                        Select::make('maintenance_plan_id')
                            ->label('Maintenance Plan')
                            ->options(MaintenancePlan::pluck('id', 'id'))
                            ->required()
                            ->placeholder('Select maintenance plan'),
                        Select::make('equipment_id')
                            ->label('Equipment')
                            ->options(Equipment::pluck('tag_number', 'id'))
                            ->required()
                            ->placeholder('Select equipment'),
                        TextInput::make('scheduled_date')
                            ->label('Scheduled Date')
                            ->type('date')
                            ->required(),
                        TextInput::make('status')
                            ->label('Status')
                            ->required()
                            ->placeholder('e.g., Pending, Rescheduled, Done'),
                        TextInput::make('rescheduled_from')
                            ->label('Rescheduled From')
                            ->type('date')
                            ->nullable(),
                        Textarea::make('description')
                            ->label('Description')
                            ->nullable()
                            ->placeholder('Enter schedule description...'),
                    ])
                    ->columns(2),
            ]);
    }
}
