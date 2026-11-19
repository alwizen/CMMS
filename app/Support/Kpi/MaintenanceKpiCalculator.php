<?php

namespace App\Support\Kpi;

use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Hitung metrik KPI maintenance per user (Teknisi / Koordinator).
 *
 * Catatan definisi:
 * - Periode WO memakai `start_at`; bila kosong memakai `created_at`.
 * - WO berstatus Cancelled tidak dihitung pada Completion Rate & Preventive Ratio,
 *   tetapi WO yang belum selesai (overdue/expired) tetap diperhitungkan sebagai penalti.
 */
class MaintenanceKpiCalculator
{
    public function calculate(string $periodFrom, string $periodTo, ?string $fungsi = null, ?int $userId = null): Collection
    {
        $metrics = KpiRegistry::metrics(KpiRegistry::SOURCE_MAINTENANCE);

        if ($metrics === []) {
            return collect();
        }

        $drivers = WorkOrder::query()
            ->whereIn('status', $this->countedStatuses())
            ->whereBetween(DB::raw('COALESCE(start_at, created_at)'), [$periodFrom.' 00:00:00', $periodTo.' 23:59:59'])
            ->whereNotNull('technician_coordinator_id')
            ->with('workOrderActivities:id,work_order_id,executed')
            ->get();

        $workOrdersByUser = $drivers->groupBy('technician_coordinator_id');

        $now = now();
        $openWorkOrdersByUser = WorkOrder::query()
            ->whereIn('status', $this->openStatuses())
            ->whereNotNull('technician_coordinator_id')
            ->get()
            ->groupBy('technician_coordinator_id');

        $users = User::query()
            ->whereIn('id', $workOrdersByUser->keys()->merge($openWorkOrdersByUser->keys())->unique())
            ->when(filled($fungsi), fn ($query) => $query->where('fungsi', $fungsi))
            ->when($userId !== null, fn ($query) => $query->whereKey($userId))
            ->get()
            ->keyBy('id');

        $results = collect();

        foreach ($workOrdersByUser as $userId => $workOrders) {
            $user = $users->get($userId);

            if (! $user) {
                continue;
            }

            $openWorkOrders = $openWorkOrdersByUser->get($userId, collect());

            $metricValues = $this->metricValues($workOrders, $openWorkOrders, $metrics);

            $results->push($this->buildResult(
                user: $user,
                values: $metricValues,
                metrics: $metrics,
                workOrders: $workOrders,
                openWorkOrders: $openWorkOrders,
            ));
        }

        return $results->sortByDesc('score')->values();
    }

    /**
     * @param  Collection<int, WorkOrder>  $workOrders
     * @param  Collection<int, WorkOrder>  $openWorkOrders
     * @param  array<string, array<string, mixed>>  $metrics
     * @return array<string, float|int|null>
     */
    protected function metricValues(Collection $workOrders, Collection $openWorkOrders, array $metrics): array
    {
        $values = [];

        foreach (array_keys($metrics) as $key) {
            $values[$key] = match ($key) {
                'wo_completion_rate' => $this->ratio($workOrders->where('status', WorkOrder::STATUSES['Completed'])->count(), $workOrders->count()),
                'wo_on_time_rate' => $this->onTimeRate($workOrders),
                'checklist_compliance' => $this->checklistCompliance($workOrders),
                'preventive_ratio' => $this->ratio($workOrders->where('classification', 'Preventive')->count(), $workOrders->count()),
                'overdue_open_wo' => $this->overdueCount($openWorkOrders),
                'expired_wo' => $this->expiredCount($openWorkOrders),
                default => null,
            };
        }

        return $values;
    }

    /**
     * @param  Collection<int, WorkOrder>  $openWorkOrders
     */
    protected function overdueCount(Collection $openWorkOrders): int
    {
        return $openWorkOrders
            ->filter(fn (WorkOrder $workOrder): bool => $workOrder->start_at
                && $workOrder->start_at->lt(now()->startOfDay()))
            ->count();
    }

