<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersByDate;
use App\Models\HousekeepingPlan;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentHousekeepingPlansWidget extends BaseWidget
{
    use FiltersByDate;

    protected static ?string $heading = 'Plan Harian Terbaru';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                HousekeepingPlan::query()
                    ->tap(fn ($q) => $this->applyDateFilter($q, 'plan_date'))
                    ->latest('plan_date')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('plan_number')
                    ->label('Plan Number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('plan_date')
                    ->label('Tanggal')
                    ->date('d M Y'),
                Tables\Columns\TextColumn::make('area.name')
                    ->label('Area'),
                Tables\Columns\TextColumn::make('area.supervisor.name')
                    ->label('Penanggung Jawab')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('manpowers_count')
                    ->label('Manpower')
                    ->counts('manpowers')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Completed' => 'success',
                        'In Progress' => 'warning',
                        default => 'gray',
                    }),
            ]);
    }
}
