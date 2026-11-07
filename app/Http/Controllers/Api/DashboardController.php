<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Support\UserVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $totalWO = UserVisibility::scopeWorkOrders(WorkOrder::query(), $user)->count();

        $woInProgress = UserVisibility::scopeWorkOrders(
            WorkOrder::query()->where('status', WorkOrder::STATUSES['In Progress']),
            $user,
        )->count();

        $woCompleted = UserVisibility::scopeWorkOrders(
            WorkOrder::query()->where('status', WorkOrder::STATUSES['Completed']),
            $user,
        )->count();

        $woPending = UserVisibility::scopeWorkOrders(
            WorkOrder::query()->where('status', WorkOrder::STATUSES['Pending']),
            $user,
        )->count();

        $openStatuses = [
            WorkOrder::STATUSES['Pending'],
            WorkOrder::STATUSES['In Progress'],
            WorkOrder::STATUSES['On Hold'],
        ];

        $woDueToday = $this->ownWorkOrders($user)
            ->whereIn('status', $openStatuses)
            ->whereDate('start_at', today())
            ->count();

        $woOverdue = $this->ownWorkOrders($user)
            ->whereIn('status', $openStatuses)
            ->whereDate('start_at', '<', today())
            ->count();

        $todayWO = $this->ownWorkOrders($user)
            ->with(['equipment.equipmentType', 'equipment.area'])
            ->whereIn('status', $openStatuses)
            ->whereDate('start_at', today())
            ->orderBy('start_at')
            ->limit(5)
            ->get()
            ->map(fn (WorkOrder $wo): array => [
                'id' => $wo->id,
                'work_order_number' => $wo->work_order_number,
                'status' => $wo->status,
                'classification' => $wo->classification,
                'start_at' => $wo->start_at?->toIso8601String(),
                'start_time' => $wo->start_at?->format('H:i'),
                'equipment' => [
                    'tag_number' => $wo->equipment?->tag_number ?? '-',
                    'equipment_type' => $wo->equipment?->equipmentType?->name,
                    'name' => $wo->equipment?->description ?? '-',
                ],
            ]);

        $recentWO = $this->ownWorkOrders($user)
            ->with(['equipment.equipmentType', 'equipment.area'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn (WorkOrder $wo): array => [
                'id' => $wo->id,
                'work_order_number' => $wo->work_order_number,
                'status' => $wo->status,
                'classification' => $wo->classification,
                'start_at' => $wo->start_at?->toIso8601String(),
                'equipment' => [
                    'tag_number' => $wo->equipment?->tag_number ?? '-',
                    'equipment_type' => $wo->equipment?->equipmentType?->name,
                    'name' => $wo->equipment?->description ?? '-',
                ],
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total_work_orders' => $totalWO,
                    'in_progress' => $woInProgress,
                    'completed' => $woCompleted,
                    'pending' => $woPending,
                    'due_today' => $woDueToday,
                    'overdue' => $woOverdue,
                ],
                'today_work_orders' => $todayWO,
                'recent_work_orders' => $recentWO,
            ],
        ]);
    }

    /**
     * Work order milik user yang login saja (koordinator, pemberi tugas, atau pelaksana).
     */
    protected function ownWorkOrders(?object $user): Builder
    {
        $userId = $user?->id;

        return WorkOrder::query()->where(function (Builder $query) use ($userId): void {
            $query->where('technician_coordinator_id', $userId)
                ->orWhere('issued_by', $userId)
                ->orWhereHas('workOrderWorkers', fn (Builder $worker) => $worker->where('user_id', $userId));
        });
    }
}
