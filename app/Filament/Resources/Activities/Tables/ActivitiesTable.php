<?php

namespace App\Filament\Resources\Activities\Tables;

use App\Models\Activity;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->paginated([
                25,
                50,
                100,
            ])
            ->columns([
                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width(1)
                    ->grow(false),
                TextColumn::make('name')
                    ->description(fn(Activity $record): string => $record->reference ?? '-')
                    ->label('Name / Reference')
                    ->searchable(),
                TextColumn::make('equipmentType.name')
                    ->label('Equipment Type'),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
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
                    ->formatStateUsing(fn(string $state): string => Activity::TYPES[$state] ?? ucfirst(str_replace('_', ' ', $state))),
                TextColumn::make('maintenance_classification')
                    ->label('Classification')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'preventive' => 'success',
                        'corrective' => 'danger',
                        'predictive' => 'info',
                        'condition_based' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => Activity::CLASSIFICATIONS[$state] ?? ucfirst(str_replace('_', ' ', $state))),
                TextColumn::make('interval')
                    ->label('Interval')
                    ->formatStateUsing(fn(?string $state): string => $state ? (Activity::INTERVALS[$state] ?? ucfirst(str_replace('_', ' ', $state))) : '-'),
                IconColumn::make('status')
                    ->label('Active')
                    ->boolean(),
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
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
        // ->toolbarActions([
        //     BulkActionGroup::make([
        //         DeleteBulkAction::make(),
        //     ]),
        // ]);
    }
}
