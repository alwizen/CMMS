<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentFileController extends Controller
{
    /**
     * Sajikan file dokumen hanya bila URL bertanda tangan masih berlaku.
     */
    public function show(Request $request, Document $document): StreamedResponse
    {
        abort_unless($request->hasValidSignature(), 403, 'Tautan dokumen sudah kedaluwarsa.');

        abort_unless($document->file_path, 404);

        $disk = $this->diskFor($document->file_path);

        abort_unless($disk !== null, 404);

        return Storage::disk($disk)->response(
            $document->file_path,
            null,
            ['Content-Disposition' => 'inline'],
        );
    }

    /**
     * Cari disk penyimpanan file: privat lebih dulu, lalu publik untuk data lama.
     */
    protected function diskFor(string $path): ?string
    {
        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return $disk;
            }
        }

        return null;
    }
}
