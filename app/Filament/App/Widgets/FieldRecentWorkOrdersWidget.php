<?php

namespace App\Filament\App\Widgets;

use App\Models\WorkOrder;
use App\Support\UserVisibility;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class FieldRecentWorkOrdersWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected static ?string $heading = 'Work Order Terbaru';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:WorkOrder');
    }

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                UserVisibility::scopeWorkOrders(WorkOrder::query(), auth()->user())
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('work_order_number')
                    ->label('WO Number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('equipment.tag_number')
                    ->label('Tag Number'),
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
