<?php

namespace Tests\Feature;

use App\Filament\Resources\HousekeepingAreas\Pages\EditHousekeepingArea;
use App\Filament\Resources\HousekeepingAreas\RelationManagers\FacilitiesRelationManager;
use App\Models\Company;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingFacility;
use App\Models\User;
use App\Support\Geo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class HousekeepingFacilityPolygonTest extends TestCase
{
    use RefreshDatabase;

    protected HousekeepingArea $area;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn (): bool => true);
        $this->actingAs(User::factory()->create());

        $company = Company::create(['code' => 'HK', 'name' => 'HK Co']);

        $this->area = HousekeepingArea::create([
            'company_id' => $company->id,
            'code' => 'taman',
            'name' => 'Taman Kantor',
            'polygon' => [
                [-6.8700, 109.1860],
                [-6.8690, 109.1870],
                [-6.8700, 109.1880],
            ],
        ]);
    }

    protected function relationManager()
    {
        return Livewire::test(FacilitiesRelationManager::class, [
            'ownerRecord' => $this->area,
            'pageClass' => EditHousekeepingArea::class,
        ]);
    }

    public function test_facility_form_has_polygon_field(): void
    {
        $this->relationManager()
            ->mountTableAction('create')
            ->assertFormFieldExists('polygon')
            ->assertFormFieldExists('area_size');
    }

    public function test_facility_polygon_is_saved_with_computed_area_and_center(): void
    {
        $polygon = [
            [-6.8705, 109.1865],
            [-6.8700, 109.1870],
            [-6.8705, 109.1875],
        ];

        $this->relationManager()
            ->mountTableAction('create')
            ->setTableActionData([
                'name' => 'Selokan Depan',
                'is_active' => true,
            ])
            ->set('mountedActions.0.data.polygon', $polygon)
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $facility = HousekeepingFacility::where('name', 'Selokan Depan')->firstOrFail();

        $this->assertSame($polygon, $facility->polygon);
        $this->assertEqualsWithDelta(Geo::polygonArea($polygon), $facility->area_size, 0.01);
        $this->assertNotNull($facility->latitude);
        $this->assertNotNull($facility->longitude);
    }

    public function test_facility_polygon_is_nullable(): void
    {
        HousekeepingFacility::create([
            'housekeeping_area_id' => $this->area->id,
            'name' => 'Tanpa Polygon',
        ]);

        $facility = HousekeepingFacility::where('name', 'Tanpa Polygon')->firstOrFail();

        $this->assertNull($facility->polygon);
        $this->assertNull($facility->area_size);
    }
}
