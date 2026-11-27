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
        .header { border-bottom: 3px solid #111827; padding-bottom: 8px; margin-bottom: 10px; }
        .header-table { width: 100%; border-collapse: collapse; margin: 0; }
        .header-table td { border: 0; padding: 0; vertical-align: middle; }
        .header-logo { text-align: right; width: 140px; }
        .header-logo img { height: 38px; width: auto; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th, td { border: 1px solid #d1d5db; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; font-weight: 600; }
        .cards { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 4px 0 2px; }
        .cards td { border: 1px solid #e5e7eb; background: #f9fafb; text-align: center; padding: 8px 4px; }
        .cards .value { font-size: 18px; font-weight: 700; color: #111827; }
        .cards .label { font-size: 9px; color: #111827; }
        .approval-note { font-size: 9px; color: #111827; margin: 0 0 8px; }
        .approval-note strong { color: #111827; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 8px; color: #111827; background: #e5e7eb; border: 1px solid #d1d5db; }
        .b-green, .b-blue, .b-amber, .b-gray, .b-red { background: #e5e7eb; color: #111827; }
        .muted { color: #111827; }
        .photo { width: 48px; border: 1px solid #e5e7eb; border-radius: 4px; margin: 1px 2px 1px 0; }
        .photo-label { font-size: 7px; color: #111827; }

        /* Pekerjaan + dokumentasi foto housekeeping di PDF (satu tabel) */
        .plan-block { page-break-inside: avoid; margin-bottom: 10px; }
        .task-table td.photo-cell { padding: 3px 4px; vertical-align: middle; }
        .photo-thumbs { border-collapse: separate; border-spacing: 2px; margin: 0; width: auto; }
        .photo-thumbs td { border: 0; padding: 0; }
        .photo-thumb img { width: 50px; height: 50px; border: 1px solid #e5e7eb; border-radius: 3px; display: block; }
        .empty { text-align: center; color: #111827; padding: 8px; }
        .footer { margin-top: 18px; font-size: 8px; color: #111827; text-align: right; }
    </style>
</head>
<body>
    @php
        $scope = $scope ?? 'all';
        $showMaintenance = in_array($scope, ['all', 'maintenance'], true);
        $showHousekeeping = in_array($scope, ['all', 'housekeeping'], true);
        $titleSuffix = match ($scope) {
            'maintenance' => ' — Maintenance',
            'housekeeping' => ' — Housekeeping',
            default => ' Maintenance &amp; Housekeeping',
        };

        $logoPath = public_path('img/ppn.png');
        $logo = is_file($logoPath)
            ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath))
            : null;
    @endphp

    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <h1>Laporan Harian{!! $titleSuffix !!}</h1>
                    <div class="meta">
                        Tanggal: <strong>{{ $report['date']->format('d M Y') }}</strong> &nbsp;|&nbsp;
                        Dicetak: {{ now()->format('d M Y H:i') }}
                    </div>
                </td>
                @if($showHousekeeping && $logo)
                    <td class="header-logo">
                        <img src="{{ $logo }}" alt="PPN">
                    </td>
                @endif
            </tr>
        </table>
    </div>

    @if($showMaintenance)
        {{-- ================= MAINTENANCE ================= --}}
        <h2>1. Work Order Maintenance</h2>
        <table class="cards">
            <tr>
                <td><div class="value">{{ $report['maintenance']['total'] }}</div><div class="label">Total WO</div></td>
                <td><div class="value">{{ $report['maintenance']['completed'] }}</div><div class="label">Selesai</div></td>
                <td><div class="value">{{ $report['maintenance']['in_progress'] }}</div><div class="label">In Progress</div></td>
                <td><div class="value">{{ $report['maintenance']['pending'] }}</div><div class="label">Pending</div></td>
                <td><div class="value">{{ $report['maintenance']['completion_rate'] }}%</div><div class="label">Completion</div></td>
            </tr>
            <tr>
                <td><div class="value">{{ $report['maintenance']['preventive'] }}</div><div class="label">Preventive</div></td>
                <td><div class="value">{{ $report['maintenance']['corrective'] }}</div><div class="label">Corrective</div></td>
                <td colspan="3"><div class="value">{{ $report['maintenance']['checklist_done'] }}/{{ $report['maintenance']['checklist_total'] }}</div><div class="label">Checklist Selesai</div></td>
            </tr>
        </table>

        <table>
            <thead>
                <tr>
                    <th style="width:22px;">#</th>
                    <th>No. WO</th>
                    <th>Equipment</th>
                    <th>Area</th>
                    <th>Klasifikasi</th>
                    <th>Status</th>
                    <th>Penanggung Jawab</th>
                    <th>Target Selesai</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report['maintenance']['work_orders'] as $i => $wo)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $wo->work_order_number }}</td>
                        <td>{{ $wo->equipment?->tag_number ?? '-' }} — {{ $wo->equipment?->equipmentType?->name ?? '-' }}</td>
                        <td>{{ $wo->equipment?->area?->name ?? '-' }}</td>
                        <td>{{ $wo->classification }}</td>
                        <td>
                            @php
                                $woColor = match ($wo->status) {
                                    'Completed' => 'b-green',
                                    'In Progress' => 'b-blue',
                                    'Cancelled' => 'b-red',
                                    default => 'b-gray',
                                };
                            @endphp
                            <span class="badge {{ $woColor }}">{{ $wo->status }}</span>
                        </td>
                        <td>{{ $wo->technicianCoordinator?->name ?? '-' }}</td>
                        <td>{{ $wo->finish_at?->format('d M Y H:i') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty">Tidak ada work order pada tanggal ini.</td></tr>
                @endforelse
            </tbody>
        </table>

        {{-- ================= REQUEST MAINTENANCE ================= --}}
        <h2>{{ $scope === 'maintenance' ? '2' : '3' }}. Maintenance Request (Korektif)</h2>
        <table class="cards">
            <tr>
                <td><div class="value">{{ $report['requests']['maintenance_total'] }}</div><div class="label">Total Request</div></td>
                <td><div class="value">{{ $report['requests']['maintenance_open'] }}</div><div class="label">Open</div></td>
                <td><div class="value">{{ $report['requests']['maintenance_completed'] }}</div><div class="label">Selesai</div></td>
                <td><div class="value">{{ $report['requests']['maintenance_rejected'] }}</div><div class="label">Ditolak</div></td>
            </tr>
        </table>

        <table>
            <thead>
                <tr>
                    <th style="width:22px;">#</th>
                    <th>No. Request</th>
                    <th>Equipment</th>
                    <th>Masalah</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report['requests']['maintenance_list'] as $i => $req)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $req->request_number }}</td>
                        <td>{{ $req->equipment?->tag_number ?? '-' }} — {{ $req->equipment?->equipmentType?->name ?? '-' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($req->description, 80) }}</td>
                        <td>{{ $req->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">Tidak ada request pada tanggal ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    @if($showHousekeeping)
        {{-- ================= HOUSEKEEPING ================= --}}
        {{-- <h2>{{ $scope === 'housekeeping' ? '1' : '2' }}. Housekeeping</h2> --}}
        <h2>Housekeeping</h2>

        @php
            $hk = $report['housekeeping'];
            $plansStarted = $hk['plans_started'] ?? collect();
            $plansActive = $hk['plans_active'] ?? collect();
            $plansCompleted = $hk['plans_completed_today'] ?? collect();
            $tasksToday = $hk['tasks_today'] ?? collect();
            $bySupervisor = $hk['plans_by_supervisor'] ?? collect();

            $overduePlans = $plansActive->filter(fn ($plan) => $plan->is_overdue);
            $sectionNumber = 0;

            // Nama SPV GOS yang sudah mereview & menyetujui plan pada laporan ini.
            $approvers = $plansStarted
                ->merge($plansActive)
                ->merge($plansCompleted)
                ->unique('id')
                ->map(fn ($plan) => $plan->approvedBy?->name)
                ->filter()
                ->unique()
                ->values();
        @endphp

        <div class="approval-note">
            Sudah di review dan disetujui oleh:
            <strong>{{ $approvers->isNotEmpty() ? $approvers->implode(', ') : '—' }}</strong>
            <span>(SPV GOS)</span>
        </div>

        <table class="cards">
            <tr>
                <td><div class="value">{{ $plansActive->count() }}</div><div class="label">Plan Berjalan</div></td>
                <td><div class="value">{{ $plansStarted->count() }}</div><div class="label">Plan Dimulai Hari Ini</div></td>
                <td><div class="value">{{ $plansCompleted->count() }}</div><div class="label">Plan Selesai Hari Ini</div></td>
                <td><div class="value">{{ $tasksToday->where('status', 'Done')->count() }}/{{ $tasksToday->count() }}</div><div class="label">Pekerjaan Selesai</div></td>
                <td><div class="value">{{ $overduePlans->count() }}</div><div class="label">Plan Melewati SLA</div></td>
            </tr>
        </table>

        {{-- Laporan dipisah per Penanggung Jawab (PJ) area. --}}
        @forelse($bySupervisor as $pj)
            <h2 style="font-size:12px; margin-top:14px;">PJ: {{ $pj['supervisor'] }}</h2>

            @if($pj['started']->isNotEmpty())
                <h2 style="font-size:11px; margin-top:10px;">{{ $pj['supervisor'] }} &middot; Plan dimulai hari ini</h2>

                @foreach($pj['started'] as $plan)
                    @include('pdf.partials.housekeeping-plan-detail', ['plan' => $plan])
                @endforeach
            @endif

            @if($pj['active']->isNotEmpty())
                <h2 style="font-size:11px; margin-top:10px;">{{ $pj['supervisor'] }} &middot; Plan masih berjalan</h2>

                @foreach($pj['active'] as $plan)
                    @include('pdf.partials.housekeeping-plan-detail', ['plan' => $plan])
                @endforeach
            @endif

            @if($pj['completed']->isNotEmpty())
                <h2 style="font-size:11px; margin-top:10px;">{{ $pj['supervisor'] }} &middot; Plan selesai hari ini</h2>

                @foreach($pj['completed'] as $plan)
                    @include('pdf.partials.housekeeping-plan-detail', ['plan' => $plan])
                @endforeach
            @endif
        @empty
            <table>
                <tbody>
                    <tr><td class="empty">Tidak ada plan yang sudah disetujui SPV GOS pada tanggal ini.</td></tr>
                </tbody>
            </table>
        @endforelse
    @endif

    <div class="footer">Dibuat otomatis oleh sistem RUMAT. Data housekeeping diambil saat laporan diunduh.</div>
</body>
</html>
