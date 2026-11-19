<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersByDate;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingManpower;
use App\Models\HousekeepingPlan;
use App\Models\HousekeepingPlanTask;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HousekeepingStatsOverviewWidget extends BaseWidget
{
    use FiltersByDate;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $planCount = HousekeepingPlan::query()->tap(fn ($q) => $this->applyDateFilter($q, 'plan_date'))->count();
        $planDone = HousekeepingPlan::query()
            ->where('status', 'Completed')
            ->tap(fn ($q) => $this->applyDateFilter($q, 'plan_date'))
            ->count();
        $taskTotal = HousekeepingPlanTask::query()
            ->whereHas('plan', fn ($q) => $this->applyDateFilter($q, 'plan_date'))
            ->count();
        $taskDone = HousekeepingPlanTask::query()
            ->where('status', 'Done')
            ->whereHas('plan', fn ($q) => $this->applyDateFilter($q, 'plan_date'))
            ->count();
        $taskInProgress = HousekeepingPlanTask::query()
            ->where('status', 'In Progress')
            ->whereHas('plan', fn ($q) => $this->applyDateFilter($q, 'plan_date'))
            ->count();
        // Plan aktif = belum selesai dan batas SLA belum lewat. Tidak difilter
        // `plan_date` saja agar plan SLA panjang yang masih berjalan ikut terhitung.
        $openPlans = HousekeepingPlan::query()
            ->where('status', '!=', 'Completed')
            ->with('area:id,sla_days')
            ->get();

        $activePlans = $openPlans->filter(fn (HousekeepingPlan $plan): bool => ! $plan->is_overdue)->count();
        $overduePlans = $openPlans->filter(fn (HousekeepingPlan $plan): bool => $plan->is_overdue)->count();
        $pendingToday = HousekeepingPlanTask::query()
            ->whereIn('status', ['Pending', 'In Progress'])
            ->whereHas('plan', fn ($q) => $q->where('status', '!=', 'Completed'))
            ->count();

        $completionRate = $taskTotal > 0 ? round(($taskDone / $taskTotal) * 100) : 0;

        return [
            Stat::make('Area Housekeeping', HousekeepingArea::query()->where('is_active', true)->count())
                ->description('Area aktif terdaftar')
                ->descriptionIcon('heroicon-o-map-pin')
                ->color('primary'),
            Stat::make('Plan Harian', $planCount)
                ->description($planDone.' plan selesai')
                ->descriptionIcon('heroicon-o-clipboard-document-list')
                ->color('info'),
            Stat::make('Manpower', HousekeepingManpower::query()->where('is_active', true)->count())
                ->description('Petugas aktif')
                ->descriptionIcon('heroicon-o-users')
                ->color('info'),
            Stat::make('Progres Checklist', $completionRate.'%')
                ->description($taskDone.'/'.$taskTotal.' pekerjaan selesai')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color($completionRate >= 100 ? 'success' : 'warning'),
            Stat::make('Sedang Dikerjakan', $taskInProgress)
                ->description('Pekerjaan berstatus In Progress')
                ->descriptionIcon('heroicon-o-play')
                ->color($taskInProgress > 0 ? 'warning' : 'success'),
            Stat::make('Plan Aktif', $activePlans)
                ->description($pendingToday.' pekerjaan belum selesai')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color($activePlans > 0 ? 'warning' : 'success'),
            Stat::make('Plan Melewati SLA', $overduePlans)
                ->description($overduePlans > 0 ? 'Perlu eskalasi' : 'Semua sesuai SLA')
                ->descriptionIcon('heroicon-o-clock')
                ->color($overduePlans > 0 ? 'danger' : 'success'),
        ];
    }
}
