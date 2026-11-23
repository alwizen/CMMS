<?php

namespace App\Filament\Resources\HousekeepingAreas\RelationManagers;

use App\Filament\Actions\CreateRelatedAction;
use App\Forms\Components\LeafletMap;
use App\Models\HousekeepingFacility;
use App\Models\HousekeepingTask;
use App\Support\Geo;
use App\Support\HousekeepingRole;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FacilitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'facilities';

    protected static ?string $title = 'Fasilitas / Objek Area';

    protected static ?string $modelLabel = 'facility';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Fasilitas')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Fasilitas')
                            ->columnSpanFull()
                            ->required()
                            ->placeholder('e.g., Oil Catcher, Selokan, Taman'),
                        Textarea::make('description')
                            ->label('Description')
                            ->nullable()
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make('Boundary & Size')
                    ->description('Gambar polygon fasilitas di dalam batas area housekeeping. Luas dihitung otomatis.')
                    ->columnSpanFull()
                    ->schema([
                        LeafletMap::make('polygon')
                            ->label('Batas Fasilitas')
                            ->mode('polygon')
                            ->height(450)
                            ->columnSpanFull()
                            ->referencePolygons(fn (): array => $this->areaPolygon())
                            ->live()
                            ->afterStateHydrated(function (LeafletMap $component, $record): void {
                                if (is_array($record?->polygon) && $record->polygon !== []) {
                                    $component->state($record->polygon);
                                }
                            })
                            ->afterStateUpdated(function (?array $state, Set $set): void {
                                if (empty($state)) {
                                    $set('area_size', null);
                                    $set('latitude', null);
                                    $set('longitude', null);

                                    return;
                                }

                                $set('area_size', Geo::polygonArea($state));

                                $center = Geo::polygonCenter($state);

                                if ($center) {
                                    $set('latitude', $center['lat']);
                                    $set('longitude', $center['lng']);
                                }
                            }),
                        TextInput::make('area_size')
                            ->label('Area Size (m²)')
                            ->numeric()
                            ->readOnly()
                            ->placeholder('Otomatis dari polygon')
                            ->helperText('Luas dihitung otomatis dari polygon (meter persegi).'),
                        Hidden::make('latitude'),
                        Hidden::make('longitude'),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Polygon area housekeeping sebagai batas acuan saat menggambar fasilitas.
     *
     * @return array<int, array<int, array{0: float|int, 1: float|int}>>
     */
    protected function areaPolygon(): array
    {
        $polygon = $this->getOwnerRecord()->polygon;

        return is_array($polygon) && count($polygon) >= 3 ? [$polygon] : [];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Fasilitas')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tasks_count')
                    ->label('Checklist')
                    ->counts('tasks')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('description')
                    ->label('Description')
                    ->placeholder('-')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('area_size')
                    ->label('Luas (m²)')
                    ->numeric(decimalPlaces: 2)
                    ->placeholder('-')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                $this->checklistAction(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    protected function checklistAction(): CreateRelatedAction
    {
        return CreateRelatedAction::make('checklist')
            ->relatedModel(HousekeepingTask::class)
            ->label('Checklist')
            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
            ->color('gray')
            // Filament memanggil ulang closure ini dengan $record = null saat
            // tombol "Buat & buat lagi" me-render ulang modal, jadi closure
            // harus aman terhadap null.
            ->relationship(fn (?HousekeepingFacility $record) => $record?->tasks())
            ->modalHeading(fn (?HousekeepingFacility $record): string => 'Checklist — '.($record?->name ?? '-'))
            ->modalWidth(Width::TwoExtraLarge)
            ->createAnother(true)
            ->modalContent(fn (?HousekeepingFacility $record) => $record === null
                ? null
                : view('filament.relation-managers.facility-checklist', [
                    'record' => $record->load('tasks'),
                ]))
            ->schema(fn (): array => [
                TextInput::make('name')
                    ->label('Nama Checklist')
                    ->required()
                    ->placeholder('e.g., Pemotongan Rumput, Pembersihan Selokan'),
                Textarea::make('description')
                    ->label('Description')
                    ->nullable()
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ])
            ->visible(fn (): bool => HousekeepingRole::canManagePlan(auth()->user()))
            ->successNotificationTitle('Checklist ditambahkan');
    }
}
