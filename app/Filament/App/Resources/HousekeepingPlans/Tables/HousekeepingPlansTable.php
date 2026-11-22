<?php

namespace App\Filament\App\Resources\HousekeepingPlans\Tables;

use App\Models\HousekeepingPlan;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HousekeepingPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('plan_date', 'desc')
            ->paginated([25, 50, 100])
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
                TextColumn::make('plan_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('area.name')
                    ->label('Area')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('progress_percent')
                    ->label('Progress')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => $state.'%')
                    ->color(fn (int $state): string => match (true) {
                        $state >= 100 => 'success',
                        $state > 0 => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('due_date')
                    ->label('Batas Waktu')
                    ->date('d M Y')
                    ->placeholder('-')
                    ->color(fn ($record): string => $record?->is_overdue ? 'danger' : 'gray'),
                TextColumn::make('sla_status')
                    ->label('Status SLA')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Completed' => 'success',
                        'On Track' => 'info',
                        'At Risk' => 'warning',
                        'Overdue' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('approval_status')
                    ->label('Review SPV GOS')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => HousekeepingPlan::APPROVAL_STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Completed' => 'success',
                        'In Progress' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(HousekeepingPlan::STATUSES),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
