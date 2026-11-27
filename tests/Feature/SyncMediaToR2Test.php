<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SyncMediaToR2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('r2');
    }

    public function test_dry_run_does_not_copy_files(): void
    {
        Storage::disk('public')->put('maintenance-requests/a.webp', 'aaa');
        Storage::disk('public')->put('housekeeping/requests/b.webp', 'bbb');

        $this->artisan('media:sync-to-r2', ['--dry-run' => true])
            ->assertSuccessful();

        Storage::disk('r2')->assertMissing('maintenance-requests/a.webp');
        Storage::disk('r2')->assertMissing('housekeeping/requests/b.webp');
    }

    public function test_it_copies_all_files_preserving_paths(): void
    {
        Storage::disk('public')->put('maintenance-requests/a.webp', 'aaa');
        Storage::disk('public')->put('housekeeping/plan-tasks/before/b.webp', 'bbb');

        $this->artisan('media:sync-to-r2')->assertSuccessful();

        Storage::disk('r2')->assertExists('maintenance-requests/a.webp');
        Storage::disk('r2')->assertExists('housekeeping/plan-tasks/before/b.webp');
        $this->assertSame('aaa', Storage::disk('r2')->get('maintenance-requests/a.webp'));
        $this->assertSame('bbb', Storage::disk('r2')->get('housekeeping/plan-tasks/before/b.webp'));
    }

    public function test_existing_target_files_are_skipped_by_default(): void
    {
        Storage::disk('public')->put('maintenance-requests/a.webp', 'new');
        Storage::disk('r2')->put('maintenance-requests/a.webp', 'old');

        $this->artisan('media:sync-to-r2')->assertSuccessful();

        $this->assertSame('old', Storage::disk('r2')->get('maintenance-requests/a.webp'));
    }

    public function test_force_overwrites_existing_target_files(): void
    {
        Storage::disk('public')->put('maintenance-requests/a.webp', 'new');
        Storage::disk('r2')->put('maintenance-requests/a.webp', 'old');

        $this->artisan('media:sync-to-r2', ['--force' => true])->assertSuccessful();

        $this->assertSame('new', Storage::disk('r2')->get('maintenance-requests/a.webp'));
    }

    public function test_it_succeeds_when_source_is_empty(): void
    {
        $this->artisan('media:sync-to-r2')
            ->expectsOutputToContain('Tidak ada file')
            ->assertSuccessful();
    }

    public function test_it_fails_when_target_disk_is_undefined(): void
    {
        $this->artisan('media:sync-to-r2', ['--target' => 'tidak-ada'])
            ->assertFailed();
    }
}
