<?php

namespace App\Filament\App\Resources\WorkOrders\Tables;

use App\Models\WorkOrder;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WorkOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100])
            ->columns([
                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width(1)
                    ->grow(false),
                TextColumn::make('work_order_number')
                    ->label('WO Number')
                    ->searchable(),
                TextColumn::make('equipment.tag_number')
                    ->label('Tag Number'),
                TextColumn::make('equipment.equipmentType.name')
                    ->label('Equipment'),
                TextColumn::make('classification')
                    ->label('Classification'),
                TextColumn::make('fungsi')
                    ->label('Fungsi')
                    ->badge()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('technicianCoordinator.name')
                    ->label('Performed by')
                    ->searchable()
                    ->placeholder('Belum diambil')
                    ->badge()
                    ->color(fn ($record): string => $record?->isUnclaimed() ? 'warning' : 'gray'),
                TextColumn::make('start_at')
                    ->label('Start At')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state, WorkOrder $record): string => match ($state) {
                        'Pending' => 'info',
                        'In Progress' => 'primary',
                        'Completed' => $record->isAwaitingReview() ? 'warning' : 'success',
                        'Cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('approval_status')
                    ->label('Review SPV MOS')
                    ->badge()
                    ->formatStateUsing(fn (string $state, WorkOrder $record): string => $record->status === WorkOrder::STATUSES['Completed']
                        || $state !== WorkOrder::APPROVAL_PENDING
                            ? (WorkOrder::APPROVAL_STATUSES[$state] ?? $state)
                            : '-')
                    ->color(fn (string $state, WorkOrder $record): string => $record->status !== WorkOrder::STATUSES['Completed']
                        && $state === WorkOrder::APPROVAL_PENDING
                            ? 'gray'
                            : match ($state) {
                                'Approved' => 'success',
                                'Rejected' => 'danger',
                                default => 'warning',
                            })
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(WorkOrder::STATUSES),
                SelectFilter::make('classification')
                    ->label('Classification')
                    ->options(WorkOrder::CLASSIFICATIONS),
                SelectFilter::make('approval_status')
                    ->label('Review')
                    ->options(WorkOrder::APPROVAL_STATUSES),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
