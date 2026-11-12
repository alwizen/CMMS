<?php

namespace Tests\Feature;

use App\Filament\Widgets\HousekeepingAreaMapWidget;
use App\Models\Company;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingFacility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class HousekeepingAreaMapFacilitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_widget_includes_facilities_list_and_total(): void
    {
        Gate::before(fn () => true);
        $this->actingAs(User::factory()->create());

        $company = Company::create(['code' => 'HK-1', 'name' => 'HK Co']);
        $area = HousekeepingArea::create([
            'company_id' => $company->id,
            'code' => 'a',
            'name' => 'Area A',
            'polygon' => [[-6.87, 109.18], [-6.871, 109.18], [-6.871, 109.181]],
            'area_size' => 6151.52,
        ]);
        HousekeepingFacility::create(['housekeeping_area_id' => $area->id, 'name' => 'Oil Catcher', 'code' => 'OC-01', 'is_active' => true]);
        HousekeepingFacility::create(['housekeeping_area_id' => $area->id, 'name' => 'Selokan', 'code' => 'SLK-01', 'is_active' => true]);
        HousekeepingFacility::create(['housekeeping_area_id' => $area->id, 'name' => 'Nonaktif', 'is_active' => false]);

        Livewire::test(HousekeepingAreaMapWidget::class)->assertOk();

        $widget = new HousekeepingAreaMapWidget;
        $widget->mount();
        $areas = $widget->getAreasProperty();

        $this->assertCount(1, $areas);
        $this->assertSame(2, $areas[0]['facilities_count']);
        $this->assertCount(2, $areas[0]['facilities']);
        $this->assertSame('Oil Catcher', $areas[0]['facilities'][0]['name']);
    }
}
