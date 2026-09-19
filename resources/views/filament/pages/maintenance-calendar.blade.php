<x-filament-panels::page>
    {{-- Calendar Card --}}
    <div class="fi-wi-widget-card">
        <div id="maintenance-calendar" class="fc fc-media-screen fc-direction-ltr fc-theme-standard p-4"></div>

        {{-- Legend --}}
        {{-- <div class="px-6 pt-4 pb-3 border-t border-gray-200 dark:border-gray-700">
            <p class="text-xs font-medium text-gray-400 dark:text-gray-500 mb-2">Legenda</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                <span class="inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <span class="w-2.5 h-2.5 rounded-sm shrink-0" style="background:#2563eb"></span> WO Aktif
                </span>
                <span class="inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <span class="w-2.5 h-2.5 rounded-sm shrink-0" style="background:#16a34a"></span> Selesai
                </span>
                <span class="inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <span class="w-2.5 h-2.5 rounded-sm shrink-0" style="background:#d97706"></span> Tertunda
                </span>
                <span class="inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <span class="w-2.5 h-2.5 rounded-sm shrink-0" style="background:#dc2626"></span> Dibatalkan
                </span>
                <span class="inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <span class="w-2.5 h-2.5 rounded-sm shrink-0" style="background:#7c3aed; opacity:0.35"></span> Plan Aktif
                </span>
                <span class="inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <span class="w-2.5 h-2.5 rounded-sm shrink-0" style="background:#7c3aed"></span> Rescheduled
                </span>
            </div>
        </div>
    </div> --}}

    {{-- FullCalendar CSS & JS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.js"></script>

    <style>
        .fc { font-family: inherit !important; }
        .fc .fc-toolbar-title { font-size: 1.125rem !important; font-weight: 600 !important; }
        .fc .fc-button {
            background: transparent !important;
            border: 1px solid var(--color-gray-300) !important;
            color: var(--color-gray-700) !important;
            border-radius: 0.5rem !important;
            padding: 0.375rem 0.875rem !important;
            font-size: 0.8125rem !important;
            font-weight: 500 !important;
            box-shadow: none !important;
            transition: all 0.15s ease !important;
        }
        .fc .fc-button:hover { background: var(--color-gray-50) !important; border-color: var(--color-gray-400) !important; }
        .fc .fc-button-active { background: var(--color-primary-500) !important; border-color: var(--color-primary-500) !important; color: white !important; }
        .fc .fc-button-primary:not(:disabled).fc-button-active,
        .fc .fc-button-primary:not(:disabled):active { background: var(--color-primary-600) !important; border-color: var(--color-primary-600) !important; }
        .fc .fc-toolbar { gap: 0.75rem !important; }
        .fc .fc-toolbar-chunk { gap: 0.5rem !important; display: flex !important; align-items: center !important; }
        .fc .fc-button-group .fc-button { margin: 0 !important; }
        .fc .fc-button-group .fc-button:not(:first-child) { margin-left: 0.375rem !important; }
        .dark .fc .fc-button { border-color: rgba(255,255,255,0.1) !important; color: #d1d5db !important; background: rgba(255,255,255,0.05) !important; }
        .dark .fc .fc-button:hover { background: rgba(255,255,255,0.1) !important; border-color: rgba(255,255,255,0.2) !important; }
        .fc th { font-size: 0.75rem !important; font-weight: 600 !important; text-transform: uppercase !important; letter-spacing: 0.05em !important; color: var(--color-gray-500) !important; padding: 0.75rem 0.5rem !important; }
        .fc td, .fc th { border-color: var(--color-gray-200) !important; }
        .fc .fc-daygrid-day { padding: 2px !important; }
        .fc .fc-daygrid-day:hover { background: var(--color-gray-50) !important; }
        .fc .fc-daygrid-day-number { font-size: 0.8125rem !important; padding: 0.375rem 0.5rem !important; color: var(--color-gray-700) !important; }
        .fc .fc-day-today { background: var(--color-primary-50) !important; }
        .fc .fc-day-today .fc-daygrid-day-number {
            background: var(--color-primary-500) !important; color: white !important;
            border-radius: 9999px !important; width: 1.75rem !important; height: 1.75rem !important;
            display: flex !important; align-items: center !important; justify-content: center !important;
        }
        .fc .fc-event { border: none !important; border-radius: 0.375rem !important; padding: 1px 6px !important; font-size: 0.75rem !important; font-weight: 500 !important; cursor: pointer !important; transition: opacity 0.15s ease !important; }
        .fc .fc-event:hover { opacity: 0.85 !important; }
        .fc .fc-scrollgrid { border-radius: 0.75rem !important; overflow: hidden !important; border: 1px solid var(--color-gray-200) !important; }
        .fc .fc-scrollgrid td, .fc .fc-scrollgrid th { border-color: var(--color-gray-200) !important; }
        .fc .fc-list-event:hover td { background: var(--color-gray-50) !important; }
        .fc .fc-list-event-dot { border-width: 0 !important; width: 8px !important; height: 8px !important; border-radius: 2px !important; }

        /* Modal detail info rows */
        .modal-row { display: flex; align-items: flex-start; gap: 0.75rem; }
        .modal-row-icon { flex-shrink: 0; width: 1.25rem; height: 1.25rem; margin-top: 0.125rem; color: #9ca3af; }
        .modal-row-label { font-size: 0.75rem; font-weight: 500; color: #6b7280; }
        .modal-row-value { font-size: 0.875rem; color: #111827; }

        .dark .modal-row-icon { color: #6b7280; }
        .dark .modal-row-label { color: #9ca3af; }
        .dark .modal-row-value { color: #f3f4f6; }

        /* Badge styles */
        .badge { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.625rem; font-size: 0.75rem; font-weight: 600; border-radius: 0.5rem; }
        .badge-type-wo { background: #eff6ff; color: #1d4ed8; }
        .badge-type-plan { background: #f5f3ff; color: #7c3aed; }
        .badge-type-schedule { background: #fffbeb; color: #d97706; }
        .badge-status-completed, .badge-status-active { background: #f0fdf4; color: #15803d; }
        .badge-status-in-progress { background: #eff6ff; color: #1d4ed8; }
        .badge-status-on-hold, .badge-status-delayed, .badge-status-scheduled { background: #fffbeb; color: #d97706; }
        .badge-status-cancelled { background: #fef2f2; color: #dc2626; }
        .badge-status-default { background: #f9fafb; color: #6b7280; }
        .dark .badge-type-wo { background: rgba(59,130,246,0.1); color: #60a5fa; }
        .dark .badge-type-plan { background: rgba(139,92,246,0.1); color: #a78bfa; }
        .dark .badge-type-schedule { background: rgba(245,158,11,0.1); color: #fbbf24; }
        .dark .badge-status-completed, .dark .badge-status-active { background: rgba(34,197,94,0.1); color: #4ade80; }
        .dark .badge-status-in-progress { background: rgba(59,130,246,0.1); color: #60a5fa; }
        .dark .badge-status-on-hold, .dark .badge-status-delayed, .dark .badge-status-scheduled { background: rgba(245,158,11,0.1); color: #fbbf24; }
        .dark .badge-status-cancelled { background: rgba(239,68,68,0.1); color: #f87171; }
        .dark .badge-status-default { background: rgba(107,114,128,0.1); color: #9ca3af; }

        /* Modal overlay */
        .evt-overlay { display:none; position:fixed; inset:0; z-index:99999; align-items:center; justify-content:center; padding:1rem; background:rgba(0,0,0,0.5); backdrop-filter:blur(4px); }

        /* Modal panel */
        .evt-panel { width:100%; max-width:32rem; background:#fff; border-radius:0.75rem; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); transform:scale(0.95); opacity:0; transition:all 0.2s ease; }
        .dark .evt-panel { background:#1f2937; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5); }

        /* Panel header */
        .evt-panel-header { display:flex; align-items:center; justify-content:space-between; padding:1.25rem 1.5rem; border-bottom:1px solid #e5e7eb; }
        .dark .evt-panel-header { border-color:#374151; }
        .evt-panel-title { font-size:1.125rem; font-weight:600; color:#111827; margin:0; }
        .dark .evt-panel-title { color:#f9fafb; }

        /* Close button */
        .evt-close-btn { display:flex; align-items:center; justify-content:center; width:2rem; height:2rem; border-radius:0.5rem; color:#9ca3af; border:none; background:none; cursor:pointer; transition:all 0.15s ease; }
        .evt-close-btn:hover { background:#f3f4f6; color:#6b7280; }
        .dark .evt-close-btn:hover { background:#374151; color:#d1d5db; }

        /* Panel body */
        .evt-panel-body { padding:1.5rem; max-height:60vh; overflow-y:auto; }

        /* Panel footer */
        .evt-panel-footer { display:flex; align-items:center; justify-content:flex-end; gap:0.75rem; padding:1rem 1.5rem; border-top:1px solid #e5e7eb; }
        .dark .evt-panel-footer { border-color:#374151; }

        /* Tutup button */
        .evt-btn-tutup { padding:0.5rem 1.25rem; font-size:0.875rem; font-weight:500; border-radius:0.5rem; border:1px solid #d1d5db; background:#fff; color:#374151; cursor:pointer; transition:all 0.15s ease; }
        .evt-btn-tutup:hover { background:#f9fafb; border-color:#9ca3af; }
        .dark .evt-btn-tutup { background:rgba(255,255,255,0.05); border-color:rgba(255,255,255,0.1); color:#e5e7eb; }
        .dark .evt-btn-tutup:hover { background:rgba(255,255,255,0.1); }
    </style>

    <script>
        const calendarEvents = @json($this->getCalendarEvents());

        (function() {
            const overlay = document.createElement('div');
            overlay.id = 'event-modal';
            overlay.className = 'evt-overlay';
            overlay.innerHTML = `
                <div id="modal-panel" class="evt-panel">
                    <div class="evt-panel-header">
                        <h2 id="modal-title" class="evt-panel-title"></h2>
                        <button onclick="closeModal()" class="evt-close-btn">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div id="modal-body" class="evt-panel-body"></div>
                    <div class="evt-panel-footer">
                        <button onclick="closeModal()" class="evt-btn-tutup">Tutup</button>
                    </div>
                </div>
            `;
            document.body.appendChild(overlay);

            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) closeModal();
            });
        })();

        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initCalendar, 300);
        });

        function initCalendar() {
            const calendarEl = document.getElementById('maintenance-calendar');
            if (!calendarEl || calendarEl.dataset.initialized) return;
            calendarEl.dataset.initialized = 'true';

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'id',
                firstDay: 1,
                height: 'auto',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
                },
                buttonText: {
                    today: 'Hari Ini',
                    month: 'Bulan',
                    week: 'Minggu',
                    day: 'Hari',
                    list: 'Daftar',
                },
                events: calendarEvents,
                eventClick: function(info) {
                    info.jsEvent.preventDefault();
                    openEventModal(info.event);
                },
            });

            calendar.render();
        }

        function openEventModal(fcEvent) {
            const props = fcEvent.extendedProps || {};

            const typeLabels = { work_order: 'Work Order', plan: 'Maintenance Plan', schedule: 'Maintenance Schedule' };
            const typeClass = { work_order: 'badge-type-wo', plan: 'badge-type-plan', schedule: 'badge-type-schedule' };
            const statusClass = {
                'Completed': 'badge-status-completed', 'Active': 'badge-status-active',
                'In Progress': 'badge-status-in-progress',
                'On Hold': 'badge-status-on-hold', 'Delayed': 'badge-status-delayed', 'Scheduled': 'badge-status-scheduled',
                'Cancelled': 'badge-status-cancelled',
            };

            const formatDate = (d) => d ? d.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) : null;

            document.getElementById('modal-title').textContent = fcEvent.title;

            let html = '';

            html += '<div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:0.75rem;">';
            html += `<span class="badge ${typeClass[props.type] || 'badge-status-default'}">${typeLabels[props.type] || props.type || '-'}</span>`;
            html += `<span class="badge ${statusClass[props.status] || 'badge-status-default'}">${props.status || '-'}</span>`;
            html += '</div>';

            const row = (icon, label, value) => {
                if (!value) return '';
                return `<div class="modal-row">
                    <svg class="modal-row-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${icon}"/></svg>
                    <div><p class="modal-row-label">${label}</p><p class="modal-row-value">${value}</p></div>
                </div>`;
            };

            const icons = {
                cube: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                calendar: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                calendarDays: 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
                mapPin: 'M15 10.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z',
                tag: 'M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z',
                arrowPath: 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182',
                user: 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z',
                clipboard: 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z',
                clock: 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
                doc: 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
                chat: 'M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 011.037-.443 48.282 48.282 0 005.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z',
            };

            html += row(icons.cube, 'Equipment', props.equipment || '-');
            html += row(icons.mapPin, 'Area', props.area || null);
            html += row(icons.calendar, 'Tanggal Mulai', formatDate(fcEvent.start));
            html += row(icons.calendarDays, 'Tanggal Selesai', formatDate(fcEvent.end));
            html += row(icons.tag, 'Klasifikasi', props.classification || null);
            html += row(icons.arrowPath, 'Interval', props.interval || null);

            if (props.type === 'work_order') {
                html += row(icons.user, 'Diterbitkan Oleh', props.issuedBy || null);
                html += row(icons.chat, 'Catatan', props.note || null);
            }
            if (props.type === 'schedule') {
                html += row(icons.clipboard, 'Maintenance Plan', props.planName || null);
                html += row(icons.clock, 'Di-reschedule dari', props.rescheduledFrom || null);
            }

            html += row(icons.doc, 'Keterangan', props.description || null);

            document.getElementById('modal-body').innerHTML = html;

            const modal = document.getElementById('event-modal');
            const panel = document.getElementById('modal-panel');
            modal.style.display = 'flex';
            requestAnimationFrame(() => {
                panel.style.transform = 'scale(1)';
                panel.style.opacity = '1';
            });
        }

        function closeModal() {
            const modal = document.getElementById('event-modal');
            const panel = document.getElementById('modal-panel');
            panel.style.transform = 'scale(0.95)';
            panel.style.opacity = '0';
            setTimeout(() => { modal.style.display = 'none'; }, 200);
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
    </script>
</x-filament-panels::page>
