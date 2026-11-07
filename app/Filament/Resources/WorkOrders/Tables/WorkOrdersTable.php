<?php

namespace App\Filament\Resources\WorkOrders\Tables;

use App\Models\WorkOrder;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WorkOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('start_at', 'desc')
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
                TextColumn::make('technicianCoordinator.name')
                    ->label('Performed by'),
                TextColumn::make('start_at')
                    ->label('Start At')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Open' => 'info',
                        'In Progress' => 'primary',
                        'Completed' => 'success',
                        'Cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('equipment_id')
                    ->label('Equipment')
                    ->relationship('equipment', 'tag_number')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('classification')
                    ->label('Classification')
                    ->options(WorkOrder::CLASSIFICATIONS),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(WorkOrder::STATUSES),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
            ]);
        // ->toolbarActions([
        //     BulkActionGroup::make([
        //         DeleteBulkAction::make(),
        //     ]),
        // ]);
    }
}
