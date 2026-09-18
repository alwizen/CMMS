<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6">
        {{-- Legend --}}
        <div class="flex flex-wrap gap-4 p-4 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700">
            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Legend:</span>
            <span class="inline-flex items-center gap-1.5 text-sm">
                <span class="w-3 h-3 rounded-sm" style="background:#2563eb"></span> Work Order (In Progress)
            </span>
            <span class="inline-flex items-center gap-1.5 text-sm">
                <span class="w-3 h-3 rounded-sm" style="background:#16a34a"></span> Work Order (Completed)
            </span>
            <span class="inline-flex items-center gap-1.5 text-sm">
                <span class="w-3 h-3 rounded-sm" style="background:#d97706"></span> Work Order (On Hold)
            </span>
            <span class="inline-flex items-center gap-1.5 text-sm">
                <span class="w-3 h-3 rounded-sm" style="background:#dc2626"></span> Work Order (Cancelled)
            </span>
            <span class="inline-flex items-center gap-1.5 text-sm">
                <span class="w-3 h-3 rounded-sm" style="background:#7c3aed; opacity:0.3"></span> Maintenance Plan (Active)
            </span>
            <span class="inline-flex items-center gap-1.5 text-sm">
                <span class="w-3 h-3 rounded-sm" style="background:#ea580c"></span> Schedule (Delayed)
            </span>
            <span class="inline-flex items-center gap-1.5 text-sm">
                <span class="w-3 h-3 rounded-sm" style="background:#7c3aed"></span> Schedule (Rescheduled)
            </span>
        </div>

        {{-- Calendar --}}
        <div
            id="maintenance-calendar"
            class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-4"
        ></div>

        {{-- Event Detail Modal --}}
        <div
            id="event-modal"
            class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            onclick="if(event.target===this) this.classList.add('hidden')"
        >
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
                <div class="flex justify-between items-start mb-4">
                    <h3 id="modal-title" class="text-lg font-bold text-gray-900 dark:text-white"></h3>
                    <button onclick="document.getElementById('event-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div id="modal-body" class="space-y-3 text-sm text-gray-600 dark:text-gray-300"></div>
            </div>
        </div>
    </div>

    {{-- FullCalendar CSS & JS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.js"></script>

    <script>
        document.addEventListener('livewire:initialized', () => {
            initCalendar();
        });

        function initCalendar() {
            const calendarEl = document.getElementById('maintenance-calendar');
            if (!calendarEl || calendarEl.dataset.initialized) return;
            calendarEl.dataset.initialized = 'true';

            const rawEvents = @json($this->getCalendarEvents());

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
                events: rawEvents,
                eventClick: function(info) {
                    info.jsEvent.preventDefault();
                    showEventModal(info.event);
                },
                eventDidMount: function(info) {
                    info.el.title = info.event.title;
                },
            });

            calendar.render();
        }

        function showEventModal(event) {
            const props = event.extendedProps || {};
            const typeLabels = {
                work_order: 'Work Order',
                plan: 'Maintenance Plan',
                schedule: 'Maintenance Schedule',
            };

            const typeColors = {
                work_order: 'bg-blue-100 text-blue-800',
                plan: 'bg-purple-100 text-purple-800',
                schedule: 'bg-amber-100 text-amber-800',
            };

            let html = `
                <div class="flex items-center gap-2 mb-3">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full ${typeColors[props.type] || 'bg-gray-100 text-gray-800'}">
                        ${typeLabels[props.type] || props.type}
                    </span>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">
                        ${props.status || '-'}
                    </span>
                </div>
                <div><strong>Equipment:</strong> ${props.equipment || '-'}</div>
            `;

            if (event.start) {
                const startStr = event.start.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                html += `<div><strong>Mulai:</strong> ${startStr}</div>`;
            }
            if (event.end) {
                const endStr = event.end.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                html += `<div><strong>Selesai:</strong> ${endStr}</div>`;
            }
            if (props.classification) {
                html += `<div><strong>Klasifikasi:</strong> ${props.classification}</div>`;
            }
            if (props.interval) {
                html += `<div><strong>Interval:</strong> ${props.interval}</div>`;
            }
            if (props.description) {
                html += `<div><strong>Keterangan:</strong> ${props.description}</div>`;
            }

            document.getElementById('modal-title').textContent = event.title;
            document.getElementById('modal-body').innerHTML = html;
            document.getElementById('event-modal').classList.remove('hidden');
        }
    </script>
</x-filament-panels::page>
