<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkOrderActivityResource;
use App\Http\Resources\WorkOrderResource;
use App\Models\WorkOrder;
use App\Models\WorkOrderActivity;
use App\Support\UserVisibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WorkOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = WorkOrder::with(['equipment.equipmentType', 'equipment.area', 'issuedBy', 'technicianCoordinator', 'workOrderWorkers.user'])
            ->orderByDesc('created_at');

        UserVisibility::scopeWorkOrders($query, $request->user());

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
                        $eq->where('tag_number', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
            });
        }

        $workOrders = $query->paginate($this->perPage($request));

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

    public function show(Request $request, $id): JsonResponse
    {
        $workOrder = WorkOrder::with([
            'equipment.equipmentType',
            'equipment.area',
            'issuedBy',
            'technicianCoordinator',
            'maintenancePlan',
            'maintenanceSchedule',
            'maintenanceRequest',
            'workOrderActivities.activity',
            'workOrderWorkers.user',
        ])->findOrFail($id);

        $this->authorizeWorkOrder($request, $workOrder);

        return response()->json([
            'success' => true,
            'data' => new WorkOrderResource($workOrder),
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $workOrder = WorkOrder::findOrFail($id);

        $this->authorizeWorkOrder($request, $workOrder);

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
                'equipment.equipmentType',
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
        $workOrder = WorkOrder::findOrFail($workOrderId);

        $this->authorizeWorkOrder($request, $workOrder);

        $workOrderActivity = WorkOrderActivity::where('work_order_id', $workOrderId)
            ->findOrFail($activityId);

        $request->validate([
            'pre_inspection' => 'sometimes|nullable|string|max:100',
            'follow_up' => 'sometimes|nullable|string|max:100',
            'final_result' => 'sometimes|nullable|string|max:100',
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

    /**
     * Unggah foto dokumentasi untuk satu item checklist (maksimal 3 foto total).
     */
    public function uploadActivityPhotos(Request $request, $workOrderId, $activityId): JsonResponse
    {
        $workOrder = WorkOrder::findOrFail($workOrderId);

        $this->authorizeWorkOrder($request, $workOrder);

        $workOrderActivity = WorkOrderActivity::where('work_order_id', $workOrderId)
            ->findOrFail($activityId);

        $existing = $workOrderActivity->photos ?? [];
        $remaining = max(0, 3 - count($existing));

        if ($remaining === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Maksimal 3 foto per aktivitas. Hapus salah satu foto terlebih dahulu.',
            ], 422);
        }

        $request->validate([
            'photos' => 'required|array|min:1|max:'.$remaining,
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $paths = $existing;

        foreach ($request->file('photos', []) as $file) {
            $paths[] = $file->store('work-order-activities', 'public');
        }

        $workOrderActivity->update(['photos' => $paths]);

        return response()->json([
            'success' => true,
            'message' => 'Foto aktivitas berhasil diunggah.',
            'data' => new WorkOrderActivityResource($workOrderActivity->load('activity')),
        ]);
    }

    /**
     * Hapus satu foto dokumentasi aktivitas.
     */
    public function deleteActivityPhoto(Request $request, $workOrderId, $activityId): JsonResponse
    {
        $workOrder = WorkOrder::findOrFail($workOrderId);

        $this->authorizeWorkOrder($request, $workOrder);

        $workOrderActivity = WorkOrderActivity::where('work_order_id', $workOrderId)
            ->findOrFail($activityId);

        $request->validate([
            'path' => 'required|string',
        ]);

        $photos = $workOrderActivity->photos ?? [];
        $path = $request->input('path');

        if (! in_array($path, $photos, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Foto tidak ditemukan pada aktivitas ini.',
            ], 404);
        }

        Storage::disk('public')->delete($path);

        $workOrderActivity->update([
            'photos' => array_values(array_diff($photos, [$path])),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Foto berhasil dihapus.',
            'data' => new WorkOrderActivityResource($workOrderActivity->load('activity')),
        ]);
    }

    /**
     * Hanya user yang terkait WO (koordinator, pemberi tugas, pelaksana)
     * atau role dengan akses penuh yang boleh mengakses.
     */
    protected function authorizeWorkOrder(Request $request, WorkOrder $workOrder): void
    {
        abort_unless(
            UserVisibility::canAccessWorkOrder($workOrder, $request->user()),
            403,
            'Anda tidak memiliki akses untuk work order ini.',
        );
    }
}
