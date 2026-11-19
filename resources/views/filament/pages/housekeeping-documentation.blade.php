@php
    use Illuminate\Support\Facades\Storage;

    $before = collect($record->planFacility?->photo_before ?? [])->filter()->values();
    $after = collect($record->planFacility?->photo_after ?? [])->filter()->values();

    $meta = array_filter([
        $record->plan?->area?->name,
        $record->plan?->plan_date?->format('d M Y'),
        $record->plan?->plan_number,
    ]);

    $statusColor = match ($record->status) {
        'Done' => ['bg' => '#dcfce7', 'text' => '#166534'],
        'In Progress' => ['bg' => '#fef3c7', 'text' => '#92400e'],
        default => ['bg' => '#f3f4f6', 'text' => '#374151'],
    };

    $groups = [
        ['label' => 'Foto Before', 'photos' => $before],
        ['label' => 'Foto After', 'photos' => $after],
    ];
@endphp

<div style="display:flex; flex-direction:column; gap:1.5rem; font-size:0.875rem; color:#111827;">
    {{-- Header --}}
    <div style="border:1px solid #e5e7eb; border-radius:0.75rem; padding:1rem 1.25rem; background:#f9fafb;">
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:0.75rem;">
            <div style="min-width:0;">
                <div style="font-size:1rem; font-weight:600; color:#111827;">{{ $record->name }}</div>
                @if ($meta)
                    <div style="margin-top:0.25rem; font-size:0.75rem; color:#6b7280;">
                        {{ implode(' · ', $meta) }}
                    </div>
                @endif
            </div>

            <span style="flex-shrink:0; display:inline-flex; align-items:center; border-radius:9999px; padding:0.25rem 0.625rem; font-size:0.75rem; font-weight:500; background:{{ $statusColor['bg'] }}; color:{{ $statusColor['text'] }};">
                {{ $record->status }}
            </span>
        </div>

        @if ($record->facility?->name)
            <div style="margin-top:0.75rem; font-size:0.75rem; color:#4b5563;">
                Fasilitas: <strong style="color:#111827;">{{ $record->facility->name }}</strong>
            </div>
        @endif
    </div>

    {{-- Photo groups --}}
    @foreach ($groups as $group)
        <div>
            <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.75rem;">
                <span style="font-weight:600; color:#111827;">{{ $group['label'] }}</span>
                <span style="display:inline-flex; align-items:center; border-radius:9999px; padding:0.125rem 0.5rem; font-size:0.75rem; font-weight:500; background:#f3f4f6; color:#4b5563;">
                    {{ $group['photos']->count() }} foto
                </span>
            </div>

            @if ($group['photos']->isEmpty())
                <div style="border:1px dashed #d1d5db; border-radius:0.5rem; padding:1.25rem; text-align:center; font-size:0.75rem; color:#6b7280;">
                    Belum ada {{ strtolower($group['label']) }}.
                </div>
            @else
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(180px, 1fr)); gap:0.75rem;">
                    @foreach ($group['photos'] as $photo)
                        @php($url = Storage::disk('public')->url($photo))
                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                           style="display:block; overflow:hidden; border:1px solid #e5e7eb; border-radius:0.5rem; background:#f3f4f6;">
                            <img src="{{ $url }}" alt="{{ $group['label'] }}" loading="lazy"
                                 style="display:block; width:100%; aspect-ratio:1 / 1; object-fit:cover;">
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach

    {{-- Note --}}
    @if ($record->note)
        <div>
            <div style="font-weight:600; color:#111827; margin-bottom:0.5rem;">Catatan</div>
            <div style="border:1px solid #e5e7eb; border-radius:0.5rem; padding:0.75rem; color:#374151;">
                {{ $record->note }}
            </div>
        </div>
    @endif
</div>
