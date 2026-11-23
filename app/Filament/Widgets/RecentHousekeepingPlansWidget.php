<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\HousekeepingPlans\HousekeepingPlanResource;
use App\Filament\Widgets\Concerns\FiltersByDate;
use App\Models\HousekeepingPlanTask;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentHousekeepingPlansWidget extends BaseWidget
{
    use FiltersByDate;

    protected static ?string $heading = 'Report Harian';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    /**
     * Widget ini memakai filter tanggal dashboard (opsional) jika tersedia.
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                HousekeepingPlanTask::query()
                    ->with(['plan.area', 'facility'])
                    ->when(
                        $this->dateFilterRange() === 'all',
                        // Default: tampilkan pekerjaan yang masih berjalan + yang
                        // baru dibuat, bukan hanya plan bertanggal hari ini.
                        fn (Builder $query) => $query->whereHas(
                            'plan',
                            fn (Builder $plan) => $plan
                                ->where('status', '!=', 'Completed')
                                ->orWhereDate('plan_date', '>=', now()->subDays(7)),
                        ),
                        fn (Builder $query) => $query->whereHas(
                            'plan',
                            fn (Builder $plan) => $this->applyDateFilter($plan, 'plan_date'),
                        ),
                    )
                    ->latest('id')
                    ->limit(15)
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
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn (?string $state): ?string => $state),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Done' => 'success',
                        'In Progress' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('photo_before')
                    ->label('Before')
                    ->badge()
                    ->getStateUsing(fn (HousekeepingPlanTask $record): string => count($record->photo_before ?? []).' foto')
                    ->color(fn (HousekeepingPlanTask $record): string => count($record->photo_before ?? []) > 0 ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('photo_after')
                    ->label('After')
                    ->badge()
                    ->getStateUsing(fn (HousekeepingPlanTask $record): string => count($record->photo_after ?? []).' foto')
                    ->color(fn (HousekeepingPlanTask $record): string => count($record->photo_after ?? []) > 0 ? 'success' : 'gray'),
            ])
            ->recordActions([
                Action::make('view_plan')
                    ->label('Lihat Plan')
                    ->icon('heroicon-m-eye')
                    ->visible(fn (HousekeepingPlanTask $record): bool => $record->plan_id !== null)
                    ->url(fn (HousekeepingPlanTask $record): ?string => $record->plan_id !== null
                        ? HousekeepingPlanResource::getUrl('view', ['record' => $record->plan_id])
                        : null),
            ])
            ->emptyStateHeading('Belum ada pekerjaan tercatat')
            ->emptyStateDescription('Pekerjaan harian muncul dari checklist Plan Harian housekeeping.');
    }
}
