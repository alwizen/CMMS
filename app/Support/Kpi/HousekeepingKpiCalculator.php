<?php

namespace App\Support\Kpi;

use App\Models\HousekeepingArea;
use App\Models\HousekeepingPlan;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Hitung metrik KPI housekeeping per Area.
 *
 * Catatan definisi:
 * - Periode memakai `housekeeping_plans.plan_date`.
 * - Set metrik ditentukan oleh klasifikasi area (`kpi_class`):
 *   - Indoor  (plan harian, SLA pendek): kedisiplinan harian.
 *   - Outdoor (plan per window, SLA panjang): efisiensi terhadap window SLA.
 * - SLA memakai `housekeeping_areas.sla_days` sehingga setiap area dinilai
 *   terhadap targetnya sendiri.
 */
class HousekeepingKpiCalculator
{
    public function calculate(string $periodFrom, string $periodTo, string $source = KpiRegistry::SOURCE_HOUSEKEEPING_OUTDOOR): Collection
    {
        $kpiClass = $source === KpiRegistry::SOURCE_HOUSEKEEPING_INDOOR ? 'indoor' : 'outdoor';

        $metrics = KpiRegistry::metrics($source);

        if ($metrics === []) {
            return collect();
        }

        $plans = HousekeepingPlan::query()
            ->whereBetween('plan_date', [$periodFrom, $periodTo])
            ->whereHas('area', fn ($query) => $query->where('kpi_class', $kpiClass))
            ->with(['area:id,name,sla_days,kpi_class', 'tasks:id,housekeeping_plan_id,status,completed_at,photo_after'])
            ->get();

        if ($plans->isEmpty()) {
            return collect();
        }

        $plansByArea = $plans->groupBy('housekeeping_area_id');

        $areaIds = $plansByArea->keys()->filter()->all();

        $areas = HousekeepingArea::query()
            ->whereIn('id', $areaIds)
            ->get()
            ->keyBy('id');

        $periodStart = Carbon::parse($periodFrom)->startOfDay();
        $periodEnd = Carbon::parse($periodTo)->startOfDay();
        $periodDays = max(1, $periodStart->diffInDays($periodEnd) + 1);

        $results = collect();

        foreach ($plansByArea as $areaId => $areaPlans) {
            $area = $areas->get($areaId);

            if (! $area) {
                continue;
            }

            $values = $this->metricValues($areaPlans, $periodDays, $periodStart, $periodEnd, $metrics);

            $results->push(KpiScorer::build(
                source: $source,
                objectType: KpiRegistry::OBJECT_AREA,
                objectId: (string) $areaId,
                objectName: $area->name,
                values: $values,
                metrics: $metrics,
            ));
        }

        return $results->sortByDesc('score')->values();
    }

    /**
     * @param  Collection<int, HousekeepingPlan>  $plans
     * @param  array<string, array<string, mixed>>  $metrics
     * @return array<string, float|int|null>
     */
    protected function metricValues(
        Collection $plans,
        int $periodDays,
        Carbon $periodStart,
        Carbon $periodEnd,
        array $metrics,
    ): array {
        $tasks = $plans->flatMap(fn (HousekeepingPlan $plan): Collection => $plan->tasks);

        $done = $tasks->where('status', 'Done');
        $backlog = $tasks->whereIn('status', ['Pending', 'In Progress']);

        $completedPlans = $plans->where('status', 'Completed');
        $withinSla = $completedPlans->filter(fn (HousekeepingPlan $plan): bool => $plan->completed_within_sla === true);

        // Dokumentasi dinilai per item checklist: checklist yang punya foto after.
        $documented = $tasks->filter(fn ($task): bool => ! empty($task->photo_after));

        $values = [];

        foreach (array_keys($metrics) as $key) {
            $values[$key] = match ($key) {
                'task_completion_rate' => $tasks->isEmpty()
                    ? null
                    : round(($done->count() / $tasks->count()) * 100, 2),
                'sla_compliance' => $completedPlans->isEmpty()
                    ? null
                    : round(($withinSla->count() / $completedPlans->count()) * 100, 2),
                'documentation_compliance' => $tasks->isEmpty()
                    ? null
                    : round(($documented->count() / $tasks->count()) * 100, 2),
                'backlog' => $backlog->count(),
                'plan_consistency' => $this->planConsistency($plans, $periodDays),
                'window_coverage' => $this->windowCoverage($plans, $periodStart, $periodEnd, $periodDays),
                'sla_efficiency' => $this->slaEfficiency($completedPlans),
                default => null,
            };
        }

        return $values;
    }

    /**
     * Indoor: jumlah hari yang punya plan ÷ jumlah hari periode.
     */
    protected function planConsistency(Collection $plans, int $periodDays): float
    {
        $days = $plans
            ->map(fn (HousekeepingPlan $plan): ?string => $plan->plan_date?->toDateString())
            ->filter()
            ->unique()
            ->count();

        return round(min(100, ($days / $periodDays) * 100), 2);
    }

    /**
     * Outdoor: jumlah hari yang tercakup rentang plan (plan_date + SLA)
     * ÷ jumlah hari periode. Plan yang tumpang tindih tidak dihitung dobel.
     */
    protected function windowCoverage(Collection $plans, Carbon $periodStart, Carbon $periodEnd, int $periodDays): float
    {
        $covered = [];

        foreach ($plans as $plan) {
            if (! $plan->plan_date) {
                continue;
            }

            $due = $plan->due_date;

            if (! $due) {
                continue;
            }

            $start = $plan->plan_date->copy()->startOfDay()->max($periodStart);
            $end = $due->copy()->startOfDay()->min($periodEnd);

            for ($day = $start->copy(); $day->lessThanOrEqualTo($end); $day->addDay()) {
                $covered[$day->toDateString()] = true;
            }
        }

        return round(min(100, (count($covered) / $periodDays) * 100), 2);
    }

    /**
     * Outdoor: rata-rata (SLA area ÷ hari penyelesaian), dibatasi 100% per plan.
     * Plan selesai jauh sebelum SLA tetap mendapat nilai penuh.
     */
    protected function slaEfficiency(Collection $completedPlans): ?float
    {
        $ratios = $completedPlans
            ->map(function (HousekeepingPlan $plan): ?float {
                $days = $plan->completion_days;

                if ($days === null) {
                    return null;
                }

                $slaDays = max(1, (int) ($plan->area?->sla_days ?? 1));

                return min(1, $slaDays / max(1, $days));
            })
            ->filter(fn ($ratio) => $ratio !== null);

        if ($ratios->isEmpty()) {
            return null;
        }

        return round($ratios->avg() * 100, 2);
    }
}
