<?php

namespace App\Filament\App\Resources\MaintenanceRequests\Schemas;

use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\MaintenanceRequest;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class MaintenanceRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('reported_by')
                    ->default(fn (): ?int => auth()->id())
                    ->dehydrated(fn (string $operation): bool => $operation === 'create'),
                Hidden::make('status')
                    ->default(MaintenanceRequest::STATUSES['Open'])
                    ->dehydrated(fn (string $operation): bool => $operation === 'create'),

                Section::make('Equipment')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->schema([
                        Select::make('equipment_type_id')
                            ->label('Equipment Type')
                            ->options(EquipmentType::pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->placeholder('Pilih tipe equipment')
                            ->afterStateUpdated(fn (Set $set) => $set('equipment_id', null)),
                        Select::make('equipment_id')
                            ->label('Equipment (Tag Number)')
                            ->options(fn (Get $get): array => Equipment::query()
                                ->where('equipment_type_id', $get('equipment_type_id'))
                                ->orderBy('tag_number')
                                ->pluck('tag_number', 'id')
                                ->all())
                            ->searchable()
                            ->required()
                            ->placeholder('Pilih equipment')
                            ->visible(fn (Get $get): bool => filled($get('equipment_type_id'))),
                    ])
                    ->columns(2),

                Section::make('Detail Kerusakan')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->schema([
                        Select::make('operation_status')
                            ->label('Operation Status')
                            ->options([
                                'Running' => 'Running',
                                'Stopped' => 'Stopped',
                                'Standby' => 'Standby',
                                'Faulty' => 'Faulty',
                            ])
                            ->required(),
                        DatePicker::make('damage_date')
                            ->label('Tanggal Kerusakan')
                            ->required()
                            ->default(now()),
                        TimePicker::make('damage_time')
                            ->label('Jam Kerusakan')
                            ->seconds(false)
                            ->default(now()->format('H:i')),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('equipment_condition')
                            ->label('Kondisi Equipment')
                            ->nullable(),
                        Textarea::make('impact')
                            ->label('Dampak')
                            ->nullable(),
                        Textarea::make('early_action')
                            ->label('Tindakan Awal')
                            ->nullable(),
                        FileUpload::make('photo')
                            ->label('Foto (maks. 3)')
                            ->image()
                            ->multiple()
                            ->maxFiles(3)
                            ->disk('public')
                            ->directory('maintenance-requests')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
