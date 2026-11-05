<?php

namespace App\Filament\Resources\MaintenanceSchedules\Schemas;

use App\Models\Equipment;
use App\Models\MaintenancePlan;
use App\Models\MaintenanceSchedule;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                        TextInput::make('schedule_number')
                            ->label('Schedule Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Dibuat otomatis saat disimpan'),
                        Select::make('maintenance_plan_id')
                            ->label('Maintenance Plan')
                            ->options(fn (): array => MaintenancePlan::query()
                                ->with('equipment')
                                ->orderByDesc('id')
                                ->get()
                                ->mapWithKeys(fn (MaintenancePlan $plan): array => [
                                    $plan->id => ($plan->plan_number ?? '#'.$plan->id)
                                        .' — '.($plan->equipment?->tag_number ?? '-'),
                                ])
                                ->all())
                            ->searchable()
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
                        Select::make('status')
                            ->label('Status')
                            ->options(MaintenanceSchedule::STATUSES)
                            ->default('Scheduled')
                            ->required(),
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
