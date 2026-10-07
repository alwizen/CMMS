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
        wire:ignore
    >
        <div
            x-ref="map"
            class="rounded-xl border border-gray-300 dark:border-gray-700 overflow-hidden relative z-0"
            style="height: {{ $getHeight() }}px;"
        ></div>

        <p class="fi-fo-field-wrp-helper-text mt-2 text-xs text-gray-500 dark:text-gray-400" x-text="hint"></p>
    </div>
</x-dynamic-component>