    /**
     * @param  Collection<int, WorkOrder>  $openWorkOrders
     */
    protected function expiredCount(Collection $openWorkOrders): int
    {
        return $openWorkOrders
            ->filter(fn (WorkOrder $workOrder): bool => $workOrder->isExpired())
            ->count();
    }

    /**
     * @param  Collection<int, WorkOrder>  $workOrders
     */
    protected function onTimeRate(Collection $workOrders): ?float
    {
        $completed = $workOrders->where('status', WorkOrder::STATUSES['Completed']);

        if ($completed->isEmpty()) {
            return null;
        }

        $onTime = $completed
            ->filter(function (WorkOrder $workOrder): bool {
                $finish = $workOrder->finish_at;
                $start = $workOrder->start_at ?? $workOrder->created_at;

                if (! $finish || ! $start) {
                    return false;
                }

                return $finish->lte($start->copy()->endOfDay());
            })
            ->count();

        return $this->ratio($onTime, $completed->count());
    }

    /**
     * @param  Collection<int, WorkOrder>  $workOrders
     */
    protected function checklistCompliance(Collection $workOrders): ?float
    {
        $total = 0;
        $done = 0;

        foreach ($workOrders as $workOrder) {
            $activities = $workOrder->workOrderActivities;
            $total += $activities->count();
            $done += $activities->where('executed', true)->count();
        }

        if ($total === 0) {
            return null;
        }

        return $this->ratio($done, $total);
    }

    protected function ratio(int $value, int $total): ?float
    {
        if ($total === 0) {
            return null;
        }

        return round(($value / $total) * 100, 2);
    }

    /**
     * @param  array<string, float|int|null>  $values
     * @param  array<string, array<string, mixed>>  $metrics
     * @param  Collection<int, WorkOrder>  $workOrders
     * @param  Collection<int, WorkOrder>  $openWorkOrders
     */
    protected function buildResult(User $user, array $values, array $metrics, Collection $workOrders, Collection $openWorkOrders): KpiResult
    {
        $result = KpiScorer::build(
            source: KpiRegistry::SOURCE_MAINTENANCE,
            objectType: KpiRegistry::OBJECT_USER,
            objectId: (string) $user->id,
            objectName: $user->name,
            values: $values,
            metrics: $metrics,
        );

        $completed = $workOrders->where('status', WorkOrder::STATUSES['Completed'])->count();
        $open = $workOrders->whereIn('status', $this->openStatuses())->count();

        $result->context = [
            'fungsi' => $user->fungsi,
            'total_wo' => $workOrders->count(),
            'completed_wo' => $completed,
            'open_wo' => $open,
            'overdue_wo' => $this->overdueCount($openWorkOrders),
            'expired_wo' => $this->expiredCount($openWorkOrders),
        ];

        return $result;
    }

    /**
     * @return array<int, string>
     */
    protected function countedStatuses(): array
    {
        return [
            WorkOrder::STATUSES['Pending'],
            WorkOrder::STATUSES['In Progress'],
            WorkOrder::STATUSES['On Hold'],
            WorkOrder::STATUSES['Completed'],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function openStatuses(): array
    {
        return [
            WorkOrder::STATUSES['Pending'],
            WorkOrder::STATUSES['In Progress'],
            WorkOrder::STATUSES['On Hold'],
        ];
    }

    /**
     * Metrik downtime (opsional, dipakai halaman detail bila diperlukan).
     */
    public function averageDowntimeMinutes(string $periodFrom, string $periodTo): ?float
    {
        $requests = MaintenanceRequest::query()
            ->whereNotNull('approved_at')
            ->whereBetween('approved_at', [$periodFrom.' 00:00:00', $periodTo.' 23:59:59'])
            ->with('workOrders')
            ->get();

        $values = $requests
            ->map(fn (MaintenanceRequest $request): ?int => $request->downtimeMinutes())
            ->filter(fn (?int $minutes): bool => $minutes !== null);

        if ($values->isEmpty()) {
            return null;
        }

        return round($values->avg(), 0);
    }
}
