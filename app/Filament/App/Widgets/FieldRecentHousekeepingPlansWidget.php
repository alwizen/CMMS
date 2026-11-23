<?php

namespace App\Filament\App\Widgets;

use App\Filament\App\Resources\HousekeepingPlans\HousekeepingPlanResource;
use App\Models\HousekeepingPlanTask;
use App\Support\HousekeepingVisibility;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class FieldRecentHousekeepingPlansWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected static ?string $heading = 'Pekerjaan Housekeeping Terbaru';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:HousekeepingPlan');
    }

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                HousekeepingVisibility::scopeTasks(
                    HousekeepingPlanTask::query()->with(['plan.area', 'facility']),
                    auth()->user(),
                )
                    ->latest('id')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('plan.plan_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('plan.area.name')
                    ->label('Area')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('facility.name')
                    ->label('Fasilitas')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Pekerjaan')
                    ->limit(40),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Done' => 'success',
                        'In Progress' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->recordActions([
                Action::make('view_plan')
                    ->label('Lihat Plan')
                    ->icon('heroicon-m-eye')
                    ->visible(fn (HousekeepingPlanTask $record): bool => $record->plan_id !== null)
                    ->url(fn (HousekeepingPlanTask $record): ?string => $record->plan_id !== null
                        ? HousekeepingPlanResource::getUrl('view', ['record' => $record->plan_id])
                        : null),
            ]);
    }
}
