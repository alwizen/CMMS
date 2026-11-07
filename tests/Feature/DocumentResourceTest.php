<?php

namespace Tests\Feature;

use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class DocumentResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_resource_is_registered_as_standalone_navigation_item(): void
    {
        $this->assertTrue(DocumentResource::shouldRegisterNavigation());
        $this->assertSame('Master Data', DocumentResource::getNavigationGroup());
        $this->assertSame('/admin/documents', parse_url(DocumentResource::getUrl('index'), PHP_URL_PATH));
    }

    public function test_document_index_create_and_edit_pages_render(): void
    {
        config(['app.env' => 'local']);
        Gate::before(fn (): bool => true);

        $user = User::factory()->create();

        $document = Document::create([
            'title' => 'Pump Calibration Certificate',
            'document_type' => 'certification',
            'expiry_date' => today()->addYear(),
        ]);

        $this->assertStringStartsWith('DOC-', $document->document_number);

        $this->actingAs($user)->get('/admin/documents')->assertSuccessful();
        $this->actingAs($user)->get('/admin/documents/create')->assertSuccessful();
        $this->actingAs($user)->get("/admin/documents/{$document->id}/edit")->assertSuccessful();
    }

    public function test_document_expiry_status_is_calculated_from_expiry_date(): void
    {
        $document = new Document(['expiry_date' => today()->subDay()]);
        $this->assertSame('expired', $document->expiryStatus());

        $document = new Document(['expiry_date' => today()->addDays(7)]);
        $this->assertSame('expiring_soon', $document->expiryStatus());

        $document = new Document(['expiry_date' => today()->addDays(60)]);
        $this->assertSame('valid', $document->expiryStatus());

        $document = new Document;
        $this->assertSame('no_expiry', $document->expiryStatus());
    }
}
