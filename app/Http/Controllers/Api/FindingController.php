<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkOrderFindingResource;
use App\Models\MaintenanceRequest;
use App\Models\WorkOrderFinding;
use App\Support\UserVisibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FindingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = WorkOrderFinding::with([
            'workOrder',
            'equipment.equipmentType',
            'activity',
            'workOrderActivity.activity',
            'maintenanceRequest',
        ])->orderByDesc('created_at');

        UserVisibility::scopeFindings($query, $request->user());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('answer_type')) {
            $query->where('answer_type', $request->answer_type);
        }

        if ($request->filled('work_order_id')) {
            $query->where('work_order_id', $request->work_order_id);
        }

        if ($request->filled('equipment_id')) {
            $query->where('equipment_id', $request->equipment_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search): void {
                $q->where('finding_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('equipment', function ($eq) use ($search): void {
                        $eq->where('tag_number', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
            });
        }

        $findings = $query->paginate($this->perPage($request));

        return response()->json([
            'success' => true,
            'data' => WorkOrderFindingResource::collection($findings),
            'meta' => [
                'current_page' => $findings->currentPage(),
                'last_page' => $findings->lastPage(),
                'per_page' => $findings->perPage(),
                'total' => $findings->total(),
            ],
        ]);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $finding = WorkOrderFinding::with([
            'workOrder',
            'equipment.equipmentType',
            'activity',
            'workOrderActivity.activity',
            'maintenanceRequest',
            'resolutionWorkOrder',
        ])->findOrFail($id);

        $this->authorizeFinding($request, $finding);

        return response()->json([
            'success' => true,
            'data' => new WorkOrderFindingResource($finding),
        ]);
    }

    /**
     * Ajukan maintenance request perbaikan dari temuan.
     */
    public function request(Request $request, $id): JsonResponse
    {
        $finding = WorkOrderFinding::with(['equipment', 'workOrderActivity'])->findOrFail($id);

        $this->authorizeFinding($request, $finding);

        if (! $finding->isOpen()) {
            return response()->json([
                'success' => false,
                'message' => 'Temuan ini sudah ditindaklanjuti.',
            ], 422);
        }

        if ($finding->equipment_id === null) {
            return response()->json([
                'success' => false,
                'message' => 'Temuan tidak memiliki equipment sehingga tidak bisa diajukan.',
            ], 422);
        }

        $maintenanceRequest = MaintenanceRequest::create([
            'equipment_id' => $finding->equipment_id,
            'reported_by' => $request->user()?->id ?? $finding->reported_by,
            'operation_status' => 'Faulty',
            'description' => $finding->description
                ?: $finding->displayName().' — hasil inspeksi: '.$finding->result,
            'damage_date' => now()->toDateString(),
            'damage_time' => now()->format('H:i'),
            'equipment_condition' => $finding->result,
            'early_action' => $finding->notes,
            'status' => MaintenanceRequest::STATUSES['Open'],
            'photo' => $finding->photos,
        ]);

        $finding->update([
            'status' => WorkOrderFinding::STATUSES['Requested'],
            'maintenance_request_id' => $maintenanceRequest->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Request perbaikan berhasil diajukan.',
            'data' => new WorkOrderFindingResource(
                $finding->fresh(['workOrder', 'equipment.equipmentType', 'maintenanceRequest']),
            ),
        ], 201);
    }

    protected function authorizeFinding(Request $request, WorkOrderFinding $finding): void
    {
        abort_unless(
            UserVisibility::canAccessFinding($finding, $request->user()),
            403,
            'Anda tidak memiliki akses untuk temuan ini.',
        );
    }
}
