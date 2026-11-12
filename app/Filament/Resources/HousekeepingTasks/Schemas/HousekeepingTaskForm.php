<?php

namespace App\Filament\Resources\HousekeepingTasks\Schemas;

use App\Models\HousekeepingArea;
use App\Models\HousekeepingFacility;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class HousekeepingTaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Checklist Information')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('area_id')
                            ->label('Area Housekeeping')
                            ->options(fn (): array => HousekeepingArea::query()
                                ->where('is_active', true)
                                ->with('company:id,name')
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (HousekeepingArea $area): array => [
                                    $area->id => $area->name.($area->company ? ' — '.$area->company->name : ''),
                                ])
                                ->all())
                            ->searchable()
                            ->live()
                            ->required()
                            ->afterStateUpdated(fn (Set $set) => $set('housekeeping_facility_id', null))
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Select $component, $record): void {
                                $component->state($record?->facility?->housekeeping_area_id);
                            })
                            ->placeholder('Select area'),
                        Select::make('housekeeping_facility_id')
                            ->label('Fasilitas / Objek Area')
                            ->options(fn (Get $get): array => HousekeepingFacility::query()
                                ->where('is_active', true)
                                ->when($get('area_id'), fn ($query, $areaId) => $query->where('housekeeping_area_id', $areaId))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required()
                            ->disabled(fn (Get $get): bool => blank($get('area_id')))
                            ->placeholder('Pilih area terlebih dahulu'),
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->placeholder('e.g., Pemotongan Rumput, Pembersihan Selokan'),
                        Textarea::make('description')
                            ->label('Description')
                            ->nullable()
                            ->columnSpanFull()
                            ->placeholder('Enter checklist description...'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
