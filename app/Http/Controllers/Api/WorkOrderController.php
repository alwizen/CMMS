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
use Illuminate\Validation\Rule;

class WorkOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = WorkOrder::with(['equipment.equipmentType', 'equipment.area', 'issuedBy', 'technicianCoordinator', 'approvedBy', 'workOrderWorkers.user'])
            ->orderByDesc('created_at');

        UserVisibility::scopeWorkOrders($query, $request->user());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('classification')) {
            $query->where('classification', $request->classification);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('start_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('start_at', '<=', $request->date_to);
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
            'approvedBy',
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

        if ($locked = $this->lockedResponse($request, $workOrder)) {
            return $locked;
        }

        // Cegah memulai/mengubah status sebelum tanggal jadwal tiba.
        $newStatus = $request->input('status');
        if (
            $newStatus !== null
            && $newStatus !== $workOrder->status
            && in_array($newStatus, [WorkOrder::STATUSES['In Progress'], WorkOrder::STATUSES['Completed']], true)
            && ! $workOrder->isExecutable()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Work order belum dapat dikerjakan sebelum tanggal '.
                    ($workOrder->start_at?->format('d M Y') ?? '-').'.',
            ], 403);
        }

        $request->validate([
            'status' => 'sometimes|string|in:Pending,In Progress,Completed,Cancelled',
            'note' => 'sometimes|string|max:5000',
            'start_at' => 'sometimes|date',
            'finish_at' => 'sometimes|date|nullable',
        ]);

        // WO shared pool: user pertama yang memulai/menyelesaikan menjadi performed by.
        if (
            $newStatus !== null
            && in_array($newStatus, [
                WorkOrder::STATUSES['In Progress'],
                WorkOrder::STATUSES['Completed'],
            ], true)
            && ! $this->claimForExecution($request, $workOrder)
        ) {
            return $this->lockedResponse($request, $workOrder->fresh())
                ?? response()->json(['success' => false, 'message' => 'Work order sedang dikerjakan user lain.'], 403);
        }

        $workOrder->update($request->only(['status', 'note', 'start_at', 'finish_at']));

        return response()->json([
            'success' => true,
            'message' => 'Work order berhasil diperbarui.',
            'data' => new WorkOrderResource($workOrder->fresh([
                'equipment.equipmentType',
                'equipment.area',
                'issuedBy',
                'technicianCoordinator',
                'approvedBy',
                'workOrderActivities.activity',
                'workOrderWorkers.user',
            ])),
        ]);
    }

    public function updateActivity(Request $request, $workOrderId, $activityId): JsonResponse
    {
        $workOrder = WorkOrder::findOrFail($workOrderId);

        $this->authorizeWorkOrder($request, $workOrder);

        if ($locked = $this->lockedResponse($request, $workOrder)) {
            return $locked;
        }

        if ($statusLocked = $this->statusEditableResponse($workOrder)) {
            return $statusLocked;
        }

        if (! $workOrder->isExecutable()) {
            return response()->json([
                'success' => false,
                'message' => 'Work order belum dapat diisi sebelum tanggal '.
                    ($workOrder->start_at?->format('d M Y') ?? '-').'.',
            ], 403);
        }

        $workOrderActivity = WorkOrderActivity::where('work_order_id', $workOrderId)
            ->findOrFail($activityId);

        $qualitative = $workOrderActivity->usesQualitativeResult();

        // Checklist kualitatif: Pre Inspection & Final Result memakai pilihan kondisi.
        // Nilai lama tetap diterima agar data existing tidak ditolak saat diedit.
        $preInspectionRule = ['sometimes', 'nullable', 'string', 'max:100'];
        $finalResultRule = ['sometimes', 'nullable', 'string', 'max:100'];

        if ($qualitative) {
            $preInspectionRule[] = Rule::in(array_keys(
                WorkOrderActivity::qualitativeOptions($workOrderActivity->pre_inspection),
            ));
            $finalResultRule[] = Rule::in(array_keys(
                WorkOrderActivity::qualitativeOptions($workOrderActivity->final_result),
            ));
        }

        $request->validate([
            'pre_inspection' => $preInspectionRule,
            'follow_up' => 'sometimes|nullable|string|max:100',
            'final_result' => $finalResultRule,
            'executed' => 'sometimes|boolean',
            'note' => 'sometimes|string|max:5000',
        ]);

        // WO shared pool: user pertama yang mengeksekusi checklist menjadi performed by.
        if ($request->boolean('executed') && ! $this->claimForExecution($request, $workOrder)) {
            return $this->lockedResponse($request, $workOrder->fresh())
                ?? response()->json(['success' => false, 'message' => 'Work order sedang dikerjakan user lain.'], 403);
        }

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

        if ($locked = $this->lockedResponse($request, $workOrder)) {
            return $locked;
        }

        if ($statusLocked = $this->statusEditableResponse($workOrder)) {
            return $statusLocked;
        }

        if (! $workOrder->isExecutable()) {
            return response()->json([
                'success' => false,
                'message' => 'Work order belum dapat diisi sebelum tanggal '.
                    ($workOrder->start_at?->format('d M Y') ?? '-').'.',
            ], 403);
        }

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

        if ($locked = $this->lockedResponse($request, $workOrder)) {
            return $locked;
        }

        if ($statusLocked = $this->statusEditableResponse($workOrder)) {
            return $statusLocked;
        }

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
    /**
     * Item checklist/foto hanya boleh diubah selama pekerjaan masih berjalan.
     */
    protected function statusEditableResponse(WorkOrder $workOrder): ?JsonResponse
    {
        if (in_array($workOrder->status, [
            WorkOrder::STATUSES['Pending'],
            WorkOrder::STATUSES['In Progress'],
        ], true)) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => $workOrder->status === WorkOrder::STATUSES['Completed']
                ? 'Work order sudah diselesaikan dan menunggu review SPV MOS.'
                : 'Work order berstatus '.$workOrder->status.' tidak dapat diubah.',
            'data' => [
                'status' => $workOrder->status,
                'approval_status' => $workOrder->approval_status,
            ],
        ], 403);
    }

    protected function authorizeWorkOrder(Request $request, WorkOrder $workOrder): void
    {
        abort_unless(
            UserVisibility::canAccessWorkOrder($workOrder, $request->user()),
            403,
            'Anda tidak memiliki akses untuk work order ini.',
        );
    }

    /**
     * Tolak perubahan bila WO sedang dikerjakan user lain. Role akses penuh
     * (supervisor/pengawas) tetap boleh menindaklanjuti.
     */
    protected function lockedResponse(Request $request, WorkOrder $workOrder): ?JsonResponse
    {
        $user = $request->user();

        if (UserVisibility::hasFullAccess($user) || ! $workOrder->isLockedFor($user)) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => $workOrder->lockMessage(),
            'data' => [
                'locked_by' => [
                    'id' => $workOrder->technician_coordinator_id,
                    'name' => $workOrder->lockedByName(),
                ],
                'status' => $workOrder->status,
            ],
        ], 403);
    }

    /**
     * Klaim WO untuk user yang mulai mengerjakan (kecuali role akses penuh).
     *
     * @return bool `false` bila WO sudah diklaim user lain.
     */
    protected function claimForExecution(Request $request, WorkOrder $workOrder): bool
    {
        $user = $request->user();

        if (! $user || UserVisibility::hasFullAccess($user)) {
            return true;
        }

        return $workOrder->claimBy($user->id);
    }
}
