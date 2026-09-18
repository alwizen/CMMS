<?php

namespace App\Filament\Resources\MaintenanceHistories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MaintenanceHistoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('equipment.name')
                    ->label('Equipment')
                    ->sortable(),
                TextColumn::make('maintenance_type')
                    ->label('Type')
                    ->sortable(),
                TextColumn::make('classification')
                    ->label('Classification')
                    ->sortable(),
                TextColumn::make('maintenance_date')
                    ->label('Maintenance Date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('performedBy.name')
                    ->label('Performed By')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->sortable(),
                TextColumn::make('duration_minutes')
                    ->label('Duration (min)')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('equipment_id')
                    ->label('Equipment')
                    ->relationship('equipment', 'name'),
                SelectFilter::make('classification')
                    ->label('Classification')
                    ->options([
                        'preventive' => 'Preventive',
                        'corrective' => 'Corrective',
                    ]),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'completed' => 'Completed',
                        'in_progress' => 'In Progress',
                        'pending' => 'Pending',
                    ]),
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
