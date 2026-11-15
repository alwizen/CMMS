<?php

namespace Tests\Feature;

use App\Filament\Widgets\CompanyAreaMapWidget;
use App\Models\Area;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AreaPolygonTest extends TestCase
{
    use RefreshDatabase;

    public function test_area_polygon_is_cast_to_array(): void
    {
        $company = Company::create(['code' => 'CO', 'name' => 'Demo Company']);

        $polygon = [[-6.87, 109.18], [-6.87, 109.19], [-6.88, 109.19], [-6.88, 109.18]];

        $area = Area::create([
            'company_id' => $company->id,
            'code' => 'LOAD',
            'name' => 'Loading',
            'polygon' => $polygon,
            'latitude' => -6.875,
            'longitude' => 109.185,
        ]);

        $this->assertSame($polygon, $area->fresh()->polygon);
    }

    public function test_map_widget_returns_area_polygon(): void
    {
        $company = Company::create([
            'code' => 'CO',
            'name' => 'Demo Company',
            'geofence' => [[-6.9, 109.1], [-6.9, 109.2], [-6.8, 109.2], [-6.8, 109.1]],
        ]);

        $polygon = [[-6.87, 109.18], [-6.87, 109.19], [-6.88, 109.19], [-6.88, 109.18]];

        Area::create([
            'company_id' => $company->id,
            'code' => 'LOAD',
            'name' => 'Loading',
            'polygon' => $polygon,
            'latitude' => -6.875,
            'longitude' => 109.185,
        ]);

        $widget = new CompanyAreaMapWidget;
        $widget->mount();
        $companies = $widget->getCompaniesProperty();

        $this->assertCount(1, $companies);
        $this->assertCount(1, $companies[0]['areas']);
        $this->assertSame($polygon, $companies[0]['areas'][0]['polygon']);
    }
}
