@php
    $tasks = $record->tasks ?? collect();
@endphp

<div style="display:flex; flex-direction:column; gap:0.75rem; font-size:0.875rem;">
    <div style="font-weight:600; color:#111827;">
        Checklist untuk: {{ $record->name }}
    </div>

    @if ($tasks->isEmpty())
        <div style="border:1px dashed #d1d5db; border-radius:0.5rem; padding:1.25rem; text-align:center; font-size:0.8rem; color:#6b7280;">
            Belum ada checklist untuk fasilitas ini.
        </div>
    @else
        <div style="border:1px solid #e5e7eb; border-radius:0.5rem; overflow:hidden;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f9fafb; text-align:left;">
                        <th style="padding:0.5rem 0.75rem; font-size:0.7rem; font-weight:600; color:#374151;">Nama</th>
                        <th style="padding:0.5rem 0.75rem; font-size:0.7rem; font-weight:600; color:#374151;">Description</th>
                        <th style="padding:0.5rem 0.75rem; font-size:0.7rem; font-weight:600; color:#374151;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tasks as $task)
                        <tr style="border-top:1px solid #f3f4f6;">
                            <td style="padding:0.5rem 0.75rem; font-weight:500; color:#111827;">{{ $task->name }}</td>
                            <td style="padding:0.5rem 0.75rem; color:#6b7280;">{{ $task->description ?: '-' }}</td>
                            <td style="padding:0.5rem 0.75rem;">
                                @if ($task->is_active)
                                    <span style="display:inline-block; padding:0.1rem 0.5rem; border-radius:9999px; font-size:0.7rem; background:#dcfce7; color:#166534;">Aktif</span>
                                @else
                                    <span style="display:inline-block; padding:0.1rem 0.5rem; border-radius:9999px; font-size:0.7rem; background:#f3f4f6; color:#6b7280;">Nonaktif</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
