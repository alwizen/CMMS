<?php

namespace App\Filament\Resources\WorkOrders\Schemas;

use App\Models\Activity;
use App\Models\Equipment;
use App\Models\MaintenancePlan;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\Models\WorkOrder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                            ->options(Equipment::pluck('tag_number', 'id'))
                            ->required()
                            ->placeholder('Select equipment'),
                        Select::make('classification')
                            ->label('Classification')
                            ->options(WorkOrder::CLASSIFICATIONS)
                            ->required()
                            ->placeholder('Select classification'),
                        Select::make('interval')
                            ->label('Interval')
                            ->options(Activity::INTERVALS)
                            ->nullable()
                            ->placeholder('Select interval'),
                        Select::make('status')
                            ->label('Status')
                            ->options(WorkOrder::STATUSES)
                            ->required()
                            ->placeholder('Select status'),
                    ])
                    ->columns(2),

                Section::make('Related Records')
                    ->schema([
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
                            ->nullable()
                            ->placeholder('Select maintenance plan'),
                        Select::make('maintenance_schedule_id')
                            ->label('Maintenance Schedule')
                            ->options(fn (): array => MaintenanceSchedule::query()
                                ->orderByDesc('id')
                                ->get()
                                ->mapWithKeys(fn (MaintenanceSchedule $schedule): array => [
                                    $schedule->id => ($schedule->schedule_number ?? '#'.$schedule->id)
                                        .' — '.$schedule->scheduled_date?->format('d M Y'),
                                ])
                                ->all())
                            ->searchable()
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
                        DateTimePicker::make('start_at')
                            ->label('Start At')
                            ->required(),
                        DateTimePicker::make('finish_at')
                            ->label('Finish At')
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
