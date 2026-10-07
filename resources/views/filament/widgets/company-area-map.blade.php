@php
    $colors = [
        'amber'  => '#f59e0b',
        'blue'   => '#3b82f6',
        'green'  => '#22c55e',
        'red'    => '#ef4444',
        'purple' => '#a855f7',
        'cyan'   => '#06b6d4',
        'orange' => '#f97316',
        'pink'   => '#ec4899',
    ];
@endphp

<x-filament-widgets::widget>
    <x-filament::section heading="Peta Area">
        @assets
            @vite(['resources/js/leaflet.js'])
        @endassets

        <div
            x-data="companyAreaMap({
                companies: @js($this->companies),
                colors: @js($colors),
            })"
            class="company-area-map-widget isolate"
            wire:ignore
        >
            <style>
                /* Keep Leaflet layers below the Filament topbar */
                .company-area-map-widget .leaflet-pane,
                .company-area-map-widget .leaflet-tile-pane,
                .company-area-map-widget .leaflet-overlay-pane,
                .company-area-map-widget .leaflet-marker-pane,
                .company-area-map-widget .leaflet-shadow-pane {
                    z-index: 0 !important;
                }

                .company-area-map-widget .leaflet-tooltip-pane,
                .company-area-map-widget .leaflet-popup-pane {
                    z-index: 1 !important;
                }

                .company-area-map-widget .leaflet-top,
                .company-area-map-widget .leaflet-bottom {
                    z-index: 2 !important;
                }

                .company-area-map-widget .leaflet-control {
                    z-index: 2 !important;
                }
            </style>
            <div
                x-ref="map"
                class="rounded-xl border border-gray-300 dark:border-gray-700 overflow-hidden relative z-0"
                style="height: 500px;"
            ></div>

            {{-- <div class="mt-3 flex flex-wrap items-center gap-4 text-xs text-gray-600 dark:text-gray-300">
                <template x-for="company in companies" :key="company.id">
                    <span class="inline-flex items-center gap-1.5">
                        <span
                            class="inline-block w-3 h-3 rounded-sm border"
                            :style="`background-color: ${colors[company.color]}33; border-color: ${colors[company.color]};`"
                        ></span>
                        <span x-text="company.name"></span>
                    </span>
                </template>

                <span class="inline-flex items-center gap-1.5 ml-auto">
                    <span class="inline-block w-3 h-3 rounded-full border-2 border-white shadow" style="background-color: #3b82f6;"></span>
                    <span>Titik = Area (klik untuk melihat daftar tag_number)</span>
                </span>
            </div> --}}
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
