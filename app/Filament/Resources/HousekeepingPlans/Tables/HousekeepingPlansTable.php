<?php

namespace App\Filament\Resources\HousekeepingPlans\Tables;

use App\Models\HousekeepingPlan;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HousekeepingPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                TextColumn::make('area.company.name')
                    ->label('Company')
                    ->toggleable(),
                TextColumn::make('area.supervisor.name')
                    ->label('Penanggung Jawab')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('manpowers_count')
                    ->label('Manpower')
                    ->counts('manpowers')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Completed' => 'success',
                        'In Progress' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('housekeeping_area_id')
                    ->label('Area')
                    ->relationship('area', 'name'),
                SelectFilter::make('status')
                    ->options(HousekeepingPlan::STATUSES),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('plan_date', 'desc');
    }
}
