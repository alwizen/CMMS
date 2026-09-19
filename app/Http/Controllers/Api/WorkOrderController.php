<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkOrderResource;
use App\Models\WorkOrder;
use App\Models\WorkOrderActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = WorkOrder::with(['equipment.area', 'issuedBy', 'technicianCoordinator', 'workOrderWorkers.user'])
            ->orderByDesc('created_at');

        if ($request->user()->hasRole('technician')) {
            $query->whereHas('workOrderWorkers', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            })->orWhere('technician_coordinator_id', $request->user()->id)
                ->orWhere('issued_by', $request->user()->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('classification')) {
            $query->where('classification', $request->classification);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('work_order_number', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%")
                    ->orWhereHas('equipment', function ($eq) use ($search) {
                        $eq->where('name', 'like', "%{$search}%")
                            ->orWhere('tag_number', 'like', "%{$search}%");
                    });
            });
        }

        $workOrders = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => WorkOrderResource::collection($workOrders),
            'meta' => [
                'current_page' => $workOrders->currentPage(),
                'last_page' => $workOrders->lastPage(),
                'per_page' => $workOrders->perPage(),
                'total' => $workOrders->total(),
            ],
        ]);
    }

    public function show($id): JsonResponse
    {
        $workOrder = WorkOrder::with([
            'equipment.area',
            'issuedBy',
            'technicianCoordinator',
            'maintenancePlan',
            'maintenanceSchedule',
            'maintenanceRequest',
            'workOrderActivities.activity',
            'workOrderWorkers.user',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new WorkOrderResource($workOrder),
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $workOrder = WorkOrder::findOrFail($id);

        $request->validate([
            'status' => 'sometimes|string|in:Pending,In Progress,On Hold,Completed,Cancelled',
            'note' => 'sometimes|string|max:5000',
            'start_at' => 'sometimes|date',
            'finish_at' => 'sometimes|date|nullable',
        ]);

        $workOrder->update($request->only(['status', 'note', 'start_at', 'finish_at']));

        return response()->json([
            'success' => true,
            'message' => 'Work order berhasil diperbarui.',
            'data' => new WorkOrderResource($workOrder->fresh([
                'equipment.area',
                'issuedBy',
                'technicianCoordinator',
                'workOrderActivities.activity',
                'workOrderWorkers.user',
            ])),
        ]);
    }

    public function updateActivity(Request $request, $workOrderId, $activityId): JsonResponse
    {
        $workOrderActivity = WorkOrderActivity::where('work_order_id', $workOrderId)
            ->findOrFail($activityId);

        $request->validate([
            'pre_inspection' => 'sometimes|string|max:5000',
            'follow_up' => 'sometimes|string|max:5000',
            'final_result' => 'sometimes|string|max:5000',
            'executed' => 'sometimes|boolean',
            'note' => 'sometimes|string|max:5000',
        ]);

        $workOrderActivity->update($request->only([
            'pre_inspection',
            'follow_up',
            'final_result',
            'executed',
            'note',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Aktivitas work order berhasil diperbarui.',
            'data' => $workOrderActivity->load('activity'),
        ]);
    }
}
