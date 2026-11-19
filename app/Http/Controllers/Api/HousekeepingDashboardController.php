<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HousekeepingPlan;
use App\Models\HousekeepingPlanTask;
use App\Models\User;
use App\Support\HousekeepingVisibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HousekeepingDashboardController extends Controller
{
    /**
     * Ringkasan housekeeping untuk dashboard aplikasi mobile.
     *
     * Hanya user housekeeping (`patra-jasa`) dan role akses penuh yang
     * boleh mengakses. Statistik dihitung dari plan yang relevan, bukan
     * dari work order maintenance.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        abort_unless($this->canAccess($user), 403, 'Anda tidak berhak mengakses dashboard ini.');

        $today = now()->toDateString();

        // Patra-jasa hanya melihat plan area yang ia pertanggungjawabkan.
        $openPlansQuery = HousekeepingPlan::query()
            ->where('status', '!=', HousekeepingPlan::STATUSES['Completed'])
            ->with('area:id,name,sla_days');

        HousekeepingVisibility::scopePlans($openPlansQuery, $user);

        $openPlans = $openPlansQuery->get();

        $activePlans = $openPlans->reject(fn (HousekeepingPlan $plan): bool => $plan->is_overdue)->values();
        $overduePlans = $openPlans->filter(fn (HousekeepingPlan $plan): bool => $plan->is_overdue)->values();

        $plansToday = $openPlans->filter(
            fn (HousekeepingPlan $plan): bool => $plan->plan_date?->toDateString() === $today,
        );

        $completedTodayQuery = HousekeepingPlan::query()
            ->where('status', HousekeepingPlan::STATUSES['Completed'])
            ->whereDate('plan_date', '<=', $today)
            ->with('area:id,name,sla_days');

        HousekeepingVisibility::scopePlans($completedTodayQuery, $user);

        $completedToday = $completedTodayQuery
            ->get()
            ->filter(fn (HousekeepingPlan $plan): bool => $plan->completed_at?->toDateString() === $today);

        $activePlanIds = $openPlans->pluck('id')->all();

        // Semua angka task dibatasi pada plan yang boleh dilihat user, agar
        // progres di dashboard tidak tercampur dengan area petugas lain.
        $scopedTasks = fn () => HousekeepingVisibility::scopeTasks(
            HousekeepingPlanTask::query()->whereIn('housekeeping_plan_id', $activePlanIds),
            $user,
        );

        $tasksTotal = $scopedTasks()->count();
        $tasksDone = $scopedTasks()->where('status', 'Done')->count();
        $tasksOpen = $scopedTasks()->whereIn('status', ['Pending', 'In Progress'])->count();

        $tasksDoneToday = HousekeepingVisibility::scopeTasks(
            HousekeepingPlanTask::query()->whereDate('completed_at', $today),
            $user,
        )->count();

        $completionRate = $tasksTotal > 0
            ? (int) round(($tasksDone / $tasksTotal) * 100)
            : 0;

        $planCards = $openPlans
            ->sortBy('plan_date')
            ->take(10)
            ->map(fn (HousekeepingPlan $plan): array => [
                'id' => $plan->id,
                'plan_number' => $plan->plan_number,
                'plan_date' => $plan->plan_date?->toDateString(),
                'status' => $plan->status,
                'approval_status' => $plan->approval_status,
                'progress_percent' => $plan->progress_percent,
                'sla_days' => $plan->area?->sla_days,
                'due_date' => $plan->due_date?->toDateString(),
                'sla_status' => $plan->sla_status,
                'is_overdue' => $plan->is_overdue,
                'area' => [
                    'id' => $plan->area?->id,
                    'name' => $plan->area?->name,
                ],
                'task_count' => $plan->tasks()->count(),
                'task_done_count' => $plan->tasks()->where('status', 'Done')->count(),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'plans_today' => $plansToday->count(),
                    'plans_active' => $activePlans->count(),
                    'plans_overdue' => $overduePlans->count(),
                    'plans_completed_today' => $completedToday->count(),
                    'tasks_open' => $tasksOpen,
                    'tasks_done_today' => $tasksDoneToday,
                    'tasks_total' => $tasksTotal,
                    'tasks_done' => $tasksDone,
                    'completion_rate' => $completionRate,
                ],
                'active_plans' => $planCards,
            ],
        ]);
    }

    protected function canAccess(?User $user): bool
    {
        if (! $user || ! $user->status) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'spv_gos', 'pengawas', 'patra-jasa']);
    }
}
