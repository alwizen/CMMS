<?php

namespace App\Filament\Resources\Areas\Schemas;

use App\Forms\Components\LeafletMap;
use App\Models\Company;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Area Information')
                    ->schema([
                        Select::make('company_id')
                            ->label('Company')
                            ->options(Company::pluck('name', 'id'))
                            ->required()
                            ->placeholder('Select company'),
                        TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->placeholder('e.g., LOAD, UNLOAD'),
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->placeholder('e.g., Loading, Unloading'),
                        Textarea::make('description')
                            ->label('Description')
                            ->nullable()
                            ->placeholder('Enter area description...'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make('Location')
                    ->description('Klik pada peta untuk menentukan titik lokasi area.')
                    ->schema([
                        LeafletMap::make('location')
                            ->label('Location Map')
                            ->mode('marker')
                            ->height(400)
                            ->columnSpanFull()
                            ->defaultLat(fn ($get): float => (float) (Company::find($get('company_id'))?->center_lat ?? -6.870255717160778))
                            ->defaultLng(fn ($get): float => (float) (Company::find($get('company_id'))?->center_lng ?? 109.18670476501677))
                            ->dehydrated(false)
                            ->afterStateHydrated(function (LeafletMap $component, $record): void {
                                if ($record?->latitude && $record?->longitude) {
                                    $component->state([
                                        'lat' => (float) $record->latitude,
                                        'lng' => (float) $record->longitude,
                                    ]);
                                }
                            })
                            ->afterStateUpdated(function ($set, ?array $state): void {
                                $set('latitude', $state['lat'] ?? null);
                                $set('longitude', $state['lng'] ?? null);
                            }),
                        Hidden::make('latitude'),
                        Hidden::make('longitude'),
                    ]),
            ]);
    }
}
