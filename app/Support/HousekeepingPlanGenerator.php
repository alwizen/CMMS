<?php

namespace App\Support;

use App\Models\HousekeepingArea;
use App\Models\HousekeepingManpower;
use App\Models\HousekeepingPlan;
use App\Models\HousekeepingPlanTask;
use App\Models\HousekeepingRequest;
use App\Models\HousekeepingTask;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HousekeepingPlanGenerator
{
    /**
     * Generate plan harian housekeeping sekaligus checklist dari master
     * checklist (HousekeepingTask) aktif milik fasilitas di area terpilih.
     *
     * @param  array<int, int|string>  $areaIds
     * @param  array<int, int|string>  $manpowerIds
     * @return array{created: int, plans: array<int, HousekeepingPlan>, skipped: array<int, string>, skip_reasons: array<string, string>}
     */
    public function generate(
        array $areaIds,
        string|Carbon $planDate,
        array $manpowerIds = [],
        ?string $note = null,
        ?int $createdBy = null,
    ): array {
        $date = Carbon::parse($planDate)->startOfDay();

        $areas = HousekeepingArea::query()
            ->whereIn('id', $areaIds)
            ->orderBy('name')
            ->get();

        $plans = [];
        $skipped = [];
        $skipReasons = [];

        foreach ($areas as $area) {
            $tasks = HousekeepingTask::query()
                ->where('is_active', true)
                ->whereHas('facility', fn ($query) => $query
                    ->where('housekeeping_area_id', $area->id)
                    ->where('is_active', true))
                ->orderBy('name')
                ->get();

            if ($tasks->isEmpty()) {
                $skipped[] = $area->name;
                $skipReasons[$area->name] = 'Tidak ada checklist aktif pada fasilitas di area ini';

                continue;
            }

            $existing = HousekeepingPlan::query()
                ->where('housekeeping_area_id', $area->id)
                ->whereDate('plan_date', $date->toDateString())
                ->exists();

            if ($existing) {
                $skipped[] = $area->name;
                $skipReasons[$area->name] = 'Plan untuk area ini pada tanggal tersebut sudah ada';

                continue;
            }

            $plans[] = DB::transaction(function () use ($area, $tasks, $date, $manpowerIds, $note, $createdBy): HousekeepingPlan {
                $plan = HousekeepingPlan::create([
                    'housekeeping_area_id' => $area->id,
                    'plan_date' => $date->toDateString(),
                    'status' => HousekeepingPlan::STATUSES['Draft'],
                    'note' => $note ?? 'Dibuat otomatis dari generate plan harian housekeeping',
                    'created_by' => $createdBy,
                ]);

                $this->fillTasks($plan, $tasks);
                $this->attachManpowers($plan, $area, $manpowerIds);

                return $plan;
            });
        }

        return [
            'created' => count($plans),
            'plans' => $plans,
            'skipped' => $skipped,
            'skip_reasons' => $skipReasons,
        ];
    }

    /**
     * Generate plan dari satu housekeeping request yang sudah disetujui.
     *
     * @param  array<int, int|string>  $manpowerIds
     */
    public function generateFromRequest(
        HousekeepingRequest $request,
        string|Carbon|null $planDate = null,
        array $manpowerIds = [],
        ?int $createdBy = null,
    ): HousekeepingPlan {
        $date = Carbon::parse($planDate ?? $request->request_date ?? now())->startOfDay();

        $tasks = $this->tasksForRequest($request);

        return DB::transaction(function () use ($request, $tasks, $date, $manpowerIds, $createdBy): HousekeepingPlan {
            $plan = HousekeepingPlan::create([
                'housekeeping_area_id' => $request->housekeeping_area_id,
                'plan_date' => $date->toDateString(),
                'status' => HousekeepingPlan::STATUSES['Draft'],
                'note' => 'Dibuat dari request '.$request->request_number.': '.$request->description,
                'created_by' => $createdBy ?? auth()->id(),
            ]);

            $this->fillTasks($plan, $tasks);
            $this->attachManpowers($plan, $request->area, $manpowerIds);

            return $plan;
        });
    }

    /**
     * Checklist untuk request: seluruh checklist fasilitas yang diminta,
     * atau seluruh checklist aktif di area bila request tidak menyebut fasilitas.
     *
     * @return Collection<int, HousekeepingTask>
     */
    public function tasksForRequest(HousekeepingRequest $request): Collection
    {
        return HousekeepingTask::query()
            ->where('is_active', true)
            ->when(
                $request->housekeeping_facility_id,
                fn ($query) => $query->where('housekeeping_facility_id', $request->housekeeping_facility_id),
                fn ($query) => $query->whereHas(
                    'facility',
                    fn ($facility) => $facility->where('housekeeping_area_id', $request->housekeeping_area_id),
                ),
            )
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  Collection<int, HousekeepingTask>  $tasks
     */
    protected function fillTasks(HousekeepingPlan $plan, Collection $tasks): void
    {
        foreach ($tasks as $task) {
            HousekeepingPlanTask::create([
                'housekeeping_plan_id' => $plan->id,
                'housekeeping_task_id' => $task->id,
                'housekeeping_facility_id' => $task->housekeeping_facility_id,
                'name' => $task->name,
                'status' => HousekeepingPlanTask::STATUSES['Pending'],
            ]);
        }
    }

    /**
     * @param  array<int, int|string>  $manpowerIds  Kosong = semua manpower aktif area.
     */
    protected function attachManpowers(HousekeepingPlan $plan, ?HousekeepingArea $area, array $manpowerIds): void
    {
        if (! $area) {
            return;
        }

        $ids = HousekeepingManpower::query()
            ->where('housekeeping_area_id', $area->id)
            ->where('is_active', true)
            ->when($manpowerIds !== [], fn ($query) => $query->whereIn('id', $manpowerIds))
            ->pluck('id')
            ->all();

        if ($ids !== []) {
            $plan->manpowers()->sync($ids);
        }
    }
}
