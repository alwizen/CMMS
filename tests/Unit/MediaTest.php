<?php

namespace Tests\Unit;

use App\Support\Media;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaTest extends TestCase
{
    public function test_disk_maps_local_to_public_disk(): void
    {
        config(['media.disk' => 'local']);

        $this->assertSame('public', Media::disk());
    }

    public function test_disk_returns_r2_when_configured(): void
    {
        config(['media.disk' => 'r2']);

        $this->assertSame('r2', Media::disk());
    }

    public function test_url_returns_null_for_blank_path(): void
    {
        $this->assertNull(Media::url(null));
        $this->assertNull(Media::url(''));
    }

    public function test_url_uses_public_disk_url(): void
    {
        Storage::fake('public');
        config(['media.disk' => 'local']);

        Storage::disk('public')->put('maintenance-requests/foto.webp', 'x');

        $url = Media::url('maintenance-requests/foto.webp');

        $this->assertIsString($url);
        $this->assertStringContainsString('maintenance-requests/foto.webp', $url);
    }

    public function test_url_resolves_file_from_r2_disk_when_configured(): void
    {
        Storage::fake('public');
        Storage::fake('r2');
        config(['media.disk' => 'r2']);

        Storage::disk('r2')->put('maintenance-requests/foto.webp', 'x');

        $url = Media::url('maintenance-requests/foto.webp');

        $this->assertIsString($url);
        $this->assertStringContainsString('maintenance-requests/foto.webp', $url);
    }
}
