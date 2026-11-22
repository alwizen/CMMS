<?php

namespace App\Filament\App\Resources\Equipment\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EquipmentTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('tag_number')
            ->paginated([25, 50, 100])
            ->columns([
                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width(1)
                    ->grow(false),
                TextColumn::make('tag_number')
                    ->label('Tag Number')
                    ->searchable(),
                TextColumn::make('pm_tag')
                    ->label('PM Tag')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(fn ($state): string => $state ?? '-'),
                TextColumn::make('area.name')
                    ->label('Area'),
                TextColumn::make('equipmentType.name')
                    ->label('Equipment Type')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'retired' => 'danger',
                    }),
                TextColumn::make('criticality')
                    ->label('Criticality')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'gray',
                        'medium' => 'warning',
                        'high', 'critical' => 'danger',
                    }),
            ])
            ->filters([
                SelectFilter::make('area_id')
                    ->label('Area')
                    ->relationship('area', 'name'),
                SelectFilter::make('equipment_type_id')
                    ->label('Equipment Type')
                    ->relationship('equipmentType', 'name'),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'retired' => 'Retired',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
