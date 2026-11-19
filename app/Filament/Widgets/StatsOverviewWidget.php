<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersByDate;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\MaintenanceRequest;
use App\Models\WorkOrder;
use App\Support\Downtime;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    use FiltersByDate;

    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $woCount = WorkOrder::query()
            ->tap(fn ($q) => $this->applyDateFilter($q))
            ->tap(fn ($q) => $this->applyUserFilter($q))
            ->tap(fn ($q) => $this->applyFungsiFilter($q))
            ->count();
        $woDone = WorkOrder::query()
            ->where('status', 'Completed')
            ->tap(fn ($q) => $this->applyDateFilter($q))
            ->tap(fn ($q) => $this->applyUserFilter($q))
            ->tap(fn ($q) => $this->applyFungsiFilter($q))
            ->count();
        $mrPending = MaintenanceRequest::query()
            ->whereIn('status', ['Open', 'Assigned', 'In Progress'])
            ->tap(fn ($q) => $this->applyDateFilter($q))
            ->count();
        $woOpen = WorkOrder::query()
            ->whereIn('status', [
                WorkOrder::STATUSES['Pending'],
                WorkOrder::STATUSES['In Progress'],
                WorkOrder::STATUSES['On Hold'],
            ])
            ->tap(fn ($q) => $this->applyDateFilter($q))
            ->tap(fn ($q) => $this->applyUserFilter($q))
            ->tap(fn ($q) => $this->applyFungsiFilter($q))
            ->count();

        $overdueWorkOrders = WorkOrder::query()
            ->whereIn('status', [
                WorkOrder::STATUSES['Pending'],
                WorkOrder::STATUSES['In Progress'],
                WorkOrder::STATUSES['On Hold'],
            ])
            ->whereDate('start_at', '<', today())
            ->tap(fn ($q) => $this->applyUserFilter($q))
            ->tap(fn ($q) => $this->applyFungsiFilter($q))
            ->count();

        $completionRate = $woCount > 0 ? round(($woDone / $woCount) * 100) : 0;

        $downtimeRequests = MaintenanceRequest::query()
            ->where('operation_status', 'Faulty')
            ->where('status', '!=', MaintenanceRequest::STATUSES['Rejected'])
            ->tap(fn ($q) => $this->applyDateFilter($q, 'damage_date'))
            ->with('workOrders')
            ->get();

        $downtimeMinutes = Downtime::totalMinutes($downtimeRequests);
        $downtimeEvents = Downtime::eventCount($downtimeRequests);

        $fungsi = $this->fungsiFilter();

        $equipmentTypeCount = EquipmentType::query()
            ->when(filled($fungsi), fn ($query) => $query->where('fungsi', $fungsi))
            ->count();

        return [
            Stat::make('Total Equipment', Equipment::count())
                ->description('Aset terdaftar')
                ->descriptionIcon('heroicon-o-cog-6-tooth')
                ->color('primary'),
            Stat::make('Total Equipment Type', $equipmentTypeCount)
                ->description($fungsi ?? 'Semua fungsi')
                ->descriptionIcon('heroicon-o-squares-2x2')
                ->color('gray'),
            Stat::make('Work Orders', $woCount)
                ->description($completionRate.'% selesai')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('info'),
            Stat::make('WO Selesai', $woDone)
                ->description('Work order tuntas')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('WO Belum Selesai', $woOpen)
                ->description('Pending / In Progress / On Hold')
                ->descriptionIcon('heroicon-o-clock')
                ->color($woOpen > 0 ? 'warning' : 'success'),
            Stat::make('Request Aktif', $mrPending)
                ->description('Belum selesai ditangani')
                ->descriptionIcon('heroicon-o-megaphone')
                ->color('warning'),
            Stat::make('WO Terlambat', $overdueWorkOrders)
                ->description($overdueWorkOrders > 0 ? 'Perlu tindak lanjut' : 'Semua sesuai jadwal')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($overdueWorkOrders > 0 ? 'danger' : 'success'),
            Stat::make('Unplanned Downtime', Downtime::formatHours($downtimeMinutes))
                ->description($downtimeEvents.' kejadian')
                ->descriptionIcon('heroicon-o-clock')
                ->color($downtimeMinutes > 0 ? 'danger' : 'success'),
        ];
    }
}
