<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Forms\Components\LeafletMap;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Company Information')
                    ->schema([
                        TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g., FT-TGL'),
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->placeholder('e.g., FT Tegal'),
                        Textarea::make('description')
                            ->label('Description')
                            ->nullable()
                            ->placeholder('Enter company description...'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make('Geofence Location')
                    ->description('Gambar polygon pada peta untuk menentukan batas area lokasi company.')
                    ->schema([
                        LeafletMap::make('geofence')
                            ->label('Geofence Map')
                            ->mode('polygon')
                            ->height(450)
                            ->columnSpanFull()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, ?array $state) {
                                if (empty($state)) {
                                    $set('center_lat', null);
                                    $set('center_lng', null);

                                    return;
                                }

                                $latSum = 0;
                                $lngSum = 0;
                                $count = count($state);

                                foreach ($state as $point) {
                                    $latSum += $point[0];
                                    $lngSum += $point[1];
                                }

                                $set('center_lat', round($latSum / $count, 7));
                                $set('center_lng', round($lngSum / $count, 7));
                            }),
                        TextInput::make('center_lat')
                            ->label('Center Latitude')
                            ->numeric()
                            ->nullable()
                            ->placeholder('Otomatis dari geofence'),
                        TextInput::make('center_lng')
                            ->label('Center Longitude')
                            ->numeric()
                            ->nullable()
                            ->placeholder('Otomatis dari geofence'),
                    ])
                    ->columns(2),
            ]);
    }
}
