<?php

namespace App\Support\Report;

use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Helper untuk mengubah file gambar di storage menjadi data URI
 * agar bisa dirender DomPDF tanpa perlu akses HTTP.
 */
class ImageData
{
    public static function dataUri(?string $path, string $disk = 'public'): ?string
    {
        if (blank($path)) {
            return null;
        }

        try {
            $storage = Storage::disk($disk);

            if (! $storage->exists($path)) {
                return null;
            }

            $contents = $storage->get($path);

            if (blank($contents)) {
                return null;
            }

            $mime = $storage->mimeType($path) ?: 'image/jpeg';

            return 'data:'.$mime.';base64,'.base64_encode($contents);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Ambil beberapa foto pertama (untuk membatasi ukuran PDF).
     *
     * @param  array<int, string>|null  $paths
     * @return array<int, string>
     */
    public static function dataUris(?array $paths, int $limit = 2, string $disk = 'public'): array
    {
        return collect($paths ?? [])
            ->filter()
            ->take($limit)
            ->map(fn (string $path): ?string => static::dataUri($path, $disk))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Thumbnail persegi (crop tengah) agar grid foto pada PDF rapi dan
     * ukuran file laporan tetap kecil. Bila ekstensi GD tidak tersedia,
     * fallback ke data URI asli.
     *
     * @param  array<int, string>|null  $paths
     * @return array<int, string>
     */
    public static function thumbnailUris(?array $paths, int $limit = 4, int $size = 160, string $disk = 'public'): array
    {
        return collect($paths ?? [])
            ->filter()
            ->take($limit)
            ->map(fn (string $path): ?string => static::thumbnailDataUri($path, $size, $disk))
            ->filter()
            ->values()
            ->all();
    }

    public static function thumbnailDataUri(?string $path, int $size = 160, string $disk = 'public'): ?string
    {
        if (blank($path)) {
            return null;
        }

        try {
            $storage = Storage::disk($disk);

            if (! $storage->exists($path)) {
                return null;
            }

            $contents = $storage->get($path);

            if (blank($contents)) {
                return null;
            }

            $thumbnail = static::squareThumbnail($contents, $size);

            if ($thumbnail === null) {
                // Fallback: kirim gambar apa adanya bila GD tidak bisa memproses.
                $mime = $storage->mimeType($path) ?: 'image/jpeg';

                return 'data:'.$mime.';base64,'.base64_encode($contents);
            }

            return 'data:image/jpeg;base64,'.base64_encode($thumbnail);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Crop tengah gambar menjadi persegi lalu resize.
     */
    protected static function squareThumbnail(string $contents, int $size): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $source = @imagecreatefromstring($contents);

        if ($source === false || $source === null) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);

        if ($width <= 0 || $height <= 0) {
            return null;
        }

        $edge = min($width, $height);
        $srcX = (int) (($width - $edge) / 2);
        $srcY = (int) (($height - $edge) / 2);

        $canvas = imagecreatetruecolor($size, $size);

        // Latar putih untuk gambar transparan (PNG/WebP).
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $size, $size, $white);

        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            $srcX,
            $srcY,
            $size,
            $size,
            $edge,
            $edge,
        );

        ob_start();
        imagejpeg($canvas, null, 75);
        $thumbnail = ob_get_clean();

        return is_string($thumbnail) && $thumbnail !== '' ? $thumbnail : null;
    }
}
