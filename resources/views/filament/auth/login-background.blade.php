@php
    $bgUrl = asset('img/ft.webp');
@endphp

<style>
    .fi-simple-layout {
        background-image: url('{{ $bgUrl }}');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-attachment: fixed;
        position: relative;
        min-height: 100vh;
    }

    .fi-simple-layout::before {
        content: '';
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.35);
        pointer-events: none;
        z-index: 0;
    }

    .fi-simple-layout .fi-simple-main-ctn,
    .fi-simple-layout .fi-simple-header {
        position: relative;
        z-index: 1;
    }
</style>