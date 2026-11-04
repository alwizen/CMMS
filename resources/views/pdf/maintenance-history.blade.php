<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #1f2937; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .meta { font-size: 9px; color: #6b7280; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 4px 6px; text-align: left; }
        th { background: #f3f4f6; font-weight: 600; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 6px; font-size: 9px; }
    </style>
</head>
<body>
    <h1>Laporan Maintenance History</h1>
    <div class="meta">
        Periode: {{ $from ?? '-' }} s/d {{ $until ?? '-' }} |
        Total: {{ $histories->count() }} record |
        Dicetak: {{ now()->format('d M Y H:i') }}
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Tanggal</th>
                <th>Equipment</th>
                <th>Type</th>
                <th>Klasifikasi</th>
                <th>Status</th>
                <th>Performed By</th>
                <th>Durasi (menit)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($histories as $i => $h)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $h->maintenance_date?->format('d M Y') ?? '-' }}</td>
                    <td>{{ $h->equipment?->tag_number ?? '-' }} — {{ $h->equipment?->equipmentType?->name ?? '-' }}</td>
                    <td>{{ $h->maintenance_type ?? '-' }}</td>
                    <td>{{ $h->classification ?? '-' }}</td>
                    <td>{{ $h->status ?? '-' }}</td>
                    <td>{{ $h->performedBy?->name ?? $h->performed_by ?? '-' }}</td>
                    <td>{{ $h->duration_minutes ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center; color:#9ca3af;">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($histories->isNotEmpty() && $histories->first()->description)
        <p style="margin-top:10px; font-size:9px; color:#6b7280;">Deskripsi contoh record pertama: {{ Str::limit($histories->first()->description, 300) }}</p>
    @endif
</body>
</html>
