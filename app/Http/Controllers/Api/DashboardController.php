<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceHistory;
use App\Models\WorkOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $totalWO = WorkOrder::where('issued_by', $user->id)
            ->orWhere('technician_coordinator_id', $user->id)
            ->orWhereHas('workOrderWorkers', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->count();

        $woInProgress = WorkOrder::where('status', 'In Progress')
            ->where(function ($q) use ($user) {
                $q->where('issued_by', $user->id)
                    ->orWhere('technician_coordinator_id', $user->id)
                    ->orWhereHas('workOrderWorkers', function ($q2) use ($user) {
                        $q2->where('user_id', $user->id);
                    });
            })
            ->count();

        $woCompleted = WorkOrder::where('status', 'Completed')
            ->where(function ($q) use ($user) {
                $q->where('issued_by', $user->id)
                    ->orWhere('technician_coordinator_id', $user->id)
                    ->orWhereHas('workOrderWorkers', function ($q2) use ($user) {
                        $q2->where('user_id', $user->id);
                    });
            })
            ->count();

        $woPending = WorkOrder::where('status', 'Pending')
            ->where(function ($q) use ($user) {
                $q->where('issued_by', $user->id)
                    ->orWhere('technician_coordinator_id', $user->id)
                    ->orWhereHas('workOrderWorkers', function ($q2) use ($user) {
                        $q2->where('user_id', $user->id);
                    });
            })
            ->count();

        $recentWO = WorkOrder::with(['equipment.area'])
            ->where('issued_by', $user->id)
            ->orWhere('technician_coordinator_id', $user->id)
            ->orWhereHas('workOrderWorkers', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($wo) {
                return [
                    'id' => $wo->id,
                    'work_order_number' => $wo->work_order_number,
                    'status' => $wo->status,
                    'classification' => $wo->classification,
                    'start_at' => $wo->start_at?->toIso8601String(),
                    'equipment' => [
                        'tag_number' => $wo->equipment->tag_number ?? '-',
                        'name' => $wo->equipment->name ?? '-',
                    ],
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total_work_orders' => $totalWO,
                    'in_progress' => $woInProgress,
                    'completed' => $woCompleted,
                    'pending' => $woPending,
                ],
                'recent_work_orders' => $recentWO,
            ],
        ]);
    }
}
