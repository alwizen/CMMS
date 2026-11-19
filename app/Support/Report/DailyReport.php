<?php

namespace App\Support\Report;

use App\Models\HousekeepingPlan;
use App\Models\HousekeepingPlanTask;
use App\Models\MaintenanceRequest;
use App\Models\WorkOrder;
use App\Models\WorkOrderActivity;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Menyusun data Laporan Harian (maintenance + housekeeping) untuk satu tanggal.
 */
class DailyReport
{
    public function build(CarbonInterface|string $date): array
    {
        $day = Carbon::parse($date)->startOfDay();

        return [
            'date' => $day,
            'maintenance' => $this->maintenance($day),
            'housekeeping' => $this->housekeeping($day),
            'requests' => $this->requests($day),
        ];
    }

    protected function maintenance(CarbonInterface $day): array
    {
        $base = WorkOrder::query()
            ->whereDate('start_at', $day)
            ->with(['equipment.equipmentType', 'equipment.area', 'technicianCoordinator']);

        $workOrders = (clone $base)->orderBy('start_at')->get();

        $workOrderIds = $workOrders->pluck('id');

        $checklistTotal = WorkOrderActivity::query()
            ->whereIn('work_order_id', $workOrderIds)
            ->count();

        $checklistDone = WorkOrderActivity::query()
            ->whereIn('work_order_id', $workOrderIds)
            ->where('executed', true)
            ->count();

        return [
            'total' => $workOrders->count(),
            'completed' => $workOrders->where('status', WorkOrder::STATUSES['Completed'])->count(),
            'in_progress' => $workOrders->where('status', WorkOrder::STATUSES['In Progress'])->count(),
            'pending' => $workOrders->where('status', WorkOrder::STATUSES['Pending'])->count(),
            'preventive' => $workOrders->where('classification', 'Preventive')->count(),
            'corrective' => $workOrders->where('classification', 'Corrective')->count(),
            'checklist_total' => $checklistTotal,
            'checklist_done' => $checklistDone,
            'completion_rate' => $workOrders->count() > 0
                ? (int) round(($workOrders->where('status', WorkOrder::STATUSES['Completed'])->count() / $workOrders->count()) * 100)
                : 0,
            'work_orders' => $workOrders,
        ];
    }

    protected function housekeeping(CarbonInterface $day): array
    {
        $with = ['area.company', 'area.supervisor', 'tasks.facility', 'facilities.facility'];

        // Plan yang dimulai pada tanggal laporan.
        $started = HousekeepingPlan::query()
            ->whereDate('plan_date', $day)
            ->with($with)
            ->orderBy('id')
            ->get();

        // Seluruh pekerjaan yang masih berjalan pada tanggal laporan, termasuk
        // yang sudah melewati SLA agar tidak ada pekerjaan yang "hilang" dari
        // laporan harian. Plan dengan SLA 14 hari tetap muncul setiap hari
        // sampai benar-benar selesai.
        $active = HousekeepingPlan::query()
            ->whereDate('plan_date', '<=', $day)
            ->where('status', '!=', HousekeepingPlan::STATUSES['Completed'])
            ->with($with)
            ->orderBy('plan_date')
            ->get();

        // Plan yang selesai pada tanggal laporan (task terakhir Done di hari itu).
        $completedPlans = HousekeepingPlan::query()
            ->whereDate('plan_date', '<=', $day)
            ->where('status', HousekeepingPlan::STATUSES['Completed'])
            ->with($with)
            ->orderBy('plan_date')
            ->get()
            ->filter(fn (HousekeepingPlan $plan): bool => $plan->completed_at?->toDateString() === $day->toDateString());

        // Task yang selesai pada tanggal laporan (bisa dari plan mana pun).
        $completedTasks = HousekeepingPlanTask::query()
            ->whereDate('completed_at', $day)
            ->with(['plan.area.company', 'plan.area.supervisor', 'facility'])
            ->orderBy('completed_at')
            ->get();

        $planList = $active
            ->merge($completedPlans)
            ->merge($started)
            ->unique('id')
            ->sortBy('plan_date');

        $tasks = $active->flatMap(fn (HousekeepingPlan $plan): Collection => $plan->tasks);

        $done = $tasks->where('status', 'Done')->count();

        // Satu daftar berisi seluruh pekerjaan pada tanggal laporan:
        // checklist plan yang masih berjalan + pekerjaan yang selesai hari ini.
        // Dengan begitu sekali generate laporan seluruh pekerjaan tampil,
        // baik yang masih In Progress/Pending maupun yang sudah Done.
        $tasksToday = $active
            ->flatMap(fn (HousekeepingPlan $plan): Collection => $plan->tasks)
            ->merge($completedTasks)
            ->unique('id')
            ->sortBy([
                fn (HousekeepingPlanTask $task): string => (string) $task->plan?->area?->name,
                fn (HousekeepingPlanTask $task): int => (int) $task->plan?->plan_date?->getTimestamp(),
                fn (HousekeepingPlanTask $task): string => (string) $task->facility?->name,
                fn (HousekeepingPlanTask $task): int => (int) $task->id,
            ])
            ->values();

        return [
            'plans' => $planList->count(),
            'tasks_total' => $tasks->count(),
            'tasks_done' => $done,
            'tasks_in_progress' => $tasks->where('status', 'In Progress')->count(),
            'tasks_pending' => $tasks->where('status', 'Pending')->count(),
            'completion_rate' => $tasks->count() > 0
                ? (int) round(($done / $tasks->count()) * 100)
                : 0,
            'plans_started' => $started,
            'plans_active' => $active,
            'plans_completed_today' => $completedPlans,
            'tasks_completed_today' => $completedTasks,
            'tasks_today' => $tasksToday,
            // Dipertahankan untuk kompatibilitas view/laporan lama.
            'plan_list' => $planList,
        ];
    }

    protected function requests(CarbonInterface $day): array
    {
        $maintenance = MaintenanceRequest::query()
            ->whereDate('damage_date', $day)
            ->with(['equipment.equipmentType', 'equipment.area'])
            ->orderBy('id')
            ->get();

        return [
            'maintenance_total' => $maintenance->count(),
            'maintenance_open' => $maintenance->where('status', MaintenanceRequest::STATUSES['Open'])->count(),
            'maintenance_completed' => $maintenance->where('status', MaintenanceRequest::STATUSES['Completed'])->count(),
            'maintenance_rejected' => $maintenance->where('status', MaintenanceRequest::STATUSES['Rejected'])->count(),
            'maintenance_list' => $maintenance,
        ];
    }
}
