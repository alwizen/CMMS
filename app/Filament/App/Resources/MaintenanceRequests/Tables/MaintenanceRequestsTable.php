<?php

namespace App\Filament\App\Resources\MaintenanceRequests\Tables;

use App\Filament\App\Resources\MaintenanceRequests\MaintenanceRequestResource;
use App\Models\MaintenanceRequest;
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
            ->defaultSort('damage_date', 'desc')
            ->paginated([25, 50, 100])
            ->columns([
                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width(1)
                    ->grow(false),
                TextColumn::make('request_number')
                    ->label('Request Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('equipment.tag_number')
                    ->label('Tag Number')
                    ->sortable(),
                TextColumn::make('equipment.equipmentType.name')
                    ->label('Equipment Type')
                    ->toggleable(),
                TextColumn::make('reportedBy.name')
                    ->label('Reported By')
                    ->toggleable(),
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
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Open' => 'info',
                        'Assigned' => 'primary',
                        'In Progress' => 'warning',
                        'Completed', 'Approved' => 'success',
                        'Rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(MaintenanceRequest::STATUSES),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (MaintenanceRequest $record): bool => MaintenanceRequestResource::canEdit($record)),
            ]);
    }
}
