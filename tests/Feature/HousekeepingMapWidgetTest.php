<?php

namespace Tests\Feature;

use App\Filament\Widgets\HousekeepingAreaMapWidget;
use App\Models\Company;
use App\Models\HousekeepingArea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class HousekeepingMapWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_widget_renders_with_area_data(): void
    {
        Gate::before(fn () => true);
        $user = User::factory()->create();
        $this->actingAs($user);

        $company = Company::create(['code' => 'HK-1', 'name' => 'HK Co']);
        HousekeepingArea::create([
            'company_id' => $company->id,
            'code' => 'a',
            'name' => 'Area A',
            'polygon' => [[-6.87, 109.18], [-6.871, 109.18], [-6.871, 109.181]],
            'area_size' => 6151.52,
        ]);

        Livewire::test(HousekeepingAreaMapWidget::class)->assertOk();

        $widget = new HousekeepingAreaMapWidget;
        $widget->mount();
        $areas = $widget->getAreasProperty();

        $this->assertCount(1, $areas);
        $this->assertSame('Area A', $areas[0]['name']);
        $this->assertSame(6151.52, $areas[0]['area_size']);
    }
}
