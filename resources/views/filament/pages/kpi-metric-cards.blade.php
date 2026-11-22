@php
    $total = max(1, $results->count());

    $rows = collect($metrics)->map(function (array $metric, string $key) use ($results, $total) {
        $achieved = $results
            ->filter(fn ($result) => (bool) ($result->metrics[$key]['achieved'] ?? false))
            ->count();

        $rate = (int) round(($achieved / $total) * 100);

        $tone = match (true) {
            $results->isEmpty() => ['fg' => '#6b7280', 'bg' => '#f3f4f6', 'bar' => '#d1d5db'],
            $rate >= 80 => ['fg' => '#15803d', 'bg' => '#dcfce7', 'bar' => '#22c55e'],
            $rate >= 60 => ['fg' => '#b45309', 'bg' => '#fef3c7', 'bar' => '#f59e0b'],
            default => ['fg' => '#b91c1c', 'bg' => '#fee2e2', 'bar' => '#ef4444'],
        };

        $targetLabel = $metric['unit'] === '%'
            ? $metric['target'].'%'
            : $metric['target'].' '.$metric['unit'];

        return $metric + [
            'key' => $key,
            'achieved' => $achieved,
            'rate' => $rate,
            'tone' => $tone,
            'target_label' => $targetLabel,
        ];
    })->values();
@endphp

<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:0.875rem;">
    @foreach ($rows as $metric)
        <div style="display:flex; flex-direction:column; gap:0.6rem; border:1px solid #e5e7eb; border-radius:0.875rem; padding:1rem 1.1rem; background:#fff; box-shadow:0 1px 2px rgba(16,24,40,.04);">
            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:0.75rem;">
                <div style="font-size:0.85rem; font-weight:600; color:#111827; line-height:1.3;">
                    {{ $metric['label'] }}
                </div>
                <span style="flex-shrink:0; font-size:0.65rem; font-weight:700; letter-spacing:.04em; padding:0.2rem 0.5rem; border-radius:9999px; background:#f3f4f6; color:#4b5563;">
                    {{ rtrim(rtrim((string) $metric['weight'], '0'), '.') }}%
                </span>
            </div>

            @if (filled($metric['description'] ?? null))
                <div style="font-size:0.72rem; line-height:1.5; color:#6b7280;">
                    {{ $metric['description'] }}
                </div>
            @endif

            <div style="display:flex; align-items:baseline; gap:0.35rem; margin-top:0.15rem;">
                <span style="font-size:1.4rem; font-weight:700; color:{{ $metric['tone']['fg'] }}; font-variant-numeric:tabular-nums;">
                    {{ $metric['average'] }}
                </span>
                <span style="font-size:0.68rem; font-weight:500; color:#9ca3af;">
                    rata-rata
                </span>
            </div>

            <div>
                <div style="display:flex; align-items:center; justify-content:space-between; font-size:0.68rem; color:#6b7280; margin-bottom:0.3rem;">
                    <span>Target {{ $metric['target_label'] }}</span>
                    <span style="font-weight:600; color:{{ $metric['tone']['fg'] }};">
                        {{ $metric['achieved'] }}/{{ $results->count() }} tercapai
                    </span>
                </div>
                <div style="height:0.45rem; border-radius:9999px; background:#f3f4f6; overflow:hidden;">
                    <div style="width:{{ $metric['rate'] }}%; height:100%; border-radius:9999px; background:{{ $metric['tone']['bar'] }};"></div>
                </div>
            </div>
        </div>
    @endforeach
</div>
