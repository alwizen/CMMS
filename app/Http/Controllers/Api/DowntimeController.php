<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DowntimeResource;
use App\Models\MaintenanceRequest;
use App\Support\UserVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DowntimeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MaintenanceRequest::with(['equipment.area', 'workOrders'])
            ->where('status', '!=', MaintenanceRequest::STATUSES['Rejected'])
            ->where(function (Builder $q): void {
                $q->whereDoesntHave('workOrders')
                    ->orWhereHas('workOrders', fn (Builder $wo) => $wo->where('classification', 'Corrective'));
            })
            ->orderByDesc('damage_date')
            ->orderByDesc('id');

        UserVisibility::scopeRequests($query, $request->user());

        if ($request->filled('equipment_id')) {
            $query->where('equipment_id', $request->equipment_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('damage_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('damage_date', '<=', $request->date_to);
        }

        $requests = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => DowntimeResource::collection($requests),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ]);
    }
}
