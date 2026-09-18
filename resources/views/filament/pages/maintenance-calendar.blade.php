<x-filament-panels::page>
    <div
        x-data="{ showModal: false, event: {} }"
        @keydown.escape.window="showModal = false"
    >
        {{-- Legend --}}
        <div class="fi-wi-widget-card mb-6">
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 px-4 py-3">
                <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">Legend:</span>

                <span class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-300">
                    <span class="w-2.5 h-2.5 rounded-sm bg-primary-500"></span> WO Aktif
                </span>
                <span class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-300">
                    <span class="w-2.5 h-2.5 rounded-sm bg-success-500"></span> Selesai
                </span>
                <span class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-300">
                    <span class="w-2.5 h-2.5 rounded-sm bg-warning-500"></span> Tertunda
                </span>
                <span class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-300">
                    <span class="w-2.5 h-2.5 rounded-sm bg-danger-500"></span> Dibatalkan
                </span>
                <span class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-300">
                    <span class="w-2.5 h-2.5 rounded-sm bg-primary-500 opacity-40"></span> Plan Aktif
                </span>
                <span class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-300">
                    <span class="w-2.5 h-2.5 rounded-sm bg-info-500"></span> Rescheduled
                </span>
            </div>
        </div>

        {{-- Calendar Card --}}
        <div class="fi-wi-widget-card">
            <div id="maintenance-calendar" class="fc fc-media-screen fc-direction-ltr fc-theme-standard p-4"></div>
        </div>

        {{-- Filament-style Modal --}}
        <div
            x-show="showModal"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
            @click.self="showModal = false"
        >
            <div
                x-show="showModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                @click.stop
                class="fi-modal-window w-full max-w-lg rounded-xl bg-white dark:bg-gray-900 shadow-2xl ring-1 ring-black/5"
            >
                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white" x-text="event.title"></h2>
                    <button
                        @click="showModal = false"
                        class="flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                    >
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>

                {{-- Body --}}
                <div class="px-6 py-4 space-y-4">
                    {{-- Type & Status Badges --}}
                    <div class="flex items-center gap-2 flex-wrap">
                        <span
                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg"
                            :class="{
                                'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-400': event.type === 'work_order',
                                'bg-purple-50 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400': event.type === 'plan',
                                'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400': event.type === 'schedule',
                            }"
                            x-text="event.typeLabel"
                        ></span>
                        <span
                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg"
                            :class="{
                                'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400': event.status === 'Completed' || event.status === 'Active',
                                'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-400': event.status === 'In Progress',
                                'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400': event.status === 'On Hold' || event.status === 'Delayed' || event.status === 'Scheduled',
                                'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400': event.status === 'Cancelled',
                                'bg-gray-50 text-gray-700 dark:bg-gray-500/10 dark:text-gray-400': !['Completed','Active','In Progress','On Hold','Delayed','Scheduled','Cancelled'].includes(event.status),
                            }"
                            x-text="event.status"
                        ></span>
                    </div>

                    {{-- Detail Info --}}
                    <div class="space-y-3">
                        <div class="flex items-start gap-3">
                            <x-heroicon-m-cube class="w-5 h-5 mt-0.5 text-gray-400 dark:text-gray-500 shrink-0" />
                            <div>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Equipment</p>
                                <p class="text-sm text-gray-950 dark:text-white" x-text="event.equipment || '-'"></p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <x-heroicon-m-calendar class="w-5 h-5 mt-0.5 text-gray-400 dark:text-gray-500 shrink-0" />
                            <div>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Tanggal Mulai</p>
                                <p class="text-sm text-gray-950 dark:text-white" x-text="event.startFormatted || '-'"></p>
                            </div>
                        </div>

                        <template x-if="event.endFormatted">
                            <div class="flex items-start gap-3">
                                <x-heroicon-m-calendar-days class="w-5 h-5 mt-0.5 text-gray-400 dark:text-gray-500 shrink-0" />
                                <div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Tanggal Selesai</p>
                                    <p class="text-sm text-gray-950 dark:text-white" x-text="event.endFormatted"></p>
                                </div>
                            </div>
                        </template>

                        <template x-if="event.classification">
                            <div class="flex items-start gap-3">
                                <x-heroicon-m-tag class="w-5 h-5 mt-0.5 text-gray-400 dark:text-gray-500 shrink-0" />
                                <div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Klasifikasi</p>
                                    <p class="text-sm text-gray-950 dark:text-white" x-text="event.classification"></p>
                                </div>
                            </div>
                        </template>

                        <template x-if="event.interval">
                            <div class="flex items-start gap-3">
                                <x-heroicon-m-arrow-path class="w-5 h-5 mt-0.5 text-gray-400 dark:text-gray-500 shrink-0" />
                                <div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Interval</p>
                                    <p class="text-sm text-gray-950 dark:text-white" x-text="event.interval"></p>
                                </div>
                            </div>
                        </template>

                        <template x-if="event.description">
                            <div class="flex items-start gap-3">
                                <x-heroicon-m-document-text class="w-5 h-5 mt-0.5 text-gray-400 dark:text-gray-500 shrink-0" />
                                <div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Keterangan</p>
                                    <p class="text-sm text-gray-950 dark:text-white" x-text="event.description"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <button
                        @click="showModal = false"
                        class="fi-btn fi-btn-size-sm inline-flex items-center justify-center gap-1 rounded-lg bg-white dark:bg-white/5 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 ring-1 ring-gray-300 dark:ring-white/10 hover:bg-gray-50 dark:hover:bg-white/10 transition"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- FullCalendar CSS & JS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.js"></script>

    <style>
        /* Override FullCalendar to match Filament design */
        .fc {
            font-family: inherit !important;
        }
        .fc .fc-toolbar-title {
            font-size: 1.125rem !important;
            font-weight: 600 !important;
        }
        .fc .fc-button {
            background: transparent !important;
            border: 1px solid var(--color-gray-300) !important;
            color: var(--color-gray-700) !important;
            border-radius: 0.5rem !important;
            padding: 0.375rem 0.75rem !important;
            font-size: 0.8125rem !important;
            font-weight: 500 !important;
            box-shadow: none !important;
            transition: all 0.15s ease !important;
        }
        .fc .fc-button:hover {
            background: var(--color-gray-50) !important;
            border-color: var(--color-gray-400) !important;
        }
        .fc .fc-button-active {
            background: var(--color-primary-500) !important;
            border-color: var(--color-primary-500) !important;
            color: white !important;
        }
        .fc .fc-button-primary:not(:disabled).fc-button-active,
        .fc .fc-button-primary:not(:disabled):active {
            background: var(--color-primary-600) !important;
            border-color: var(--color-primary-600) !important;
        }
        .fc th {
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            color: var(--color-gray-500) !important;
            padding: 0.75rem 0.5rem !important;
        }
        .fc td, .fc th {
            border-color: var(--color-gray-200) !important;
        }
        .fc .fc-daygrid-day {
            padding: 2px !important;
        }
        .fc .fc-daygrid-day:hover {
            background: var(--color-gray-50) !important;
        }
        .fc .fc-daygrid-day-number {
            font-size: 0.8125rem !important;
            padding: 0.375rem 0.5rem !important;
            color: var(--color-gray-700) !important;
        }
        .fc .fc-day-today {
            background: var(--color-primary-50) !important;
        }
        .fc .fc-day-today .fc-daygrid-day-number {
            background: var(--color-primary-500) !important;
            color: white !important;
            border-radius: 9999px !important;
            width: 1.75rem !important;
            height: 1.75rem !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .fc .fc-event {
            border: none !important;
            border-radius: 0.375rem !important;
            padding: 1px 6px !important;
            font-size: 0.75rem !important;
            font-weight: 500 !important;
            cursor: pointer !important;
            transition: opacity 0.15s ease !important;
        }
        .fc .fc-event:hover {
            opacity: 0.85 !important;
        }
        .fc .fc-col-header-cell {
            padding: 0.5rem 0 !important;
        }
        .fc .fc-scrollgrid {
            border-radius: 0.75rem !important;
            overflow: hidden !important;
            border: 1px solid var(--color-gray-200) !important;
        }
        .fc .fc-scrollgrid td, .fc .fc-scrollgrid th {
            border-color: var(--color-gray-200) !important;
        }
        .fc .fc-list-event:hover td {
            background: var(--color-gray-50) !important;
        }
        .fc .fc-list-event-dot {
            border-width: 0 !important;
            width: 8px !important;
            height: 8px !important;
            border-radius: 2px !important;
        }
        [x-cloak] { display: none !important; }
    </style>

    <script>
        document.addEventListener('livewire:initialized', () => {
            initCalendar();
        });

        function initCalendar() {
            const calendarEl = document.getElementById('maintenance-calendar');
            if (!calendarEl || calendarEl.dataset.initialized) return;
            calendarEl.dataset.initialized = 'true';

            const rawEvents = @json($this->getCalendarEvents());
            const app = document.querySelector('[x-data]').__x;

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
                    openModal(info.event);
                },
            });

            calendar.render();
        }

        function openModal(event) {
            const props = event.extendedProps || {};
            const typeLabels = {
                work_order: 'Work Order',
                plan: 'Maintenance Plan',
                schedule: 'Maintenance Schedule',
            };

            const formatDate = (date) => {
                if (!date) return null;
                return date.toLocaleDateString('id-ID', {
                    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
                });
            };

            const Alpine = window.Alpine;
            const root = document.querySelector('[x-data]');
            const data = Alpine.$data(root);

            data.event = {
                title: event.title,
                type: props.type,
                typeLabel: typeLabels[props.type] || props.type,
                status: props.status || '-',
                equipment: props.equipment || '-',
                startFormatted: formatDate(event.start),
                endFormatted: formatDate(event.end),
                classification: props.classification || null,
                interval: props.interval || null,
                description: props.description || null,
            };
            data.showModal = true;
        }
    </script>
</x-filament-panels::page>
