<?php

namespace App\Filament\Resources\MeterLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MeterLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('equipment.equipmentType.name')
                    ->label('Equipment')
                    ->sortable(),
                TextColumn::make('reading_date')
                    ->label('Reading Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('value')
                    ->label('Value')
                    ->sortable(),
                TextColumn::make('recordedBy.name')
                    ->label('Recorded By')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('equipment_id')
                    ->label('Equipment')
                    ->relationship('equipment', 'tag_number'),
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
