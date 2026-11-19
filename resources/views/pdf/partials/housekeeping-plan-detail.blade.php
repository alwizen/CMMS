@php
    $planDone = $plan->tasks->where('status', 'Done')->count();
    $planTotal = $plan->tasks->count();

    // Kelompokkan checklist per fasilitas agar dokumentasi foto hanya tampil
    // sekali (rowspan), bukan berulang di setiap baris pekerjaan.
    $tasksByFacility = $plan->tasks
        ->filter(fn ($task) => $task->housekeeping_facility_id !== null)
        ->groupBy('housekeeping_facility_id');

    $documentedFacilityIds = $plan->facilities
        ->pluck('housekeeping_facility_id')
        ->filter()
        ->all();

    $groups = $plan->facilities
        ->map(fn ($planFacility) => [
            'facilityName' => $planFacility->facility?->name ?? 'Fasilitas',
            'tasks' => $tasksByFacility->get($planFacility->housekeeping_facility_id, collect()),
            'before' => \App\Support\Report\ImageData::thumbnailUris($planFacility->photo_before, 4),
            'after' => \App\Support\Report\ImageData::thumbnailUris($planFacility->photo_after, 4),
        ]);

    // Checklist yang menunjuk fasilitas tanpa baris dokumentasi.
    $undocumentedGroups = $tasksByFacility
        ->reject(fn ($tasks, $facilityId) => in_array($facilityId, $documentedFacilityIds, true))
        ->map(fn ($tasks) => [
            'facilityName' => $tasks->first()?->facility?->name ?? 'Fasilitas',
            'tasks' => $tasks,
            'before' => [],
            'after' => [],
        ]);

    $groups = $groups->concat($undocumentedGroups)->values();

    // Checklist yang tidak terhubung ke fasilitas mana pun.
    $looseTasks = $plan->tasks
        ->filter(fn ($task) => $task->housekeeping_facility_id === null)
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

@if($planTotal > 0 || $groups->isNotEmpty())
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
            @foreach($groups as $group)
                @php
                    $rows = $group['tasks'];
                    $rowCount = max(1, $rows->count());
                @endphp

                @foreach($rows->isEmpty() ? [null] : $rows as $task)
                    <tr>
                        <td>{{ $task?->name ?? '-' }}</td>
                        <td>{{ $group['facilityName'] }}</td>
                        <td>
                            @if($task)
                                @php
                                    $taskColor = match ($task->status) {
                                        'Done' => 'b-green',
                                        'In Progress' => 'b-amber',
                                        default => 'b-gray',
                                    };
                                @endphp
                                <span class="badge {{ $taskColor }}">{{ $task->status }}</span>
                            @else
                                <span class="muted">-</span>
                            @endif
                        </td>

                        @if($loop->first)
                            <td class="photo-cell" rowspan="{{ $rowCount }}">
                                @if($group['before'] === [])
                                    <span class="muted">-</span>
                                @else
                                    <table class="photo-thumbs">
                                        @foreach(array_chunk($group['before'], 2) as $chunk)
                                            <tr>
                                                @foreach($chunk as $photo)
                                                    <td class="photo-thumb"><img src="{{ $photo }}" alt="before"></td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </table>
                                @endif
                            </td>
                            <td class="photo-cell" rowspan="{{ $rowCount }}">
                                @if($group['after'] === [])
                                    <span class="muted">-</span>
                                @else
                                    <table class="photo-thumbs">
                                        @foreach(array_chunk($group['after'], 2) as $chunk)
                                            <tr>
                                                @foreach($chunk as $photo)
                                                    <td class="photo-thumb"><img src="{{ $photo }}" alt="after"></td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </table>
                                @endif
                            </td>
                        @endif
                    </tr>
                @endforeach
            @endforeach

            @foreach($looseTasks as $task)
                @php
                    $taskColor = match ($task->status) {
                        'Done' => 'b-green',
                        'In Progress' => 'b-amber',
                        default => 'b-gray',
                    };
                @endphp
                <tr>
                    <td>{{ $task->name }}</td>
                    <td>-</td>
                    <td><span class="badge {{ $taskColor }}">{{ $task->status }}</span></td>
                    <td class="photo-cell"><span class="muted">-</span></td>
                    <td class="photo-cell"><span class="muted">-</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
</div>
