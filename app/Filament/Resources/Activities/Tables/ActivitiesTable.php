<?php

namespace App\Filament\Resources\Activities\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('equipmentType.name')
                    ->label('Equipment Type')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->sortable(),
                TextColumn::make('maintenance_classification')
                    ->label('Classification')
                    ->sortable(),
                TextColumn::make('interval')
                    ->label('Interval')
                    ->sortable(),
                BooleanColumn::make('status')
                    ->label('Active')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('equipment_type_id')
                    ->label('Equipment Type')
                    ->relationship('equipmentType', 'name'),
                SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'Maintenance' => 'Maintenance',
                        'Inspection' => 'Inspection',
                        'Replacement' => 'Replacement',
                        'Cleaning' => 'Cleaning',
                        'Testing' => 'Testing',
                    ]),
                SelectFilter::make('maintenance_classification')
                    ->label('Classification')
                    ->options([
                        'Preventive' => 'Preventive',
                        'Corrective' => 'Corrective',
                    ]),
                SelectFilter::make('interval')
                    ->label('Interval')
                    ->options([
                        'Daily' => 'Daily',
                        'Weekly' => 'Weekly',
                        'Monthly' => 'Monthly',
                        'Quarterly' => 'Quarterly',
                        'Semi-annually' => 'Semi-annually',
                        'Yearly' => 'Yearly',
                        'As needed' => 'As needed',
                        'Every 250 hours' => 'Every 250 hours',
                        'Every 500 hours' => 'Every 500 hours',
                    ]),
                TernaryFilter::make('status')
                    ->label('Status'),
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
