<?php

namespace App\Filament\App\Widgets;

use App\Models\MaintenanceRequest;
use App\Models\WorkOrder;
use App\Support\UserVisibility;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FieldStatsOverviewWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:WorkOrder');
    }

    protected static ?int $sort = 1;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        $workOrders = fn () => UserVisibility::scopeWorkOrders(WorkOrder::query(), $user);

        $today = $workOrders()->whereDate('start_at', today())->count();
        $inProgress = $workOrders()->where('status', WorkOrder::STATUSES['In Progress'])->count();
        $completed = $workOrders()->where('status', WorkOrder::STATUSES['Completed'])->count();
        $overdue = $workOrders()
            ->whereIn('status', [
                WorkOrder::STATUSES['Pending'],
                WorkOrder::STATUSES['In Progress'],
            ])
            ->whereDate('start_at', '<', today())
            ->count();

        $openRequests = UserVisibility::scopeRequests(MaintenanceRequest::query(), $user)
            ->whereIn('status', [
                MaintenanceRequest::STATUSES['Open'],
                MaintenanceRequest::STATUSES['Assigned'],
                MaintenanceRequest::STATUSES['In Progress'],
            ])
            ->count();

        return [
            Stat::make('WO Hari Ini', $today)
                ->description('Dijadwalkan hari ini')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('primary'),
            Stat::make('Sedang Berjalan', $inProgress)
                ->description('Status In Progress')
                ->descriptionIcon('heroicon-o-play')
                ->color($inProgress > 0 ? 'warning' : 'success'),
            Stat::make('WO Terlambat', $overdue)
                ->description($overdue > 0 ? 'Perlu tindak lanjut' : 'Semua sesuai jadwal')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($overdue > 0 ? 'danger' : 'success'),
            Stat::make('WO Selesai', $completed)
                ->description('Total work order selesai')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('Request Aktif', $openRequests)
                ->description('Belum selesai ditangani')
                ->descriptionIcon('heroicon-o-megaphone')
                ->color($openRequests > 0 ? 'warning' : 'success'),
        ];
    }
}
