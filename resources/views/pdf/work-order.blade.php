<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body { font-family: sans-serif; font-size: 10px; color: #111827; margin: 0; }
        h1 { font-size: 18px; margin: 0 0 2px; color: #111827; }
        h2 { font-size: 12px; margin: 16px 0 6px; color: #111827; border-bottom: 1px solid #d1d5db; padding-bottom: 3px; page-break-after: avoid; break-after: avoid; }
        .meta { font-size: 9px; color: #111827; margin-bottom: 12px; }
        .header { border-bottom: 3px solid #015E4E; padding-bottom: 8px; margin-bottom: 12px; }
        .header-table { width: 100%; border-collapse: collapse; margin: 0; }
        .header-table td { border: 0; padding: 0; vertical-align: middle; }
        .header-logo { text-align: right; width: 140px; }
        .header-logo img { height: 42px; width: auto; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th, td { border: 1px solid #d1d5db; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; font-weight: 600; }
        .info-table td.label { width: 26%; background: #f9fafb; color: #374151; font-weight: 600; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 8px; color: #111827; background: #e5e7eb; border: 1px solid #d1d5db; }
        .b-green { background: #dcfce7; border-color: #86efac; }
        .b-amber { background: #fef3c7; border-color: #fcd34d; }
        .b-red { background: #fee2e2; border-color: #fca5a5; }
        .b-gray { background: #e5e7eb; }
        .muted { color: #6b7280; }
        .empty { text-align: center; color: #6b7280; padding: 8px; }
        .photo-thumbs { border-collapse: separate; border-spacing: 2px; margin: 0; width: auto; }
        .photo-thumbs td { border: 0; padding: 0; }
        .photo-thumb img { width: 60px; height: 60px; border: 1px solid #e5e7eb; border-radius: 3px; display: block; }
        .approval-block { width: 100%; margin-top: 26px; }
        .approval-block td { border: 1px solid #d1d5db; background: #f9fafb; padding: 12px 16px; }
        .approval-label { font-size: 10px; color: #6b7280; margin-bottom: 3px; }
        .approval-name { font-size: 14px; font-weight: 700; color: #111827; }
        .approval-status { margin-top: 6px; }
        .footer { margin-top: 18px; font-size: 8px; color: #6b7280; text-align: right; }
        .page-break { page-break-inside: avoid; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <h1>Laporan Work Order Maintenance</h1>
                    <div class="meta">
                        No. WO: <strong>{{ $workOrder->work_order_number }}</strong> &nbsp;|&nbsp;
                        Dicetak: {{ now()->format('d M Y H:i') }}
                    </div>
                </td>
                @if($logo)
                    <td class="header-logo">
                        <img src="{{ $logo }}" alt="PPN">
                    </td>
                @endif
            </tr>
        </table>
    </div>

    @php
        $statusClass = match ($workOrder->status) {
            'Completed' => 'b-green',
            'In Progress' => 'b-amber',
            'Cancelled' => 'b-red',
            default => 'b-gray',
        };
        $approvalLabel = \App\Models\WorkOrder::APPROVAL_STATUSES[$workOrder->approval_status] ?? $workOrder->approval_status;
    @endphp

    <h2>Informasi Work Order</h2>
    <table class="info-table">
        <tr>
            <td class="label">Nomor WO</td>
            <td>{{ $workOrder->work_order_number }}</td>
            <td class="label">Status</td>
            <td><span class="badge {{ $statusClass }}">{{ $workOrder->status }}</span></td>
        </tr>
        <tr>
            <td class="label">Klasifikasi</td>
            <td>{{ $workOrder->classification ?? '-' }}</td>
            <td class="label">Interval</td>
            <td>{{ $workOrder->interval ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Fungsi</td>
            <td>{{ $workOrder->fungsi ?? '-' }}</td>
            <td class="label">Review SPV MOS</td>
            <td>{{ $approvalLabel }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Mulai</td>
            <td>{{ $workOrder->start_at?->format('d M Y H:i') ?? '-' }}</td>
            <td class="label">Tanggal Selesai</td>
            <td>{{ $workOrder->finish_at?->format('d M Y H:i') ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Sumber</td>
            <td colspan="3">
                @if($workOrder->maintenanceRequest)
                    Maintenance Request {{ $workOrder->maintenanceRequest->request_number ?? '#'.$workOrder->maintenanceRequest->id }}
                @else
                    -
                @endif
            </td>
        </tr>
        @if($workOrder->note)
            <tr>
                <td class="label">Catatan</td>
                <td colspan="3">{{ $workOrder->note }}</td>
            </tr>
        @endif
    </table>

    <h2>Equipment</h2>
    <table class="info-table">
        <tr>
            <td class="label">Tag Number</td>
            <td>{{ $workOrder->equipment?->tag_number ?? '-' }}</td>
            <td class="label">Tipe</td>
            <td>{{ $workOrder->equipment?->equipmentType?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Area</td>
            <td>{{ $workOrder->equipment?->area?->name ?? '-' }}</td>
            <td class="label">Deskripsi</td>
            <td>{{ $workOrder->equipment?->description ?? '-' }}</td>
        </tr>
    </table>

    <h2>Personel</h2>
    <table class="info-table">
        <tr>
            <td class="label">Pemberi Tugas</td>
            <td>{{ $workOrder->issuedBy?->name ?? '-' }}</td>
            <td class="label">Performed By</td>
            <td>{{ $workOrder->technicianCoordinator?->name ?? 'Belum diambil' }}</td>
        </tr>
        <tr>
            <td class="label">Direview Oleh</td>
            <td>{{ $workOrder->approvedBy?->name ?? '-' }}</td>
            <td class="label">Waktu Review</td>
            <td>{{ $workOrder->approved_at?->format('d M Y H:i') ?? '-' }}</td>
        </tr>
        @if($workOrder->approval_notes)
            <tr>
                <td class="label">Catatan Review</td>
                <td colspan="3">{{ $workOrder->approval_notes }}</td>
            </tr>
        @endif
    </table>

    @if($workOrder->workOrderWorkers->isNotEmpty())
        <h2>Pelaksana</h2>
        <table>
            <thead>
                <tr>
                    <th style="width:22px;">#</th>
                    <th>Nama</th>
                    <th>Fungsi</th>
                    <th>Mulai</th>
                    <th>Selesai</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($workOrder->workOrderWorkers as $i => $worker)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $worker->user?->name ?? '-' }}</td>
                        <td>{{ $worker->user?->fungsi ?? '-' }}</td>
                        <td>{{ $worker->started_at?->format('d M Y H:i') ?? '-' }}</td>
                        <td>{{ $worker->finished_at?->format('d M Y H:i') ?? '-' }}</td>
                        <td>{{ $worker->note ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Checklist &amp; Hasil Eksekusi</h2>
    <table>
        <thead>
            <tr>
                <th style="width:22px;">#</th>
                <th>Aktivitas</th>
                <th>Pre Inspection</th>
                <th>Final Result</th>
                <th>Unit</th>
                <th>Status</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($activities as $i => $item)
                @php $activity = $item['activity']; @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>
                        {{ $activity->displayName() }}
                        @if($activity->reference)
                            <div class="muted">Ref: {{ $activity->reference }}</div>
                        @endif
                    </td>
                    <td>{{ $activity->pre_inspection ?? '-' }}</td>
                    <td>{{ $activity->final_result ?? '-' }}</td>
                    <td>{{ $activity->unit ?? '-' }}</td>
                    <td>
                        @if($activity->executed)
                            <span class="badge b-green">Sudah</span>
                        @else
                            <span class="badge b-gray">Belum</span>
                        @endif
                    </td>
                    <td>{{ $activity->note ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty">Tidak ada item checklist.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @php
        $withPhotos = $activities->filter(fn ($item): bool => ! empty($item['photos']));
    @endphp

    @if($withPhotos->isNotEmpty())
        <h2>Dokumentasi Foto</h2>
        <table>
            <thead>
                <tr>
                    <th style="width:34%;">Aktivitas</th>
                    <th>Foto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($withPhotos as $item)
                    <tr class="page-break">
                        <td>{{ $item['activity']->displayName() }}</td>
                        <td>
                            <table class="photo-thumbs">
                                <tr>
                                    @foreach($item['photos'] as $photo)
                                        <td><span class="photo-thumb"><img src="{{ $photo }}" alt="foto"></span></td>
                                    @endforeach
                                </tr>
                            </table>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @php
        $approvalText = match ($workOrder->approval_status) {
            \App\Models\WorkOrder::APPROVAL_APPROVED => 'Sudah disetujui oleh',
            \App\Models\WorkOrder::APPROVAL_REJECTED => 'Ditolak oleh',
            default => 'Menunggu persetujuan',
        };
        $approvalBadge = match ($workOrder->approval_status) {
            \App\Models\WorkOrder::APPROVAL_APPROVED => 'b-green',
            \App\Models\WorkOrder::APPROVAL_REJECTED => 'b-red',
            default => 'b-amber',
        };
    @endphp

    <table class="approval-block">
        <tr>
            <td>
                <div class="approval-label">{{ $approvalText }}</div>
                <div class="approval-name">{{ $workOrder->approvedBy?->name ?? 'SPV MOS' }}</div>
                <div class="approval-status">
                    <span class="badge {{ $approvalBadge }}">{{ $approvalLabel }}</span>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Dicetak otomatis oleh sistem RUMAT &mdash; {{ now()->format('d M Y H:i') }}
    </div>
</body>
</html>
