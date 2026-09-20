<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersByDate;
use App\Models\WorkOrder;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentWorkOrdersWidget extends BaseWidget
{
    use FiltersByDate;

    protected static ?string $heading = 'Work Order Terbaru';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                WorkOrder::query()
                    ->tap(fn ($q) => $this->applyDateFilter($q))
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('work_order_number')
                    ->label('WO Number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('equipment.name')
                    ->label('Equipment'),
                Tables\Columns\TextColumn::make('classification')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Preventive' => 'success',
                        'Corrective' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Completed' => 'success',
                        'In Progress' => 'warning',
                        'Pending' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('start_at')
                    ->label('Start Date')
                    ->dateTime('d M Y H:i'),
            ]);
    }
}
