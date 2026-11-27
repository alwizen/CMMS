<?php

namespace App\Filament\Resources\HousekeepingRequests\Schemas;

use App\Models\HousekeepingArea;
use App\Models\HousekeepingFacility;
use App\Models\HousekeepingRequest;
use App\Support\ImageCompressor;
use App\Support\Media;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class HousekeepingRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Request')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('request_number')
                            ->label('Request Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Otomatis'),
                        DatePicker::make('request_date')
                            ->label('Tanggal Request')
                            ->required()
                            ->default(now()),
                        TimePicker::make('request_time')
                            ->label('Jam Request')
                            ->seconds(false)
                            ->default(now()->format('H:i')),
                        Select::make('priority')
                            ->label('Prioritas')
                            ->options(HousekeepingRequest::PRIORITIES)
                            ->default('Normal')
                            ->required(),
                        Select::make('housekeeping_area_id')
                            ->label('Area Housekeeping')
                            ->options(fn (): array => HousekeepingArea::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->live()
                            ->required()
                            ->placeholder('Pilih area')
                            ->afterStateUpdated(fn (Set $set) => $set('housekeeping_facility_id', null)),
                        Select::make('housekeeping_facility_id')
                            ->label('Fasilitas')
                            ->options(fn (Get $get): array => HousekeepingFacility::query()
                                ->where('housekeeping_area_id', $get('housekeeping_area_id'))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->placeholder('Opsional')
                            ->helperText('Kosongkan bila request berlaku untuk seluruh area.'),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Jelaskan pekerjaan housekeeping yang dimohonkan...'),
                        FileUpload::make('photo')
                            ->label('Foto')
                            ->image()
                            ->multiple()
                            ->maxFiles(3)
                            ->disk(Media::disk())
                            ->directory('housekeeping/requests')
                            ->saveUploadedFileUsing(ImageCompressor::storeFilamentUpload(...))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
