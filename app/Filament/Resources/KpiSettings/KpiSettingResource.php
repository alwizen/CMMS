<?php

namespace App\Filament\Resources\KpiSettings;

use App\Filament\Resources\KpiSettings\Pages\ListKpiSettings;
use App\Models\KpiMetric;
use App\Support\Kpi\KpiRegistry;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Konfigurasi target & bobot metrik KPI. Definisi metrik (formula) dikunci di
 * App\Support\Kpi\KpiRegistry; resource ini hanya mengatur override-nya.
 */
class KpiSettingResource extends Resource
{
    protected static ?string $model = KpiMetric::class;

    protected static ?string $navigationLabel = 'KPI Metric Settings';

    protected static ?string $modelLabel = 'KPI Metric';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = 'Performance';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('source')
                    ->label('Sumber')
                    ->formatStateUsing(fn (?string $state): string => KpiRegistry::label((string) $state))
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('key')
                    ->label('Metrik')
                    ->formatStateUsing(fn (?string $state, ?KpiMetric $record): string => $record?->label() ?? (string) $state)
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('target_value')
                    ->label('Target')
                    ->numeric()
                    ->required()
                    ->minValue(0),
                TextInput::make('weight')
                    ->label('Bobot')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%'),
                Toggle::make('is_active')
                    ->label('Aktif dalam penilaian')
                    ->default(true),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->columns([
                TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => KpiRegistry::label($state))
                    ->color(fn (string $state): string => KpiRegistry::isHousekeeping($state) ? 'success' : 'info'),
                TextColumn::make('key')
                    ->label('Metrik')
                    ->formatStateUsing(fn (string $state, KpiMetric $record): string => $record->label()),
                TextColumn::make('unit')
                    ->label('Satuan')
                    ->getStateUsing(fn (KpiMetric $record): string => KpiRegistry::DEFINITIONS[KpiRegistry::normalizeSource($record->source)][$record->key]['unit'] ?? '-'),
                TextColumn::make('direction')
                    ->label('Arah')
                    ->getStateUsing(fn (KpiMetric $record): string => KpiRegistry::DEFINITIONS[KpiRegistry::normalizeSource($record->source)][$record->key]['direction'] ?? 'higher')
                    ->formatStateUsing(fn (string $state): string => $state === 'lower' ? 'Makin kecil makin baik' : 'Makin besar makin baik'),
                TextColumn::make('target_value')
                    ->label('Target')
                    ->numeric(decimalPlaces: 0)
                    ->sortable(),
                TextColumn::make('weight')
                    ->label('Bobot')
                    ->numeric(decimalPlaces: 0)
                    ->suffix('%')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('source')
                    ->label('Sumber')
                    ->options([
                        KpiRegistry::SOURCE_MAINTENANCE => 'Maintenance',
                        KpiRegistry::SOURCE_HOUSEKEEPING_INDOOR => 'Housekeeping Indoor',
                        KpiRegistry::SOURCE_HOUSEKEEPING_OUTDOOR => 'Housekeeping Outdoor',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('source');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKpiSettings::route('/'),
        ];
    }
}
