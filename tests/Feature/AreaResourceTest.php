<?php

namespace Tests\Feature;

use App\Filament\Resources\Areas\AreaResource;
use App\Models\Area;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AreaResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_area_resource_is_registered_as_standalone_navigation_item(): void
    {
        $this->assertTrue(AreaResource::shouldRegisterNavigation());
        $this->assertSame('Master Data', AreaResource::getNavigationGroup());
        $this->assertSame('/admin/areas', parse_url(AreaResource::getUrl('index'), PHP_URL_PATH));
    }

    public function test_area_nested_under_company_route_is_removed(): void
    {
        $this->assertFalse(
            \Route::has('filament.admin.resources.companies.areas.index'),
        );

        $this->assertTrue(
            \Route::has('filament.admin.resources.areas.index'),
        );
    }

    public function test_area_index_and_edit_pages_render(): void
    {
        config(['app.env' => 'local']);
        Gate::before(fn (): bool => true);

        $user = User::factory()->create();
        $company = Company::create(['code' => 'CO', 'name' => 'Demo Company']);
        $area = Area::create([
            'company_id' => $company->id,
            'code' => 'LOAD',
            'name' => 'Loading',
        ]);

        $this->actingAs($user)->get('/admin/areas')->assertSuccessful();
        $this->actingAs($user)->get("/admin/areas/{$area->id}/edit")->assertSuccessful();
        $this->actingAs($user)->get("/admin/areas/{$area->id}/equipment")->assertSuccessful();
    }
}
