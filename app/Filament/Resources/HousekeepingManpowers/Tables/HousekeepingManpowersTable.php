<?php

namespace App\Filament\Resources\HousekeepingManpowers\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class HousekeepingManpowersTable
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
                TextColumn::make('employee_number')
                    ->label('NIP')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('area.name')
                    ->label('Area Housekeeping')
                    ->placeholder('-'),
                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('housekeeping_area_id')
                    ->label('Area')
                    ->relationship('area', 'name'),
                TernaryFilter::make('is_active')
                    ->label('Status'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            // ->toolbarActions([
            //     BulkActionGroup::make([
            //         DeleteBulkAction::make(),
            //     ]),
            // ])
            ->defaultSort('name');
    }
}
