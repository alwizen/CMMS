<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersByDate;
use App\Models\Equipment;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceSchedule;
use App\Models\WorkOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    use FiltersByDate;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $woCount = WorkOrder::query()->tap(fn ($q) => $this->applyDateFilter($q))->count();
        $woDone = WorkOrder::query()->where('status', 'Completed')->tap(fn ($q) => $this->applyDateFilter($q))->count();
        $mrPending = MaintenanceRequest::query()
            ->whereIn('status', ['Open', 'Assigned', 'In Progress'])
            ->tap(fn ($q) => $this->applyDateFilter($q))
            ->count();
        $overdueSchedules = MaintenanceSchedule::query()
            ->whereIn('status', [
                MaintenanceSchedule::STATUSES['Scheduled'],
                MaintenanceSchedule::STATUSES['Rescheduled'],
                MaintenanceSchedule::STATUSES['Delayed'],
            ])
            ->whereDate('scheduled_date', '<', today())
            ->count();

        $completionRate = $woCount > 0 ? round(($woDone / $woCount) * 100) : 0;

        return [
            Stat::make('Total Equipment', Equipment::count())
                ->description('Aset terdaftar')
                ->descriptionIcon('heroicon-o-cog-6-tooth')
                ->color('primary'),
            Stat::make('Work Orders', $woCount)
                ->description($completionRate.'% selesai')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('info'),
            Stat::make('WO Selesai', $woDone)
                ->description('Work order tuntas')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('Request Aktif', $mrPending)
                ->description('Belum selesai ditangani')
                ->descriptionIcon('heroicon-o-megaphone')
                ->color('warning'),
            Stat::make('Jadwal Terlambat', $overdueSchedules)
                ->description($overdueSchedules > 0 ? 'Perlu tindak lanjut' : 'Semua sesuai jadwal')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($overdueSchedules > 0 ? 'danger' : 'success'),
        ];
    }
}
