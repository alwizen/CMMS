<?php

namespace App\Filament\Resources\HousekeepingPlans\Tables;

use App\Filament\Resources\HousekeepingPlans\Actions\ApproveHousekeepingPlanAction;
use App\Models\HousekeepingPlan;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
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
                TextColumn::make('area.supervisor.name')
                    ->label('Penanggung Jawab')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('progress_percent')
                    ->label('Progress')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => $state.'%')
                    ->color(fn (int $state): string => match (true) {
                        $state >= 100 => 'success',
                        $state > 0 => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('area.sla_days')
                    ->label('SLA')
                    ->badge()
                    ->formatStateUsing(fn (?int $state): string => ($state ?? 1).' hari')
                    ->color('gray'),
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
                    ->formatStateUsing(fn (string $state, HousekeepingPlan $record): string => ! $record->hasStartedWork() && $state === HousekeepingPlan::APPROVAL_PENDING
                        ? 'Belum perlu review'
                        : (HousekeepingPlan::APPROVAL_STATUSES[$state] ?? $state))
                    ->color(fn (string $state, HousekeepingPlan $record): string => ! $record->hasStartedWork() && $state === HousekeepingPlan::APPROVAL_PENDING
                        ? 'gray'
                        : match ($state) {
                            'Approved' => 'success',
                            'Rejected' => 'danger',
                            default => 'warning',
                        })
                    ->sortable(),
                TextColumn::make('approvedBy.name')
                    ->label('Disetujui Oleh')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                SelectFilter::make('approval_status')
                    ->label('Review')
                    ->options(HousekeepingPlan::APPROVAL_STATUSES),
                Filter::make('awaiting_verification')
                    ->label('Perlu Review SPV GOS')
                    ->toggle()
                    ->query(fn ($query, array $data) => ($data['isActive'] ?? false)
                        ? $query->where('status', '!=', HousekeepingPlan::STATUSES['Draft'])
                            ->where('approval_status', HousekeepingPlan::APPROVAL_PENDING)
                        : $query),
            ])
            ->recordActions([
                ViewAction::make(),
                ApproveHousekeepingPlanAction::approve(),
                ApproveHousekeepingPlanAction::reject(),
                EditAction::make(),
            ])
            ->defaultSort('plan_date', 'desc');
    }
}
