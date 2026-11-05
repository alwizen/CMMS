<?php

namespace App\Filament\Widgets;

use App\Models\MaintenanceSchedule;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class DueScheduleWidget extends BaseWidget
{
    protected static ?string $heading = 'Jadwal Jatuh Tempo & Terlambat';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $today = today();

        return $table
            ->query(
                MaintenanceSchedule::query()
                    ->with(['equipment.area', 'maintenancePlan'])
                    ->whereIn('status', [
                        MaintenanceSchedule::STATUSES['Scheduled'],
                        MaintenanceSchedule::STATUSES['Rescheduled'],
                        MaintenanceSchedule::STATUSES['Delayed'],
                    ])
                    ->whereDate('scheduled_date', '<=', $today->copy()->addDays(7))
                    ->orderBy('scheduled_date')
                    ->limit(15)
            )
            ->emptyStateHeading('Tidak ada jadwal jatuh tempo')
            ->emptyStateDescription('Semua jadwal 7 hari ke depan sudah tertangani.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->columns([
                Tables\Columns\TextColumn::make('schedule_number')
                    ->label('No. Jadwal')
                    ->searchable(),
                Tables\Columns\TextColumn::make('equipment.tag_number')
                    ->label('Equipment'),
                Tables\Columns\TextColumn::make('equipment.area.name')
                    ->label('Area')
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('maintenancePlan.plan_number')
                    ->label('Plan')
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('scheduled_date')
                    ->label('Jadwal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('overdue')
                    ->label('Keterlambatan')
                    ->badge()
                    ->state(function (MaintenanceSchedule $record): string {
                        $days = (int) ($record->scheduled_date?->startOfDay()->diffInDays(today()->startOfDay(), false) ?? 0);

                        return match (true) {
                            $days > 0 => $days.' hari terlambat',
                            $days === 0 => 'Hari ini',
                            default => abs($days).' hari lagi',
                        };
                    })
                    ->color(function (MaintenanceSchedule $record): string {
                        $days = (int) ($record->scheduled_date?->startOfDay()->diffInDays(today()->startOfDay(), false) ?? 0);

                        return match (true) {
                            $days > 0 => 'danger',
                            $days === 0 => 'warning',
                            default => 'success',
                        };
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        MaintenanceSchedule::STATUSES['Scheduled'] => 'warning',
                        MaintenanceSchedule::STATUSES['Rescheduled'] => 'primary',
                        MaintenanceSchedule::STATUSES['Delayed'] => 'danger',
                        default => 'gray',
                    }),
            ]);
    }
}
