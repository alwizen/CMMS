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
    <x-filament::section heading="Peta Area Housekeeping">
        @assets
            @vite(['resources/js/leaflet.js'])
        @endassets

        <div
            x-data="housekeepingMap({
                areas: @js($this->areas),
                colors: @js($colors),
            })"
            class="housekeeping-area-map-widget isolate"
            wire:ignore
        >
            <style>
                /* Keep Leaflet layers below the Filament topbar */
                .housekeeping-area-map-widget .leaflet-pane,
                .housekeeping-area-map-widget .leaflet-tile-pane,
                .housekeeping-area-map-widget .leaflet-overlay-pane,
                .housekeeping-area-map-widget .leaflet-marker-pane,
                .housekeeping-area-map-widget .leaflet-shadow-pane {
                    z-index: 0 !important;
                }

                .housekeeping-area-map-widget .leaflet-tooltip-pane,
                .housekeeping-area-map-widget .leaflet-popup-pane {
                    z-index: 1 !important;
                }

                .housekeeping-area-map-widget .leaflet-top,
                .housekeeping-area-map-widget .leaflet-bottom {
                    z-index: 2 !important;
                }

                .housekeeping-area-map-widget .leaflet-control {
                    z-index: 2 !important;
                }

                .hk-map {
                    height: 500px;
                    border-radius: 0.75rem;
                    border: 1px solid rgb(209 213 219);
                    overflow: hidden;
                    position: relative;
                    z-index: 0;
                }

                .dark .hk-map {
                    border-color: rgb(55 65 81);
                }

                .hk-area-list {
                    margin-top: 1rem;
                    display: grid;
                    grid-template-columns: repeat(1, minmax(0, 1fr));
                    gap: 0.75rem;
                }

                @media (min-width: 640px) {
                    .hk-area-list { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                }

                @media (min-width: 1024px) {
                    .hk-area-list { grid-template-columns: repeat(3, minmax(0, 1fr)); }
                }

                .hk-card {
                    border: 1px solid rgb(229 231 235);
                    border-radius: 0.75rem;
                    padding: 0.75rem;
                    background-color: #fff;
                }

                .dark .hk-card {
                    border-color: rgb(55 65 81);
                    background-color: rgb(31 41 55);
                }

                .hk-card__head {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 0.5rem;
                }

                .hk-card__title {
                    font-weight: 600;
                    font-size: 0.875rem;
                    color: rgb(17 24 39);
                }

                .dark .hk-card__title { color: rgb(243 244 246); }

                .hk-badge {
                    display: inline-flex;
                    align-items: center;
                    border-radius: 9999px;
                    padding: 0.125rem 0.5rem;
                    font-size: 0.75rem;
                    font-weight: 500;
                    color: #fff;
                    white-space: nowrap;
                }

                .hk-card__meta {
                    margin-top: 0.25rem;
                    font-size: 0.75rem;
                    color: rgb(107 114 128);
                }

                .hk-card__facilities {
                    margin: 0.5rem 0 0;
                    padding: 0;
                    list-style: none;
                    font-size: 0.75rem;
                    color: rgb(55 65 81);
                    max-height: 9rem;
                    overflow-y: auto;
                }

                .dark .hk-card__facilities { color: rgb(209 213 219); }

                .hk-card__facilities li {
                    display: flex;
                    align-items: flex-start;
                    gap: 0.35rem;
                    padding: 0.1rem 0;
                }

                .hk-card__empty { color: rgb(156 163 175); }

                .hk-dot { color: rgb(156 163 175); }
                .hk-muted { color: rgb(156 163 175); }
            </style>
            <div x-ref="map" class="hk-map"></div>

            <div class="hk-area-list">
                <template x-for="area in areas" :key="area.id">
                    <div class="hk-card">
                        <div class="hk-card__head">
                            <div class="hk-card__title" x-text="area.name"></div>
                            <span
                                class="hk-badge"
                                :style="`background-color: ${colors[area.color] ?? '#f59e0b'}`"
                                x-text="`${area.facilities_count} fasilitas`"
                            ></span>
                        </div>
                        <div class="hk-card__meta">
                            <span x-text="area.company || '-'"></span>
                            &middot;
                            <span x-text="area.area_size ? area.area_size.toLocaleString('id-ID') + ' m²' : '-'"></span>
                        </div>
                        <ul class="hk-card__facilities">
                            <template x-for="facility in area.facilities" :key="facility.id">
                                <li>
                                    <span class="hk-dot">&bull;</span>
                                    <span>
                                        <span x-text="facility.name"></span>
                                        <span class="hk-muted" x-text="facility.code ? `(${facility.code})` : ''"></span>
                                    </span>
                                </li>
                            </template>
                            <li x-show="!area.facilities || area.facilities.length === 0" class="hk-card__empty">
                                Belum ada fasilitas
                            </li>
                        </ul>
                    </div>
                </template>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
