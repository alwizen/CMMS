<?php

namespace App\Filament\Resources\HousekeepingPlans\Schemas;

use App\Models\HousekeepingArea;
use App\Models\HousekeepingPlan;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HousekeepingPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plan Information')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('plan_number')
                            ->label('Plan Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Otomatis'),
                        DatePicker::make('plan_date')
                            ->label('Plan Date')
                            ->required()
                            ->default(now()),
                        Select::make('housekeeping_area_id')
                            ->label('Area Housekeeping')
                            ->options(fn (): array => HousekeepingArea::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (HousekeepingArea $area): array => [
                                    $area->id => $area->name.' — '.($area->company?->name ?? '-'),
                                ])
                                ->all())
                            ->searchable()
                            ->required()
                            ->placeholder('Select area'),
                        Select::make('status')
                            ->label('Status')
                            ->options(HousekeepingPlan::STATUSES)
                            ->default('Draft')
                            ->required(),
                        Textarea::make('note')
                            ->label('Note')
                            ->nullable()
                            ->columnSpanFull()
                            ->placeholder('Enter plan notes...'),
                    ])
                    ->columns(2),
            ]);
    }
}
