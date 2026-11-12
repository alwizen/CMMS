<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @assets
        @vite(['resources/js/leaflet.js'])
    @endassets

    <div
        x-data="leafletMap({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
            mode: @js($getMode()),
            defaultLat: @js($getDefaultLat()),
            defaultLng: @js($getDefaultLng()),
            defaultZoom: @js($getDefaultZoom()),
        })"
        class="leaflet-map-field isolate"
        wire:ignore
    >
        <style>
            /* Keep Leaflet panes below Filament modals so they don't overlap form/modal content */
            .leaflet-map-field .leaflet-pane,
            .leaflet-map-field .leaflet-tile-pane,
            .leaflet-map-field .leaflet-overlay-pane,
            .leaflet-map-field .leaflet-marker-pane,
            .leaflet-map-field .leaflet-shadow-pane {
                z-index: 0 !important;
            }

            .leaflet-map-field .leaflet-tooltip-pane,
            .leaflet-map-field .leaflet-popup-pane {
                z-index: 1 !important;
            }

            .leaflet-map-field .leaflet-top,
            .leaflet-map-field .leaflet-bottom,
            .leaflet-map-field .leaflet-control {
                z-index: 2 !important;
            }
        </style>

        <div
            x-ref="map"
            class="rounded-xl border border-gray-300 dark:border-gray-700 overflow-hidden relative z-0"
            style="height: {{ $getHeight() }}px;"
        ></div>

        <p class="fi-fo-field-wrp-helper-text mt-2 text-xs text-gray-500 dark:text-gray-400" x-text="hint"></p>
    </div>
</x-dynamic-component>
