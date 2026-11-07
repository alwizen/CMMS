<?php

namespace App\Filament\Resources\MeterLogs\Schemas;

use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\MeterLog;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class MeterLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Meter Log Information')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('equipment_type_id')
                            ->label('Equipment Type')
                            ->options(fn (): array => EquipmentType::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->live()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Select $component, ?MeterLog $record): void {
                                $component->state($record?->equipment?->equipment_type_id);
                            })
                            ->afterStateUpdated(fn (Set $set) => $set('equipment_id', null))
                            ->placeholder('Select equipment type'),
                        Select::make('equipment_id')
                            ->label('Equipment')
                            ->options(fn (Get $get): array => Equipment::query()
                                ->when(
                                    filled($get('equipment_type_id')),
                                    fn ($query) => $query->where('equipment_type_id', $get('equipment_type_id')),
                                )
                                ->orderBy('tag_number')
                                ->pluck('tag_number', 'id')
                                ->all())
                            ->searchable()
                            ->required()
                            ->disabled(fn (Get $get): bool => blank($get('equipment_type_id')))
                            ->helperText(fn (Get $get): string => blank($get('equipment_type_id'))
                                ? 'Pilih equipment type terlebih dahulu.'
                                : 'Hanya equipment dengan tipe terpilih yang ditampilkan.')
                            ->placeholder('Select equipment'),
                        TextInput::make('reading_date')
                            ->label('Reading Date')
                            ->default(now()->format('Y-m-d'))
                            ->type('date')
                            ->required(),
                        TextInput::make('value')
                            ->label('Value')
                            ->numeric()
                            ->required()
                            ->placeholder('e.g., 1200.50'),
                        Select::make('recorded_by')
                            ->label('Recorded By')
                            ->options(User::pluck('name', 'id'))
                            ->required()
                            ->placeholder('Select recorder'),
                    ])
                    ->columns(2),
            ]);
    }
}
