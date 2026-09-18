<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersByDate;
use App\Models\Equipment;
use App\Models\MaintenanceHistory;
use App\Models\MaintenanceRequest;
use App\Models\WorkOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    use FiltersByDate;

    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $woCount = WorkOrder::query()->tap(fn ($q) => $this->applyDateFilter($q))->count();
        $woDone  = WorkOrder::query()->where('status', 'Completed')->tap(fn ($q) => $this->applyDateFilter($q))->count();
        $mrCount = MaintenanceRequest::query()->tap(fn ($q) => $this->applyDateFilter($q))->count();
        $mhCount = MaintenanceHistory::query()->tap(fn ($q) => $this->applyDateFilter($q))->count();

        return [
            Stat::make('Total Equipment', Equipment::count())
                ->description('Jumlah equipment aktif')
                ->descriptionIcon('heroicon-o-cog-6-tooth')
                ->color('primary'),
            Stat::make('Work Orders', $woCount)
                ->description('Total work order')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('info'),
            Stat::make('WO Selesai', $woDone)
                ->description('Work order selesai')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('Request Masuk', $mrCount)
                ->description('Maintenance request')
                ->descriptionIcon('heroicon-o-megaphone')
                ->color('warning'),
            Stat::make('Riwayat', $mhCount)
                ->description('Total history maintenance')
                ->descriptionIcon('heroicon-o-clock')
                ->color('danger'),
        ];
    }
}
