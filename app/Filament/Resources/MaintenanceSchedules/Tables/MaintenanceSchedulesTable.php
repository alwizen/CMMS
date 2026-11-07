<?php

namespace App\Filament\Resources\MaintenanceSchedules\Tables;

use App\Models\MaintenanceSchedule;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MaintenanceSchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('scheduled_date', 'desc')
            ->paginated([25, 50, 100])
            ->columns([
                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width(1)
                    ->grow(false),
                TextColumn::make('schedule_number')
                    ->label('Schedule Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('equipment.equipmentType.name')
                    ->label('Equipment')
                    ->sortable(),
                TextColumn::make('maintenancePlan.plan_number')
                    ->label('Plan')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('scheduled_date')
                    ->label('Scheduled Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Scheduled' => 'warning',
                        'In Progress' => 'info',
                        'Completed' => 'success',
                        'Delayed' => 'danger',
                        'Rescheduled' => 'primary',
                        'Cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('rescheduled_from')
                    ->label('Rescheduled From')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('equipment_id')
                    ->label('Equipment')
                    ->relationship('equipment', 'tag_number'),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(MaintenanceSchedule::STATUSES),
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
