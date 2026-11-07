<?php

namespace App\Filament\Resources\MaintenancePlans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MaintenancePlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('start_date', 'desc')
            ->columns([
                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width(1)
                    ->grow(false),
                TextColumn::make('plan_number')
                    ->label('Plan Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('equipment.equipmentType.name')
                    ->label('Equipment Type')
                    ->sortable(),
                TextColumn::make('equipment.tag_number')
                    ->label('Equipment')
                    ->sortable(),
                TextColumn::make('maintenance_classification')
                    ->label('Classification')
                    ->sortable(),
                TextColumn::make('interval')
                    ->label('Interval')
                    ->sortable(),
                TextColumn::make('technicianCoordinator.name')
                    ->label('Technician Coordinator'),
                TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('End Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Active' => 'success',
                        'Inactive' => 'gray',
                        'Suspended' => 'warning',
                        'Completed' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('equipment_id')
                    ->label('Equipment')
                    ->relationship('equipment', 'tag_number')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('maintenance_classification')
                    ->label('Classification')
                    ->options([
                        'preventive' => 'Preventive',
                        'corrective' => 'Corrective',
                        'predictive' => 'Predictive',
                        'condition_based' => 'Condition-Based',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
            // ->toolbarActions([
            //     BulkActionGroup::make([
            //         DeleteBulkAction::make(),
            //     ]),
            // ]);
    }
}
