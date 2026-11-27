<?php

namespace Tests\Unit;

use App\Support\Media;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaUrlTest extends TestCase
{
    public function test_blank_values_return_null(): void
    {
        $this->assertNull(Media::url(null));
        $this->assertNull(Media::url(''));
        $this->assertNull(Media::pathFromUrl(null));
        $this->assertNull(Media::pathFromUrl('  '));
    }

    public function test_absolute_url_is_passed_through(): void
    {
        $url = 'https://bucket.r2.cloudflarestorage.com/rumat-media/a.webp?X-Amz-Signature=abc';

        $this->assertSame($url, Media::url($url));
    }

    public function test_plain_path_is_kept_when_normalizing(): void
    {
        $this->assertSame(
            'maintenance-requests/a.webp',
            Media::pathFromUrl('maintenance-requests/a.webp'),
        );
        $this->assertSame(
            'maintenance-requests/a.webp',
            Media::pathFromUrl('/maintenance-requests/a.webp'),
        );
    }

    public function test_local_public_url_is_normalized_to_path(): void
    {
        $this->assertSame(
            'housekeeping/requests/a.webp',
            Media::pathFromUrl('https://rumat.test/storage/housekeeping/requests/a.webp'),
        );
    }

    public function test_r2_path_style_url_is_normalized_to_path(): void
    {
        config(['filesystems.disks.r2.bucket' => 'rumat-media']);

        $url = 'https://acc.r2.cloudflarestorage.com/rumat-media/maintenance-requests/a.webp'
            .'?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Signature=abc';

        $this->assertSame('maintenance-requests/a.webp', Media::pathFromUrl($url));
    }

    public function test_r2_virtual_host_url_is_normalized_to_path(): void
    {
        config(['filesystems.disks.r2.bucket' => 'rumat-media']);

        $url = 'https://rumat-media.acc.r2.cloudflarestorage.com/maintenance-requests/a.webp?X-Amz-Signature=abc';

        $this->assertSame('maintenance-requests/a.webp', Media::pathFromUrl($url));
    }

    public function test_url_encoded_path_is_decoded(): void
    {
        $this->assertSame(
            'maintenance-requests/foto 1.webp',
            Media::pathFromUrl('https://rumat.test/storage/maintenance-requests/foto%201.webp'),
        );
    }

    public function test_urls_maps_paths_and_drops_empty_entries(): void
    {
        Storage::fake('public');
        config(['media.disk' => 'local']);

        $urls = Media::urls(['a.webp', '', null, 'b.webp']);

        $this->assertCount(2, $urls);
        $this->assertStringContainsString('a.webp', $urls[0]);
        $this->assertStringContainsString('b.webp', $urls[1]);
    }

    public function test_urls_returns_empty_array_for_null(): void
    {
        $this->assertSame([], Media::urls(null));
    }
}
