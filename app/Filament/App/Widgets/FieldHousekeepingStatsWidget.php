<?php

namespace App\Filament\App\Widgets;

use App\Models\HousekeepingPlan;
use App\Models\HousekeepingPlanTask;
use App\Support\HousekeepingVisibility;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FieldHousekeepingStatsWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:HousekeepingPlan');
    }

    protected static ?int $sort = 1;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        $plans = HousekeepingVisibility::scopePlans(
            HousekeepingPlan::query()->with('area:id,sla_days'),
            $user,
        )->get();

        $openPlans = $plans->where('status', '!=', HousekeepingPlan::STATUSES['Completed']);
        $activePlans = $openPlans->reject(fn (HousekeepingPlan $plan): bool => $plan->is_overdue);
        $overduePlans = $openPlans->filter(fn (HousekeepingPlan $plan): bool => $plan->is_overdue);
        $todayPlans = $openPlans->filter(
            fn (HousekeepingPlan $plan): bool => $plan->plan_date?->toDateString() === today()->toDateString(),
        );

        $tasks = HousekeepingVisibility::scopeTasks(HousekeepingPlanTask::query(), $user);
        $taskTotal = (clone $tasks)->count();
        $taskDone = (clone $tasks)->where('status', 'Done')->count();
        $taskOpen = (clone $tasks)->whereIn('status', ['Pending', 'In Progress'])->count();
        $rate = $taskTotal > 0 ? (int) round(($taskDone / $taskTotal) * 100) : 0;

        return [
            Stat::make('Plan Hari Ini', $todayPlans->count())
                ->description('Plan berjalan hari ini')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('primary'),
            Stat::make('Plan Aktif', $activePlans->count())
                ->description($taskOpen.' pekerjaan belum selesai')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color($activePlans->count() > 0 ? 'warning' : 'success'),
            Stat::make('Progres Checklist', $rate.'%')
                ->description($taskDone.'/'.$taskTotal.' pekerjaan selesai')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color($rate >= 100 ? 'success' : 'warning'),
            Stat::make('Plan Lewat SLA', $overduePlans->count())
                ->description($overduePlans->count() > 0 ? 'Perlu eskalasi' : 'Semua sesuai SLA')
                ->descriptionIcon('heroicon-o-clock')
                ->color($overduePlans->count() > 0 ? 'danger' : 'success'),
        ];
    }
}
