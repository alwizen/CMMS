@php
    use Illuminate\Support\Facades\Storage;

    $photos = collect($type === 'before' ? ($record->photo_before ?? []) : ($record->photo_after ?? []))
        ->filter()
        ->values();

    $label = $type === 'before' ? 'Foto Before' : 'Foto After';
@endphp

<div style="display:flex; flex-direction:column; gap:1rem; font-size:0.875rem; color:#111827;">
    <div style="display:flex; align-items:center; gap:0.5rem;">
        <span style="font-weight:600;">{{ $record->name }}</span>
        <span style="display:inline-flex; align-items:center; border-radius:9999px; padding:0.125rem 0.5rem; font-size:0.75rem; font-weight:500; background:#f3f4f6; color:#4b5563;">
            {{ $label }} · {{ $photos->count() }} foto
        </span>
    </div>

    @if ($record->facility?->name)
        <div style="font-size:0.75rem; color:#4b5563;">
            Fasilitas: <strong style="color:#111827;">{{ $record->facility->name }}</strong>
        </div>
    @endif

    @if ($photos->isEmpty())
        <div style="border:1px dashed #d1d5db; border-radius:0.5rem; padding:1.25rem; text-align:center; font-size:0.75rem; color:#6b7280;">
            Belum ada {{ strtolower($label) }}.
        </div>
    @else
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(180px, 1fr)); gap:0.75rem;">
            @foreach ($photos as $photo)
                @php($url = Storage::disk('public')->url($photo))
                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                   style="display:block; overflow:hidden; border:1px solid #e5e7eb; border-radius:0.5rem; background:#f3f4f6;">
                    <img src="{{ $url }}" alt="{{ $label }}" loading="lazy"
                         style="display:block; width:100%; aspect-ratio:1 / 1; object-fit:cover;">
                </a>
            @endforeach
        </div>
    @endif
</div>
