<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HousekeepingPlanResource;
use App\Http\Resources\HousekeepingRequestResource;
use App\Models\HousekeepingFacility;
use App\Models\HousekeepingRequest;
use App\Support\HousekeepingPlanGenerator;
use App\Support\UserVisibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HousekeepingRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = HousekeepingRequest::query()
            ->with(['area:id,name,code', 'facility:id,name,code', 'requestedBy:id,name', 'approvedBy:id,name', 'plan:id,plan_number,plan_date,status'])
            ->orderByDesc('request_date')
            ->orderByDesc('id');

        if ($request->boolean('mine')) {
            $query->where('requested_by', $request->user()->id);
        }

        if ($request->filled('requested_by')) {
            $query->where('requested_by', $request->input('requested_by'));
        }

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

    /**
     * Buat request housekeeping dari lapangan.
     *
     * Foto dapat dikirim sebagai file multipart pada `photos[]`, atau sebagai
     * path string pada `photo[]` (kompatibilitas).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'housekeeping_area_id' => 'required|integer|exists:housekeeping_areas,id',
            'housekeeping_facility_id' => 'nullable|integer|exists:housekeeping_facilities,id',
            'request_date' => 'required|date',
            'request_time' => 'nullable|date_format:H:i',
            'description' => 'required|string|max:5000',
            'priority' => 'nullable|string|in:Low,Normal,High',
            'photo' => 'nullable|array|max:5',
            'photo.*' => 'string',
            'photos' => 'nullable|array|max:5',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $photoPaths = $this->storePhotos($request, $request->input('photo', []));

        $housekeepingRequest = HousekeepingRequest::create([
            'housekeeping_area_id' => $request->input('housekeeping_area_id'),
            'housekeeping_facility_id' => $request->input('housekeeping_facility_id'),
            'request_date' => $request->input('request_date'),
            'request_time' => $request->input('request_time'),
            'description' => $request->input('description'),
            'priority' => $request->input('priority', 'Normal'),
            'status' => HousekeepingRequest::STATUSES['Open'],
            'photo' => $photoPaths ?: null,
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
     * Ubah request yang masih berstatus Open.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $housekeepingRequest = HousekeepingRequest::findOrFail($id);

        $this->authorizeRequest($request, $housekeepingRequest);

        if (! $housekeepingRequest->isOpen()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya request berstatus Open yang bisa diubah.',
            ], 422);
        }

        $request->validate([
            'housekeeping_area_id' => 'sometimes|integer|exists:housekeeping_areas,id',
            'housekeeping_facility_id' => 'nullable|integer|exists:housekeeping_facilities,id',
            'request_date' => 'sometimes|date',
            'request_time' => 'nullable|date_format:H:i',
            'description' => 'sometimes|string|max:5000',
            'priority' => 'sometimes|string|in:Low,Normal,High',
        ]);

        $data = $request->only([
            'housekeeping_area_id',
            'housekeeping_facility_id',
            'request_date',
            'request_time',
            'description',
            'priority',
        ]);

        $facilityId = $data['housekeeping_facility_id'] ?? $housekeepingRequest->housekeeping_facility_id;
        $areaId = $data['housekeeping_area_id'] ?? $housekeepingRequest->housekeeping_area_id;

        if ($facilityId && ! $this->facilityBelongsToArea((int) $facilityId, (int) $areaId)) {
            return response()->json([
                'success' => false,
                'message' => 'Fasilitas tidak berada pada area yang dipilih.',
            ], 422);
        }

        $housekeepingRequest->update($data);

        $housekeepingRequest->load(['area:id,name,code', 'facility:id,name,code', 'requestedBy:id,name', 'approvedBy:id,name', 'plan:id,plan_number,plan_date,status']);

        return response()->json([
            'success' => true,
            'message' => 'Housekeeping request berhasil diperbarui.',
            'data' => new HousekeepingRequestResource($housekeepingRequest),
        ]);
    }

    /**
     * Hapus request yang masih berstatus Open beserta fotonya.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $housekeepingRequest = HousekeepingRequest::findOrFail($id);

        $this->authorizeRequest($request, $housekeepingRequest);

        if (! $housekeepingRequest->isOpen()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya request berstatus Open yang bisa dihapus.',
            ], 422);
        }

        foreach ($housekeepingRequest->photo ?? [] as $photo) {
            Storage::disk('public')->delete($photo);
        }

        $housekeepingRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Housekeeping request berhasil dihapus.',
        ]);
    }

    /**
     * Unggah foto tambahan untuk request (maksimal 5 foto total).
     */
    public function uploadPhotos(Request $request, int $id): JsonResponse
    {
        $housekeepingRequest = HousekeepingRequest::findOrFail($id);

        $this->authorizeRequest($request, $housekeepingRequest);

        $existing = $housekeepingRequest->photo ?? [];
        $remaining = max(0, 5 - count($existing));

        if ($remaining === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Maksimal 5 foto per request. Hapus salah satu foto terlebih dahulu.',
            ], 422);
        }

        $request->validate([
            'photos' => 'required|array|min:1|max:'.$remaining,
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $paths = $this->storePhotos($request, $existing);

        $housekeepingRequest->update(['photo' => $paths]);

        $housekeepingRequest->load(['area:id,name,code', 'facility:id,name,code', 'requestedBy:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Foto request berhasil diunggah.',
            'data' => new HousekeepingRequestResource($housekeepingRequest),
        ]);
    }

    /**
     * Hapus satu foto request.
     */
    public function deletePhoto(Request $request, int $id): JsonResponse
    {
        $housekeepingRequest = HousekeepingRequest::findOrFail($id);

        $this->authorizeRequest($request, $housekeepingRequest);

        $request->validate([
            'path' => 'required|string',
        ]);

        $photos = $housekeepingRequest->photo ?? [];
        $path = $request->input('path');

        if (! in_array($path, $photos, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Foto tidak ditemukan pada request ini.',
            ], 404);
        }

        Storage::disk('public')->delete($path);

        $housekeepingRequest->update([
            'photo' => array_values(array_diff($photos, [$path])),
        ]);

        $housekeepingRequest->load(['area:id,name,code', 'facility:id,name,code', 'requestedBy:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Foto request berhasil dihapus.',
            'data' => new HousekeepingRequestResource($housekeepingRequest),
        ]);
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
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
            'approval_notes' => 'nullable|string|max:5000',
        ]);

        try {
            $plan = app(HousekeepingPlanGenerator::class)->generateFromRequest(
                request: $housekeepingRequest,
                planDate: $request->input('plan_date'),
                manpowerIds: $request->input('manpower_ids', []),
                createdBy: auth()->id(),
                userIds: $request->input('user_ids', []),
            );
        } catch (\RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

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

        $plan->load(['area.company:id,name', 'area.supervisor:id,name', 'manpowers', 'users', 'tasks']);

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

    /**
     * Hanya pelapor atau role akses penuh yang boleh mengubah request.
     */
    protected function authorizeRequest(Request $request, HousekeepingRequest $housekeepingRequest): void
    {
        $user = $request->user();

        abort_unless(
            UserVisibility::hasFullAccess($user) || $housekeepingRequest->requested_by === $user?->id,
            403,
            'Anda tidak memiliki akses untuk housekeeping request ini.',
        );
    }

    protected function facilityBelongsToArea(int $facilityId, int $areaId): bool
    {
        return HousekeepingFacility::query()
            ->whereKey($facilityId)
            ->where('housekeeping_area_id', $areaId)
            ->exists();
    }

    /**
     * @param  array<int, string>  $existing
     * @return array<int, string>
     */
    protected function storePhotos(Request $request, array $existing): array
    {
        $paths = array_values($existing);

        foreach ($request->file('photos', []) as $file) {
            $paths[] = $file->store('housekeeping/requests', 'public');
        }

        return array_values(array_slice($paths, 0, 5));
    }
}
