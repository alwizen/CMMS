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
     *
     * Ukuran akhir ditekan agar mendekati target (default ~100 KB) dengan
     * menurunkan kualitas bertahap, lalu memperkecil dimensi bila perlu.
     * Kualitas tidak turun di bawah batas agar foto tetap tajam.
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

        $quality ??= (int) config('media.webp_quality', 82);
        $maxWidth ??= (int) config('media.max_width', 1920);
        $maxKb = (int) config('media.max_kb', 100);
        $minQuality = (int) config('media.min_quality', 62);
        $maxBytes = $maxKb > 0 ? $maxKb * 1024 : 0;

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

        $quality = max(0, min(100, $quality));
        $minQuality = max(0, min($quality, $minQuality));

        $webp = static::encodeWebp($canvas, $quality);

        // 1) Turunkan kualitas bertahap sampai masuk target.
        while (
            $webp !== null
            && $maxBytes > 0
            && strlen($webp) > $maxBytes
            && $quality > $minQuality
        ) {
            $quality = max($minQuality, $quality - 5);
            $webp = static::encodeWebp($canvas, $quality);
        }

        // 2) Bila masih terlalu besar, perkecil dimensi (kualitas dipertahankan).
        $minWidth = 1024;

        while (
            $webp !== null
            && $maxBytes > 0
            && strlen($webp) > $maxBytes
            && imagesx($canvas) > $minWidth
        ) {
            $canvas = static::scaleBy($canvas, 0.85);
            $webp = static::encodeWebp($canvas, $quality);
        }

        imagedestroy($canvas);

        return $webp;
    }

    protected static function encodeWebp(GdImage $image, int $quality): ?string
    {
        ob_start();
        $success = imagewebp($image, null, max(0, min(100, $quality)));
        $webp = ob_get_clean();

        return $success && is_string($webp) && $webp !== '' ? $webp : null;
    }

    /**
     * Perkecil gambar dengan rasio tertentu (dipakai saat target ukuran belum tercapai).
     */
    protected static function scaleBy(GdImage $image, float $ratio): GdImage
    {
        $width = max(1, (int) round(imagesx($image) * $ratio));
        $height = max(1, (int) round(imagesy($image) * $ratio));

        $canvas = imagecreatetruecolor($width, $height);

        imagecopyresampled(
            $canvas,
            $image,
            0,
            0,
            0,
            0,
            $width,
            $height,
            imagesx($image),
            imagesy($image),
        );

        imagedestroy($image);

        return $canvas;
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
