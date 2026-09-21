<?php

namespace App\Filament\Resources\Equipment\Schemas;

use App\Models\Area;
use App\Models\EquipmentType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EquipmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Information')
                    ->schema([
                        Select::make('area_id')
                            ->label('Area')
                            ->options(Area::pluck('name', 'id'))
                            ->required()
                            ->placeholder('Select area'),
                        TextInput::make('tag_number')
                            ->label('Tag Number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g., P-101, T-001'),
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->placeholder('e.g., Pump, Storage Tank'),
                        Textarea::make('description')
                            ->label('Description')
                            ->nullable()
                            ->placeholder('Enter equipment description...'),
                        Select::make('equipment_type_id')
                            ->label('Equipment Type')
                            ->options(EquipmentType::pluck('name', 'id')),
                    ])
                    ->columns(2),

                Section::make('Technical Details')
                    ->schema([
                        TextInput::make('manufacturer')
                            ->label('Manufacturer')
                            ->nullable()
                            ->placeholder('e.g., GRUNDFOS, VESTOIL'),
                        TextInput::make('model')
                            ->label('Model')
                            ->nullable()
                            ->placeholder('e.g., CR32, STD-50000'),
                        TextInput::make('serial_number')
                            ->label('Serial Number')
                            ->nullable()
                            ->placeholder('e.g., SN-P-101'),
                        TextInput::make('installation_date')
                            ->label('Installation Date')
                            ->type('date')
                            ->nullable(),
                        TextInput::make('operational_unit')
                            ->label('Operational Unit')
                            ->nullable()
                            ->placeholder('e.g., hour, day, km'),
                    ])
                    ->columns(2),

                Section::make('Status & Media')
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'retired' => 'Retired',
                            ])
                            ->default('active')
                            ->required(),
                        Select::make('criticality')
                            ->label('Criticality')
                            ->options([
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'high' => 'High',
                                'critical' => 'Critical',
                            ])
                            ->default('medium')
                            ->required(),
                        FileUpload::make('photo')
                            ->label('Photo')
                            ->nullable(),
                    ])
                    ->columns(2),
            ]);
    }
}
