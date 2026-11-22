@php
    use Illuminate\Support\Facades\Storage;

    $photos = collect($record->photos ?? [])->filter()->values();
    $meta = array_filter([
        $record->displayName(),
        $record->workOrder?->work_order_number,
        $record->executed ? 'Sudah dieksekusi' : null,
    ]);
@endphp

<div style="display:flex; flex-direction:column; gap:1rem; font-size:0.875rem; color:#111827;">
    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:0.75rem; border:1px solid #e5e7eb; border-radius:0.75rem; padding:0.875rem 1rem; background:#f9fafb;">
        <div style="min-width:0;">
            <div style="font-weight:600;">{{ $record->displayName() }}</div>
            @if ($meta !== [])
                <div style="margin-top:0.2rem; font-size:0.75rem; color:#6b7280;">
                    {{ implode(' · ', $meta) }}
                </div>
            @endif
        </div>

        <span style="flex-shrink:0; display:inline-flex; align-items:center; border-radius:9999px; padding:0.15rem 0.6rem; font-size:0.75rem; font-weight:600; background:#e5e7eb; color:#374151;">
            {{ $photos->count() }} foto
        </span>
    </div>

    @if ($photos->isEmpty())
        <div style="border:1px dashed #d1d5db; border-radius:0.5rem; padding:1.25rem; text-align:center; font-size:0.75rem; color:#6b7280;">
            Belum ada foto dokumentasi untuk item ini.
        </div>
    @else
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:0.75rem;">
            @foreach ($photos as $index => $photo)
                @php($url = Storage::disk('public')->url($photo))
                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                   style="display:block; overflow:hidden; border:1px solid #e5e7eb; border-radius:0.5rem; background:#f3f4f6; text-decoration:none;">
                    <img src="{{ $url }}" alt="Foto {{ $index + 1 }}" loading="lazy"
                         style="display:block; width:100%; aspect-ratio:4 / 3; object-fit:cover;">
                    <div style="padding:0.4rem 0.6rem; font-size:0.7rem; color:#4b5563; border-top:1px solid #e5e7eb; background:#fff;">
                        Foto {{ $index + 1 }} · klik untuk ukuran penuh
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
