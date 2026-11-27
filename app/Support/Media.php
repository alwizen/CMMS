<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Resolusi disk & URL media foto (maintenance & housekeeping).
 *
 * Nilai MEDIA_DISK "local" memakai disk "public" agar file bisa diakses web,
 * sedangkan "r2" memakai disk Cloudflare R2 (bucket privat).
 */
class Media
{
    public static function disk(): string
    {
        $disk = (string) config('media.disk', 'local');

        return $disk === 'local' ? 'public' : $disk;
    }

    /**
     * URL absolut media. Untuk disk privat (R2) dipakai presigned URL yang
     * di-cache agar URL stabil selama TTL (bagus untuk cache gambar di app).
     */
    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (static::isAbsoluteUrl($path)) {
            return $path;
        }

        $disk = static::disk();
        $storage = Storage::disk($disk);

        if ($disk !== 'public' && method_exists($storage, 'temporaryUrl')) {
            try {
                return static::temporaryUrl($disk, $storage, $path);
            } catch (Throwable) {
                // Driver tidak mendukung temporary URL; fallback ke url().
            }
        }

        try {
            return $storage->url($path);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Ubah nilai yang dikirim klien (path relatif atau URL absolut) menjadi
     * path penyimpanan. Dipakai endpoint hapus foto agar app boleh mengirim
     * URL presigned yang diterimanya dari API.
     */
    public static function pathFromUrl(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $value = trim($value);

        if (static::isAbsoluteUrl($value)) {
            $parsed = parse_url($value, PHP_URL_PATH);

            if (! is_string($parsed)) {
                return null;
            }

            $value = $parsed;
        }

        // Buang query/fragment (mis. dari URL presigned) lalu normalisasi.
        $path = rawurldecode(preg_split('/[?#]/', ltrim($value, '/'), 2)[0]);

        $bucket = (string) config('filesystems.disks.r2.bucket');

        if ($bucket !== '' && Str::startsWith($path, $bucket.'/')) {
            $path = Str::after($path, $bucket.'/');
        }

        if (Str::startsWith($path, 'storage/')) {
            $path = Str::after($path, 'storage/');
        }

        return $path !== '' ? $path : null;
    }

    public static function isAbsoluteUrl(string $value): bool
    {
        return Str::startsWith($value, ['http://', 'https://']);
    }

    /**
     * Peta daftar path menjadi daftar URL absolut.
     *
     * @param  array<int, string>|null  $paths
     * @return array<int, string>
     */
    public static function urls(?array $paths): array
    {
        return collect($paths ?? [])
            ->filter()
            ->map(fn (string $path): ?string => static::url($path))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Presigned URL yang di-cache per path agar tidak berubah tiap request.
     */
    protected static function temporaryUrl(string $disk, mixed $storage, string $path): string
    {
        $ttlMinutes = max(1, (int) config('media.url_ttl_minutes', 720));
        $cacheKey = 'media:url:'.$disk.':'.sha1($path);

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(max(1, $ttlMinutes - 5)),
            fn (): string => $storage->temporaryUrl($path, now()->addMinutes($ttlMinutes)),
        );
    }
}
