<?php

namespace App\Filament\Resources\HousekeepingAreas\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class HousekeepingAreasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->paginated([25, 50, 100])
            ->columns([
                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width(1)
                    ->grow(false),
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company.name')
                    ->label('Company')
                    ->sortable(),
                TextColumn::make('supervisor.name')
                    ->label('Penanggung Jawab')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('area_size')
                    ->label('Luas (m²)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('facilities_count')
                    ->label('Fasilitas')
                    ->counts('facilities')
                    ->badge()
                    ->color('gray'),
                BooleanColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label('Company')
                    ->relationship('company', 'name'),
                TernaryFilter::make('is_active')
                    ->label('Status'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('name');
    }
}
