<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MaintenanceRequestResource;
use App\Models\MaintenanceRequest;
use App\Support\DocumentNumber;
use App\Support\UserVisibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MaintenanceRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MaintenanceRequest::with(['equipment.area', 'reportedBy'])
            ->orderByDesc('created_at');

        UserVisibility::scopeRequests($query, $request->user());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('equipment_id')) {
            $query->where('equipment_id', $request->equipment_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('equipment', function ($eq) use ($search) {
                        $eq->where('tag_number', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
            });
        }

        $requests = $query->paginate($this->perPage($request));

        return response()->json([
            'success' => true,
            'data' => MaintenanceRequestResource::collection($requests),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ]);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $maintenanceRequest = MaintenanceRequest::with([
            'equipment.equipmentType',
            'equipment.area',
            'reportedBy',
            'approvedBy',
            'workOrders',
        ])->findOrFail($id);

        $this->authorizeRequest($request, $maintenanceRequest);

        return response()->json([
            'success' => true,
            'data' => new MaintenanceRequestResource($maintenanceRequest),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'operation_status' => 'required|string|in:Running,Stopped,Standby,Faulty',
            'description' => 'required|string|max:5000',
            'damage_date' => 'required|date',
            'damage_time' => 'nullable|date_format:H:i',
            'equipment_condition' => 'nullable|string|max:5000',
            'impact' => 'nullable|string|max:5000',
            'early_action' => 'nullable|string|max:5000',
            'photo' => 'nullable|array',
            'photo.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        $requestNumber = DocumentNumber::generate('maintenance_request', MaintenanceRequest::class, 'request_number');

        $photoPaths = [];
        if ($request->hasFile('photo')) {
            foreach ($request->file('photo') as $file) {
                $photoPaths[] = $file->store('maintenance-requests', 'public');
            }
        }

        $maintenanceRequest = MaintenanceRequest::create([
            'equipment_id' => $request->equipment_id,
            'request_number' => $requestNumber,
            'reported_by' => $request->user()->id,
            'operation_status' => $request->operation_status,
            'description' => $request->description,
            'damage_date' => $request->damage_date,
            'damage_time' => $request->damage_time,
            'equipment_condition' => $request->equipment_condition,
            'impact' => $request->impact,
            'early_action' => $request->early_action,
            'status' => MaintenanceRequest::STATUSES['Open'],
            'photo' => $photoPaths ?: null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance request berhasil dibuat.',
            'data' => new MaintenanceRequestResource($maintenanceRequest->load(['equipment.equipmentType', 'equipment.area', 'reportedBy'])),
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $maintenanceRequest = MaintenanceRequest::findOrFail($id);

        $this->authorizeRequest($request, $maintenanceRequest);

        $request->validate([
            'equipment_id' => 'sometimes|exists:equipment,id',
            'operation_status' => 'sometimes|string|in:Running,Stopped,Standby,Faulty',
            'description' => 'sometimes|string|max:5000',
            'damage_date' => 'sometimes|date',
            'damage_time' => 'nullable|date_format:H:i',
            'equipment_condition' => 'nullable|string|max:5000',
            'impact' => 'nullable|string|max:5000',
            'early_action' => 'nullable|string|max:5000',
            'status' => 'sometimes|string|in:'.implode(',', array_keys(MaintenanceRequest::STATUSES)),
            'photo' => 'nullable|array',
            'photo.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        $data = $request->only([
            'equipment_id',
            'operation_status',
            'description',
            'damage_date',
            'damage_time',
            'equipment_condition',
            'impact',
            'early_action',
        ]);

        if ($request->filled('status') && $request->status !== $maintenanceRequest->status) {
            $this->applyStatusChange($request, $maintenanceRequest, $request->status);
        }

        if ($request->hasFile('photo')) {
            if ($maintenanceRequest->photo) {
                foreach ($maintenanceRequest->photo as $oldPhoto) {
                    Storage::disk('public')->delete($oldPhoto);
                }
            }
            $photoPaths = [];
            foreach ($request->file('photo') as $file) {
                $photoPaths[] = $file->store('maintenance-requests', 'public');
            }
            $data['photo'] = $photoPaths;
        }

        $maintenanceRequest->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance request berhasil diperbarui.',
            'data' => new MaintenanceRequestResource($maintenanceRequest->fresh(['equipment.equipmentType', 'equipment.area', 'reportedBy'])),
        ]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $maintenanceRequest = MaintenanceRequest::findOrFail($id);

        $this->authorizeRequest($request, $maintenanceRequest);

        if ($maintenanceRequest->photo) {
            foreach ($maintenanceRequest->photo as $photo) {
                Storage::disk('public')->delete($photo);
            }
        }

        $maintenanceRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Maintenance request berhasil dihapus.',
        ]);
    }

    /**
     * Hanya pelapor, approver, atau role akses penuh yang boleh mengakses request.
     */
    protected function authorizeRequest(Request $request, MaintenanceRequest $maintenanceRequest): void
    {
        abort_unless(
            UserVisibility::canAccessRequest($maintenanceRequest, $request->user()),
            403,
            'Anda tidak memiliki akses untuk maintenance request ini.',
        );
    }

    /**
     * Perubahan status hanya boleh oleh role akses penuh dan mengikuti alur transisi.
     */
    protected function applyStatusChange(Request $request, MaintenanceRequest $maintenanceRequest, string $status): void
    {
        abort_unless(
            UserVisibility::hasFullAccess($request->user()),
            403,
            'Anda tidak memiliki izin mengubah status maintenance request.',
        );

        $allowed = [
            MaintenanceRequest::STATUSES['Open'] => [
                MaintenanceRequest::STATUSES['Approved'],
                MaintenanceRequest::STATUSES['Rejected'],
            ],
            MaintenanceRequest::STATUSES['Approved'] => [
                MaintenanceRequest::STATUSES['Assigned'],
                MaintenanceRequest::STATUSES['In Progress'],
                MaintenanceRequest::STATUSES['Rejected'],
            ],
            MaintenanceRequest::STATUSES['Assigned'] => [
                MaintenanceRequest::STATUSES['In Progress'],
                MaintenanceRequest::STATUSES['Rejected'],
            ],
            MaintenanceRequest::STATUSES['In Progress'] => [
                MaintenanceRequest::STATUSES['Completed'],
            ],
        ];

        $current = $maintenanceRequest->status;
        $allowedNext = $allowed[$current] ?? [];

        abort_unless(
            in_array($status, $allowedNext, true),
            422,
            "Perubahan status dari {$current} ke {$status} tidak diizinkan.",
        );

        $maintenanceRequest->forceFill([
            'status' => $status,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ])->save();
    }
}
