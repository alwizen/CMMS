<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Document::with(['equipment', 'uploadedBy'])
            ->orderBy('title');

        if ($request->filled('type')) {
            $query->where('document_type', $request->type);
        }

        if ($request->filled('equipment_id')) {
            $query->where('equipment_id', $request->equipment_id);
        }

        if ($request->filled('expiry_status')) {
            $query->ofExpiryStatus($request->expiry_status);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhere('issuer', 'like', "%{$search}%")
                    ->orWhereHas('equipment', fn ($eq) => $eq->where('tag_number', 'like', "%{$search}%"));
            });
        }

        $documents = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => DocumentResource::collection($documents),
            'meta' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ],
        ]);
    }

    public function show($id): JsonResponse
    {
        $document = Document::with(['equipment', 'uploadedBy'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new DocumentResource($document),
        ]);
    }
}
