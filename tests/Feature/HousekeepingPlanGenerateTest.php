<?php

namespace Tests\Feature;

use App\Filament\Pages\HousekeepingDashboard;
use App\Filament\Resources\HousekeepingRequests\Pages\ViewHousekeepingRequest;
use App\Models\Company;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingFacility;
use App\Models\HousekeepingManpower;
use App\Models\HousekeepingPlan;
use App\Models\HousekeepingPlanTask;
use App\Models\HousekeepingRequest;
use App\Models\HousekeepingTask;
use App\Models\Setting;
use App\Models\User;
use App\Support\HousekeepingPlanGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class HousekeepingPlanGenerateTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected HousekeepingArea $area;

    protected HousekeepingFacility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();

        $this->user = User::factory()->create();

        $company = Company::create(['code' => 'HK', 'name' => 'HK Co']);

        $this->area = HousekeepingArea::create([
            'company_id' => $company->id,
            'code' => 'taman',
            'name' => 'Taman Kantor',
            'is_active' => true,
        ]);

        $this->facility = HousekeepingFacility::create([
            'housekeeping_area_id' => $this->area->id,
            'code' => 'gazebo',
            'name' => 'Gazebo',
            'is_active' => true,
        ]);

        HousekeepingTask::create([
            'housekeeping_facility_id' => $this->facility->id,
            'name' => 'Sapu Daun',
            'is_active' => true,
        ]);

        HousekeepingTask::create([
            'housekeeping_facility_id' => $this->facility->id,
            'name' => 'Bersihkan Bangku',
            'is_active' => true,
        ]);

        HousekeepingTask::create([
            'housekeeping_facility_id' => $this->facility->id,
            'name' => 'Task Nonaktif',
            'is_active' => false,
        ]);
    }

    public function test_generator_creates_plan_with_checklist_and_all_active_manpowers(): void
    {
        HousekeepingManpower::create([
            'housekeeping_area_id' => $this->area->id,
            'name' => 'Budi',
            'is_active' => true,
        ]);
        HousekeepingManpower::create([
            'housekeeping_area_id' => $this->area->id,
            'name' => 'Non Aktif',
            'is_active' => false,
        ]);

        $result = app(HousekeepingPlanGenerator::class)->generate(
            areaIds: [$this->area->id],
            planDate: today()->toDateString(),
            createdBy: $this->user->id,
        );

        $this->assertSame(1, $result['created']);

        $plan = HousekeepingPlan::firstOrFail();

        $this->assertSame(HousekeepingPlan::STATUSES['Draft'], $plan->status);
        $this->assertSame(2, $plan->tasks()->count());
        $this->assertSame(1, $plan->manpowers()->count());
        $this->assertSame('Budi', $plan->manpowers()->first()->name);
    }

    public function test_generator_skips_area_without_checklist(): void
    {
        $emptyArea = HousekeepingArea::create([
            'company_id' => $this->area->company_id,
            'code' => 'kosong',
            'name' => 'Area Kosong',
            'is_active' => true,
        ]);

        $result = app(HousekeepingPlanGenerator::class)->generate(
            areaIds: [$this->area->id, $emptyArea->id],
            planDate: today()->toDateString(),
        );

        $this->assertSame(1, $result['created']);
        $this->assertArrayHasKey('Area Kosong', $result['skip_reasons']);
    }

    public function test_generator_skips_duplicate_plan_on_same_date(): void
    {
        HousekeepingPlan::create([
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => today(),
            'status' => 'Draft',
        ]);

        $result = app(HousekeepingPlanGenerator::class)->generate(
            areaIds: [$this->area->id],
            planDate: today()->toDateString(),
        );

        $this->assertSame(0, $result['created']);
        $this->assertArrayHasKey('Taman Kantor', $result['skip_reasons']);
    }

    public function test_generator_filters_selected_manpowers(): void
    {
        $budi = HousekeepingManpower::create([
            'housekeeping_area_id' => $this->area->id,
            'name' => 'Budi',
            'is_active' => true,
        ]);
        HousekeepingManpower::create([
            'housekeeping_area_id' => $this->area->id,
            'name' => 'Sari',
            'is_active' => true,
        ]);

        app(HousekeepingPlanGenerator::class)->generate(
            areaIds: [$this->area->id],
            planDate: today()->toDateString(),
            manpowerIds: [$budi->id],
        );

        $plan = HousekeepingPlan::firstOrFail();

        $this->assertSame([$budi->id], $plan->manpowers()->pluck('housekeeping_manpowers.id')->all());
    }

    public function test_dashboard_generate_plan_action_creates_plan(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->user);

        Livewire::test(HousekeepingDashboard::class)
            ->assertActionExists('generateHousekeepingPlans')
            ->callAction('generateHousekeepingPlans', data: [
                'area_ids' => [$this->area->id],
                'plan_date' => today()->toDateString(),
            ])
            ->assertHasNoActionErrors()
            ->assertNotified('Generate Plan Harian selesai');

        $this->assertSame(1, HousekeepingPlan::count());
        $this->assertSame(2, HousekeepingPlanTask::count());
    }

    public function test_dashboard_generate_plan_warns_when_no_area_qualifies(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->user);

        HousekeepingTask::query()->update(['is_active' => false]);

        Livewire::test(HousekeepingDashboard::class)
            ->callAction('generateHousekeepingPlans', data: [
                'area_ids' => [$this->area->id],
                'plan_date' => today()->toDateString(),
            ])
            ->assertNotified('Tidak ada Plan Harian yang dibuat');

        $this->assertSame(0, HousekeepingPlan::count());
    }

    public function test_housekeeping_request_generated_number_and_approval_creates_plan(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->user);

        $request = HousekeepingRequest::create([
            'housekeeping_area_id' => $this->area->id,
            'housekeeping_facility_id' => $this->facility->id,
            'request_date' => today()->toDateString(),
            'description' => 'Gazebo kotor setelah acara',
            'status' => HousekeepingRequest::STATUSES['Open'],
        ]);

        $this->assertStringStartsWith('HKR-', $request->request_number);

        Livewire::test(ViewHousekeepingRequest::class, ['record' => $request->getKey()])
            ->assertOk()
            ->assertSee('Gazebo kotor setelah acara')
            ->callAction('approve', data: [
                'plan_date' => today()->toDateString(),
            ])
            ->assertHasNoActionErrors();

        $request->refresh();

        $this->assertSame(HousekeepingRequest::STATUSES['Approved'], $request->status);
        $this->assertNotNull($request->housekeeping_plan_id);
        $this->assertSame(2, $request->plan->tasks()->count());
    }
}
