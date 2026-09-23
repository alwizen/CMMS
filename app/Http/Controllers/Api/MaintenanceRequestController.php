<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MaintenanceRequestResource;
use App\Models\MaintenanceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MaintenanceRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MaintenanceRequest::with(['equipment.area', 'reportedBy'])
            ->orderByDesc('created_at');

        if ($request->user()->hasRole('technician')) {
            $query->where('reported_by', $request->user()->id);
        }

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
                        $eq->where('name', 'like', "%{$search}%")
                            ->orWhere('tag_number', 'like', "%{$search}%");
                    });
            });
        }

        $requests = $query->paginate($request->get('per_page', 15));

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

    public function show($id): JsonResponse
    {
        $maintenanceRequest = MaintenanceRequest::with([
            'equipment.area',
            'reportedBy',
            'approvedBy',
            'workOrders',
        ])->findOrFail($id);

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
            'status' => 'required|string|in:Open,Assigned,In Progress,Completed,Rejected',
            'photo' => 'nullable|array',
            'photo.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        $requestNumber = 'MR-' . now()->format('Ymd') . '-' . str_pad(
            MaintenanceRequest::whereDate('created_at', today())->count() + 1,
            4,
            '0',
            STR_PAD_LEFT
        );

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
            'status' => $request->status,
            'photo' => $photoPaths ?: null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance request berhasil dibuat.',
            'data' => new MaintenanceRequestResource($maintenanceRequest->load(['equipment.area', 'reportedBy'])),
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $maintenanceRequest = MaintenanceRequest::findOrFail($id);

        $request->validate([
            'equipment_id' => 'sometimes|exists:equipment,id',
            'operation_status' => 'sometimes|string|in:Running,Stopped,Standby,Faulty',
            'description' => 'sometimes|string|max:5000',
            'damage_date' => 'sometimes|date',
            'damage_time' => 'nullable|date_format:H:i',
            'equipment_condition' => 'nullable|string|max:5000',
            'impact' => 'nullable|string|max:5000',
            'early_action' => 'nullable|string|max:5000',
            'status' => 'sometimes|string|in:Open,Assigned,In Progress,Completed,Rejected',
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
            'status',
        ]);

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
            'data' => new MaintenanceRequestResource($maintenanceRequest->fresh(['equipment.area', 'reportedBy'])),
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $maintenanceRequest = MaintenanceRequest::findOrFail($id);

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
}
