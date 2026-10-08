<?php

namespace App\Filament\Resources\MeterLogs\Tables;

use App\Models\Area;
use App\Models\EquipmentType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MeterLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('equipment.tag_number')
                    ->label('Tag Number')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('equipment.description')
                    ->label('Description')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->equipment?->description)
                    ->searchable(),
                TextColumn::make('equipment.equipmentType.name')
                    ->label('Type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('equipment.area.name')
                    ->label('Area')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('value')
                    ->label('Value')
                    ->numeric(decimalPlaces: 3)
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('reading_date')
                    ->label('Reading Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('recordedBy.name')
                    ->label('Recorded By')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('equipment_id')
                    ->label('Tag Number')
                    ->relationship('equipment', 'tag_number')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('equipment_type')
                    ->label('Equipment Type')
                    ->options(fn () => EquipmentType::pluck('name', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'] ?? null)) {
                            return $query;
                        }

                        return $query->whereHas('equipment', fn (Builder $q) => $q->where('equipment_type_id', $data['value']));
                    }),
                SelectFilter::make('area_id')
                    ->label('Area')
                    ->options(fn () => Area::pluck('name', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'] ?? null)) {
                            return $query;
                        }

                        return $query->whereHas('equipment', fn (Builder $q) => $q->where('area_id', $data['value']));
                    }),
                Filter::make('reading_date')
                    ->label('Reading Date')
                    ->schema([
                        DatePicker::make('from')
                            ->label('From'),
                        DatePicker::make('until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $q, $date) => $q->whereDate('reading_date', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $q, $date) => $q->whereDate('reading_date', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
