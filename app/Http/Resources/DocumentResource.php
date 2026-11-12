<?php

namespace App\Http\Resources;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_number' => $this->document_number,
            'title' => $this->title,
            'document_type' => $this->document_type,
            'document_type_label' => Document::TYPES[$this->document_type] ?? $this->document_type,
            'equipment' => $this->whenLoaded('equipment', fn () => $this->equipment ? [
                'id' => $this->equipment->id,
                'tag_number' => $this->equipment->tag_number,
            ] : null),
            'revision' => $this->revision,
            'issuer' => $this->issuer,
            'issue_date' => $this->issue_date?->format('Y-m-d'),
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'expiry_status' => $this->expiryStatus(),
            'expiry_status_label' => $this->expiryStatusLabel(),
            'file_url' => $this->file_path
                ? URL::temporarySignedRoute(
                    'documents.file',
                    now()->addMinutes(30),
                    ['document' => $this->id],
                )
                : null,
            'file_name' => $this->file_path ? basename($this->file_path) : null,
            'description' => $this->description,
            'uploaded_by' => $this->whenLoaded('uploadedBy', fn () => $this->uploadedBy?->name),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
