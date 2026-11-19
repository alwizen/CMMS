<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HousekeepingPlanResource;
use App\Http\Resources\HousekeepingRequestResource;
use App\Models\HousekeepingRequest;
use App\Support\HousekeepingPlanGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HousekeepingRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = HousekeepingRequest::query()
            ->with(['area:id,name,code', 'facility:id,name,code', 'requestedBy:id,name', 'approvedBy:id,name', 'plan:id,plan_number,plan_date,status'])
            ->orderByDesc('request_date')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('area_id')) {
            $query->where('housekeeping_area_id', $request->area_id);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('request_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('request_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search): void {
                $q->where('request_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $requests = $query->paginate($this->perPage($request));

        return response()->json([
            'success' => true,
            'data' => HousekeepingRequestResource::collection($requests),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $housekeepingRequest = HousekeepingRequest::query()
            ->with(['area:id,name,code', 'facility:id,name,code', 'requestedBy:id,name', 'approvedBy:id,name', 'plan:id,plan_number,plan_date,status'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new HousekeepingRequestResource($housekeepingRequest),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'housekeeping_area_id' => 'required|integer|exists:housekeeping_areas,id',
            'housekeeping_facility_id' => 'nullable|integer|exists:housekeeping_facilities,id',
            'request_date' => 'required|date',
            'request_time' => 'nullable|date_format:H:i',
            'description' => 'required|string|max:5000',
            'priority' => 'nullable|string|in:Low,Normal,High',
            'photo' => 'nullable|array',
            'photo.*' => 'string',
        ]);

        $housekeepingRequest = HousekeepingRequest::create([
            'housekeeping_area_id' => $request->input('housekeeping_area_id'),
            'housekeeping_facility_id' => $request->input('housekeeping_facility_id'),
            'request_date' => $request->input('request_date'),
            'request_time' => $request->input('request_time'),
            'description' => $request->input('description'),
            'priority' => $request->input('priority', 'Normal'),
            'status' => HousekeepingRequest::STATUSES['Open'],
            'photo' => $request->input('photo'),
            'requested_by' => auth()->id(),
        ]);

        $housekeepingRequest->load(['area:id,name,code', 'facility:id,name,code', 'requestedBy:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Housekeeping request berhasil dibuat.',
            'data' => new HousekeepingRequestResource($housekeepingRequest),
        ], 201);
    }

    /**
     * Setujui request dan buat Plan Harian dari checklist fasilitas terkait.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $housekeepingRequest = HousekeepingRequest::findOrFail($id);

        if (! $housekeepingRequest->isOpen()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya request berstatus Open yang bisa disetujui.',
            ], 422);
        }

        $request->validate([
            'plan_date' => 'nullable|date',
            'manpower_ids' => 'nullable|array',
            'manpower_ids.*' => 'integer|exists:housekeeping_manpowers,id',
            'approval_notes' => 'nullable|string|max:5000',
        ]);

        $plan = app(HousekeepingPlanGenerator::class)->generateFromRequest(
            request: $housekeepingRequest,
            planDate: $request->input('plan_date'),
            manpowerIds: $request->input('manpower_ids', []),
            createdBy: auth()->id(),
        );

        $housekeepingRequest->update([
            'status' => HousekeepingRequest::STATUSES['Approved'],
            'approval_notes' => $request->input('approval_notes'),
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'housekeeping_plan_id' => $plan->id,
        ]);

        $housekeepingRequest->load([
            'area:id,name,code',
            'facility:id,name,code',
            'requestedBy:id,name',
            'approvedBy:id,name',
            'plan:id,plan_number,plan_date,status',
        ]);

        $plan->load(['area.company:id,name', 'area.supervisor:id,name', 'manpowers', 'tasks']);

        return response()->json([
            'success' => true,
            'message' => 'Request disetujui dan Plan Harian '.$plan->plan_number.' dibuat.',
            'data' => new HousekeepingRequestResource($housekeepingRequest),
            'plan' => new HousekeepingPlanResource($plan),
        ]);
    }

    /**
     * Tolak request housekeeping.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $housekeepingRequest = HousekeepingRequest::findOrFail($id);

        if (! $housekeepingRequest->isOpen()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya request berstatus Open yang bisa ditolak.',
            ], 422);
        }

        $request->validate([
            'approval_notes' => 'required|string|max:5000',
        ]);

        $housekeepingRequest->update([
            'status' => HousekeepingRequest::STATUSES['Rejected'],
            'approval_notes' => $request->input('approval_notes'),
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $housekeepingRequest->load(['area:id,name,code', 'facility:id,name,code', 'requestedBy:id,name', 'approvedBy:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Request housekeeping ditolak.',
            'data' => new HousekeepingRequestResource($housekeepingRequest),
        ]);
    }
}
