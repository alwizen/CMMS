<?php

namespace App\Filament\Resources\Activities\Tables;

use App\Models\Activity;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
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
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'maintenance' => 'primary',
                        'inspection' => 'info',
                        'testing' => 'warning',
                        'visual_check' => 'gray',
                        'measurement' => 'success',
                        'lubrication' => 'warning',
                        'cleaning' => 'info',
                        'calibration' => 'danger',
                        'replacement' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Activity::TYPES[$state] ?? ucfirst(str_replace('_', ' ', $state)))
                    ->sortable(),
                TextColumn::make('maintenance_classification')
                    ->label('Classification')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'preventive' => 'success',
                        'corrective' => 'danger',
                        'predictive' => 'info',
                        'condition_based' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Activity::CLASSIFICATIONS[$state] ?? ucfirst(str_replace('_', ' ', $state)))
                    ->sortable(),
                TextColumn::make('interval')
                    ->label('Interval')
                    ->formatStateUsing(fn (?string $state): string => $state ? (Activity::INTERVALS[$state] ?? ucfirst(str_replace('_', ' ', $state))) : '-')
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
                    ->options(Activity::TYPES),
                SelectFilter::make('maintenance_classification')
                    ->label('Classification')
                    ->options(Activity::CLASSIFICATIONS),
                SelectFilter::make('interval')
                    ->label('Interval')
                    ->options(Activity::INTERVALS),
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
