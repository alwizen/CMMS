<?php

namespace App\Support;

use Filament\Forms\Components\BaseFileUpload;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

/**
 * Kompres foto unggahan menjadi WebP agar hemat penyimpanan.
 *
 * Dipakai oleh endpoint API maupun FileUpload Filament pada modul
 * maintenance dan housekeeping.
 */
class ImageCompressor
{
    /**
     * Simpan gambar sebagai WebP terkompresi. Bila GD/WebP tidak tersedia
     * atau proses gagal, file disimpan apa adanya (fallback).
     */
    public static function storeAsWebp(
        UploadedFile $file,
        string $directory,
        ?string $disk = null,
        ?int $quality = null,
        ?int $maxWidth = null,
    ): string {
        $disk ??= Media::disk();
        $directory = trim($directory, '/');

        $contents = static::toWebp(
            (string) $file->getRealPath(),
            $quality,
            $maxWidth,
        );

        if ($contents === null) {
            return $file->store($directory, $disk);
        }

        $path = trim($directory.'/'.Str::ulid().'.webp', '/');

        Storage::disk($disk)->put($path, $contents);

        return $path;
    }

    /**
     * Callback untuk FileUpload Filament: hasil unggahan otomatis jadi WebP.
     */
    public static function storeFilamentUpload(BaseFileUpload $component, TemporaryUploadedFile $file): ?string
    {
        return static::storeAsWebp(
            $file,
            (string) $component->getDirectory(),
            $component->getDiskName(),
        );
    }

    /**
     * Ubah file gambar menjadi biner WebP. Null bila gagal/tidak didukung.
     */
    public static function toWebp(string $path, ?int $quality = null, ?int $maxWidth = null): ?string
    {
        if (
            $path === ''
            || ! is_file($path)
            || ! function_exists('imagecreatefromstring')
            || ! function_exists('imagewebp')
        ) {
            return null;
        }

        $quality ??= (int) config('media.webp_quality', 75);
        $maxWidth ??= (int) config('media.max_width', 1920);

        try {
            $contents = @file_get_contents($path);
        } catch (Throwable) {
            return null;
        }

        if (blank($contents)) {
            return null;
        }

        $image = @imagecreatefromstring($contents);

        if (! $image instanceof GdImage) {
            return null;
        }

        $image = static::orient($image, $path);

        if ($maxWidth > 0 && imagesx($image) > $maxWidth) {
            $image = static::resize($image, $maxWidth);
        }

        $canvas = static::flatten($image);

        imagedestroy($image);

        ob_start();
        $success = imagewebp($canvas, null, max(0, min(100, $quality)));
        $webp = ob_get_clean();

        imagedestroy($canvas);

        return $success && is_string($webp) && $webp !== '' ? $webp : null;
    }

    /**
     * Perbaiki orientasi foto (EXIF) dari kamera ponsel.
     */
    protected static function orient(GdImage $image, string $path): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        try {
            $exif = @exif_read_data($path);
        } catch (Throwable) {
            return $image;
        }

        $rotated = match ($exif['Orientation'] ?? null) {
            3 => @imagerotate($image, 180, 0xFFFFFF),
            6 => @imagerotate($image, -90, 0xFFFFFF),
            8 => @imagerotate($image, 90, 0xFFFFFF),
            default => null,
        };

        return $rotated instanceof GdImage ? $rotated : $image;
    }

    protected static function resize(GdImage $image, int $maxWidth): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $newWidth = $maxWidth;
        $newHeight = max(1, (int) round($height * ($maxWidth / $width)));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);

        imagecopyresampled(
            $canvas,
            $image,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $width,
            $height,
        );

        imagedestroy($image);

        return $canvas;
    }

    /**
     * Ratakan transparansi ke latar putih agar tampil konsisten di PDF.
     */
    protected static function flatten(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $canvas = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($canvas, 255, 255, 255);

        imagefilledrectangle($canvas, 0, 0, $width - 1, $height - 1, $white);
        imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);

        return $canvas;
    }
}
