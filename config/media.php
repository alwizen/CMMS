<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kompresi Foto
    |--------------------------------------------------------------------------
    |
    | Foto unggahan (maintenance & housekeeping) otomatis dikonversi ke WebP
    | agar hemat penyimpanan. Atur kualitas (0-100) dan lebar maksimum
    | (piksel) di sini. Nilai lebih kecil = file lebih kecil.
    |
    */

    'webp_quality' => (int) env('IMAGE_WEBP_QUALITY', 75),

    'max_width' => (int) env('IMAGE_MAX_WIDTH', 1920),

    /*
    |--------------------------------------------------------------------------
    | Disk Penyimpanan Media
    |--------------------------------------------------------------------------
    |
    | "local" (disk server) atau "r2" (Cloudflare R2, bucket privat).
    | Untuk produksi isi nilai R2 di bawah lalu ubah MEDIA_DISK=r2.
    |
    */

    'disk' => env('MEDIA_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Masa Berlaku URL Media (menit)
    |--------------------------------------------------------------------------
    |
    | Untuk disk privat (R2) API mengirim presigned URL. Nilai ini menentukan
    | berapa lama URL berlaku. URL di-cache agar stabil bagi cache gambar app.
    |
    */

    'url_ttl_minutes' => (int) env('MEDIA_URL_TTL_MINUTES', 720),

];
