<?php

namespace App\Filament\Resources\MaintenanceRequests\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MaintenanceRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('request_number')
                    ->label('Request Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('equipment.equipmentType.name')
                    ->label('Equipment Type')
                    ->sortable(),
                TextColumn::make('equipment.tag_number')
                    ->label('Tag Number')
                    ->sortable(),
                TextColumn::make('reportedBy.name')
                    ->label('Reported By')
                    ->sortable(),
                TextColumn::make('damage_date')
                    ->label('Damage Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('operation_status')
                    ->label('Operation Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Running' => 'success',
                        'Stopped' => 'danger',
                        'Standby' => 'warning',
                        'Faulty' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Open' => 'info',
                        'Assigned' => 'primary',
                        'In Progress' => 'warning',
                        'Completed' => 'success',
                        'Rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('equipment_id')
                    ->label('Equipment')
                    ->relationship('equipment', 'tag_number'),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'completed' => 'Completed',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
