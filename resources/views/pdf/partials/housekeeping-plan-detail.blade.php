@php
    $planDone = $plan->tasks->where('status', 'Done')->count();
    $planTotal = $plan->tasks->count();

    $tasks = $plan->tasks
        ->sortBy(fn ($task) => sprintf(
            '%s|%010d',
            (string) $task->facility?->name,
            (int) $task->id,
        ))
        ->values();
@endphp

<div class="plan-block">
<table style="margin-top:6px;">
    <thead>
        <tr>
            <th colspan="2">{{ $plan->plan_number }}</th>
            <th colspan="2">{{ $plan->area?->name ?? '-' }}</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td colspan="2">PJ: {{ $plan->area?->supervisor?->name ?? '-' }}</td>
            <td colspan="2">
                Checklist: {{ $planDone }}/{{ $planTotal }} ({{ $plan->progress_percent }}%)
                &nbsp;|&nbsp; SLA {{ $plan->area?->sla_days ?? 1 }} hari
            </td>
            <td>{{ $plan->status }}</td>
        </tr>
        <tr>
            <td colspan="2">Tanggal Plan: {{ $plan->plan_date?->format('d M Y') ?? '-' }}</td>
            <td colspan="2">Batas Waktu: {{ $plan->due_date?->format('d M Y') ?? '-' }}</td>
            <td>{{ $plan->sla_status }}</td>
        </tr>
    </tbody>
</table>

@if($planTotal > 0)
    <table class="task-table">
        <thead>
            <tr>
                <th style="width:30%;">Pekerjaan</th>
                <th style="width:16%;">Fasilitas</th>
                <th style="width:10%;">Status</th>
                <th style="width:22%;">Foto Before</th>
                <th style="width:22%;">Foto After</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tasks as $task)
                @php
                    $taskColor = match ($task->status) {
                        'Done' => 'b-green',
                        'In Progress' => 'b-amber',
                        default => 'b-gray',
                    };
                    $before = \App\Support\Report\ImageData::thumbnailUris($task->photo_before, 4);
                    $after = \App\Support\Report\ImageData::thumbnailUris($task->photo_after, 4);
                @endphp
                <tr>
                    <td>{{ $task->name }}</td>
                    <td>{{ $task->facility?->name ?? '-' }}</td>
                    <td><span class="badge {{ $taskColor }}">{{ $task->status }}</span></td>
                    <td class="photo-cell">
                        @if($before === [])
                            <span class="muted">-</span>
                        @else
                            <table class="photo-thumbs">
                                @foreach(array_chunk($before, 2) as $chunk)
                                    <tr>
                                        @foreach($chunk as $photo)
                                            <td class="photo-thumb"><img src="{{ $photo }}" alt="before"></td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </table>
                        @endif
                    </td>
                    <td class="photo-cell">
                        @if($after === [])
                            <span class="muted">-</span>
                        @else
                            <table class="photo-thumbs">
                                @foreach(array_chunk($after, 2) as $chunk)
                                    <tr>
                                        @foreach($chunk as $photo)
                                            <td class="photo-thumb"><img src="{{ $photo }}" alt="after"></td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </table>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
</div>
