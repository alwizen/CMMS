<?php

namespace Tests\Unit;

use App\Support\ImageCompressor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageCompressorTest extends TestCase
{
    protected string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir().'/webp-test-'.uniqid();
        mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir.'/*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($this->tmpDir);

        parent::tearDown();
    }

    protected function makeJpeg(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, 20, 120, 200);
        imagefilledrectangle($image, 0, 0, $width, $height, $color);

        $path = $this->tmpDir.'/source.jpg';
        imagejpeg($image, $path, 95);
        imagedestroy($image);

        return $path;
    }

    public function test_to_webp_converts_and_resizes_image(): void
    {
        config(['media.max_width' => 1000, 'media.webp_quality' => 70]);

        $webp = ImageCompressor::toWebp($this->makeJpeg(2000, 1000));

        $this->assertIsString($webp);
        $this->assertNotSame('', $webp);

        $path = $this->tmpDir.'/out.webp';
        file_put_contents($path, $webp);

        $info = getimagesize($path);

        $this->assertSame(1000, $info[0]);
        $this->assertSame(500, $info[1]);
        $this->assertSame('image/webp', $info['mime']);
    }

    protected function makeDetailedJpeg(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);

        for ($y = 0; $y < $height; $y += 4) {
            $color = imagecolorallocate($image, (int) (120 * $y / $height) + 40, (int) (160 * $y / $height) + 60, (int) (200 * $y / $height) + 40);
            imagefilledrectangle($image, 0, $y, $width, $y + 3, $color);
        }

        for ($i = 0; $i < 300; $i++) {
            $color = imagecolorallocate($image, random_int(0, 255), random_int(0, 255), random_int(0, 255));
            imagefilledellipse($image, random_int(0, $width), random_int(0, $height), random_int(20, 500), random_int(20, 500), $color);
        }

        $path = $this->tmpDir.'/detailed.jpg';
        imagejpeg($image, $path, 95);
        imagedestroy($image);

        return $path;
    }

    public function test_output_is_compressed_toward_max_kb(): void
    {
        config([
            'media.max_width' => 1920,
            'media.webp_quality' => 82,
            'media.max_kb' => 100,
            'media.min_quality' => 62,
        ]);

        $webp = ImageCompressor::toWebp($this->makeDetailedJpeg(4032, 3024));

        $this->assertIsString($webp);
        $this->assertLessThanOrEqual(100 * 1024, strlen($webp), 'Ukuran WebP harus <= 100 KB.');

        $info = getimagesizefromstring($webp);

        // Dimensi tetap besar agar foto tidak pecah saat di-zoom.
        $this->assertSame(1920, $info[0]);
        $this->assertSame(1440, $info[1]);
    }

    public function test_max_kb_can_be_disabled(): void
    {
        config(['media.max_width' => 1920, 'media.webp_quality' => 90, 'media.max_kb' => 0]);

        $webp = ImageCompressor::toWebp($this->makeDetailedJpeg(1200, 900));

        $this->assertIsString($webp);
        $this->assertNotSame('', $webp);
    }

    public function test_to_webp_returns_null_for_non_image(): void
    {
        $path = $this->tmpDir.'/notes.txt';
        file_put_contents($path, 'bukan gambar');

        $this->assertNull(ImageCompressor::toWebp($path));
    }

    public function test_to_webp_returns_null_for_missing_file(): void
    {
        $this->assertNull(ImageCompressor::toWebp($this->tmpDir.'/tidak-ada.jpg'));
    }

    public function test_store_as_webp_stores_webp_on_media_disk(): void
    {
        Storage::fake('public');
        config(['media.disk' => 'local']);

        $path = ImageCompressor::storeAsWebp(
            UploadedFile::fake()->image('foto.jpg', 2400, 1600),
            'maintenance-requests',
        );

        $this->assertStringStartsWith('maintenance-requests/', $path);
        $this->assertStringEndsWith('.webp', $path);

        Storage::disk('public')->assertExists($path);

        $info = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertSame('image/webp', $info['mime']);
    }

    public function test_store_as_webp_falls_back_for_unsupported_file(): void
    {
        Storage::fake('public');
        config(['media.disk' => 'local']);

        $path = ImageCompressor::storeAsWebp(
            UploadedFile::fake()->create('catatan.pdf', 10, 'application/pdf'),
            'maintenance-requests',
        );

        $this->assertStringEndsWith('.pdf', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_store_as_webp_respects_explicit_disk(): void
    {
        Storage::fake('public');
        Storage::fake('r2');
        config(['media.disk' => 'local']);

        $path = ImageCompressor::storeAsWebp(
            UploadedFile::fake()->image('foto.jpg'),
            'housekeeping/requests',
            'r2',
        );

        Storage::disk('r2')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }
}
