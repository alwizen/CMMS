@php
    $total = max(1, (int) $summary['count']);
    $average = (int) $summary['average_score'];
    $achieve = (int) $summary['achieve'];
    $warning = (int) $summary['warning'] ?? 0;
    $below = (int) $summary['below'];

    $scoreTone = match (true) {
        $summary['count'] === 0 => ['fg' => '#6b7280', 'bg' => '#f3f4f6', 'bar' => '#9ca3af', 'label' => 'Belum ada data'],
        $average >= 80 => ['fg' => '#15803d', 'bg' => '#dcfce7', 'bar' => '#22c55e', 'label' => 'Achieve'],
        $average >= 60 => ['fg' => '#b45309', 'bg' => '#fef3c7', 'bar' => '#f59e0b', 'label' => 'Warning'],
        default => ['fg' => '#b91c1c', 'bg' => '#fee2e2', 'bar' => '#ef4444', 'label' => 'Below Target'],
    };

    $cards = [
        [
            'label' => 'Objek Dinilai',
            'value' => (string) $summary['count'],
            'description' => 'Orang/area dengan data',
            'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
            'fg' => '#1d4ed8',
            'bg' => '#dbeafe',
            'ring' => 'rgba(59,130,246,.16)',
        ],
        [
            'label' => 'Rata-rata Skor',
            'value' => $average,
            'suffix' => '/100',
            'description' => 'Periode: '.$period->label,
            'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
            'fg' => $scoreTone['fg'],
            'bg' => $scoreTone['bg'],
            'ring' => 'rgba(0,66,54,.14)',
        ],
        [
            'label' => 'Achieve',
            'value' => (string) $achieve,
            'description' => 'Skor ≥ 80',
            'icon' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            'fg' => '#15803d',
            'bg' => '#dcfce7',
            'ring' => 'rgba(34,197,94,.16)',
        ],
        [
            'label' => 'Below Target',
            'value' => (string) $below,
            'description' => 'Skor < 60',
            'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
            'fg' => $below > 0 ? '#b91c1c' : '#6b7280',
            'bg' => $below > 0 ? '#fee2e2' : '#f3f4f6',
            'ring' => $below > 0 ? 'rgba(239,68,68,.16)' : 'rgba(107,114,128,.14)',
        ],
    ];

    $segments = [
        ['label' => 'Achieve', 'value' => $achieve, 'color' => '#22c55e'],
        ['label' => 'Warning', 'value' => $warning, 'color' => '#f59e0b'],
        ['label' => 'Below Target', 'value' => $below, 'color' => '#ef4444'],
    ];
@endphp

<div style="display:flex; flex-direction:column; gap:1rem;">
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:0.875rem;">
        @foreach ($cards as $card)
            <div style="position:relative; overflow:hidden; border:1px solid #e5e7eb; border-radius:0.875rem; padding:1rem 1.1rem; background:#fff; box-shadow:0 1px 2px rgba(16,24,40,.04);">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:0.75rem;">
                    <div style="min-width:0;">
                        <div style="font-size:0.7rem; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color:#6b7280;">
                            {{ $card['label'] }}
                        </div>
                        <div style="margin-top:0.4rem; display:flex; align-items:baseline; gap:0.25rem;">
                            <span style="font-size:1.75rem; line-height:1.1; font-weight:700; color:#111827; font-variant-numeric:tabular-nums;">
                                {{ $card['value'] }}
                            </span>
                            @isset($card['suffix'])
                                <span style="font-size:0.75rem; font-weight:600; color:#9ca3af;">{{ $card['suffix'] }}</span>
                            @endisset
                        </div>
                    </div>

                    <span style="flex-shrink:0; display:grid; place-items:center; width:2.4rem; height:2.4rem; border-radius:0.75rem; background:{{ $card['bg'] }}; color:{{ $card['fg'] }}; box-shadow:0 0 0 4px {{ $card['ring'] }};">
                        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" style="width:1.15rem; height:1.15rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}"/>
                        </svg>
                    </span>
                </div>

                <div style="margin-top:0.65rem; font-size:0.72rem; color:#6b7280; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    {{ $card['description'] }}
                </div>
            </div>
        @endforeach
    </div>

    {{-- Distribusi status --}}
    <div style="border:1px solid #e5e7eb; border-radius:0.875rem; padding:1rem 1.1rem; background:#fff;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:0.7rem;">
            <div style="font-size:0.78rem; font-weight:600; color:#111827;">Distribusi Status</div>
            <div style="display:inline-flex; align-items:center; gap:0.4rem; font-size:0.7rem; font-weight:600; padding:0.15rem 0.55rem; border-radius:9999px; background:{{ $scoreTone['bg'] }}; color:{{ $scoreTone['fg'] }};">
                {{ $scoreTone['label'] }}
            </div>
        </div>

        @if ($summary['count'] === 0)
            <div style="height:0.6rem; border-radius:9999px; background:#f3f4f6;"></div>
            <div style="margin-top:0.6rem; font-size:0.72rem; color:#9ca3af;">
                Belum ada objek yang dinilai pada periode ini.
            </div>
        @else
            <div style="display:flex; width:100%; height:0.6rem; border-radius:9999px; overflow:hidden; background:#f3f4f6;">
                @foreach ($segments as $segment)
                    @if ($segment['value'] > 0)
                        <div
                            style="width:{{ round(($segment['value'] / $total) * 100, 2) }}%; background:{{ $segment['color'] }};"
                            title="{{ $segment['label'] }}: {{ $segment['value'] }}"
                        ></div>
                    @endif
                @endforeach
            </div>

            <div style="display:flex; flex-wrap:wrap; gap:1rem; margin-top:0.7rem;">
                @foreach ($segments as $segment)
                    <div style="display:inline-flex; align-items:center; gap:0.4rem; font-size:0.72rem; color:#4b5563;">
                        <span style="width:0.55rem; height:0.55rem; border-radius:0.2rem; background:{{ $segment['color'] }};"></span>
                        {{ $segment['label'] }}
                        <strong style="color:#111827;">{{ $segment['value'] }}</strong>
                        <span style="color:#9ca3af;">({{ (int) round(($segment['value'] / $total) * 100) }}%)</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
