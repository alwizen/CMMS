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
use Spatie\Permission\Models\Role;
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

    public function test_generator_blocks_area_with_running_plan_within_sla(): void
    {
        $this->area->update(['sla_days' => 14]);

        $running = HousekeepingPlan::create([
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => today()->subDays(3),
            'status' => 'In Progress',
        ]);

        $result = app(HousekeepingPlanGenerator::class)->generate(
            areaIds: [$this->area->id],
            planDate: today()->toDateString(),
        );

        $this->assertSame(0, $result['created']);
        $this->assertStringContainsString($running->plan_number, $result['skip_reasons']['Taman Kantor']);
    }

    public function test_generator_allows_area_after_sla_deadline_passed(): void
    {
        $this->area->update(['sla_days' => 1]);

        HousekeepingPlan::create([
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => today()->subDays(5),
            'status' => 'In Progress',
        ]);

        $result = app(HousekeepingPlanGenerator::class)->generate(
            areaIds: [$this->area->id],
            planDate: today()->toDateString(),
        );

        $this->assertSame(1, $result['created']);
    }

    public function test_generator_allows_area_after_previous_plan_completed(): void
    {
        $this->area->update(['sla_days' => 14]);

        HousekeepingPlan::create([
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => today()->subDays(3),
            'status' => 'Completed',
        ]);

        $result = app(HousekeepingPlanGenerator::class)->generate(
            areaIds: [$this->area->id],
            planDate: today()->toDateString(),
        );

        $this->assertSame(1, $result['created']);
    }

    public function test_completed_plan_on_same_date_does_not_block_new_plan(): void
    {
        $this->area->update(['sla_days' => 14]);

        $previous = HousekeepingPlan::create([
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => today(),
            'status' => 'Completed',
        ]);

        $this->assertNull(
            app(HousekeepingPlanGenerator::class)->blockingPlan($this->area, today()),
        );

        $result = app(HousekeepingPlanGenerator::class)->generate(
            areaIds: [$this->area->id],
            planDate: today()->toDateString(),
        );

        $this->assertSame(1, $result['created']);
        $this->assertNotSame($previous->id, HousekeepingPlan::query()->latest('id')->value('id'));
    }

    public function test_running_plan_on_same_date_still_blocks(): void
    {
        $this->area->update(['sla_days' => 14]);

        HousekeepingPlan::create([
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => today(),
            'status' => 'In Progress',
        ]);

        $this->assertNotNull(
            app(HousekeepingPlanGenerator::class)->blockingPlan($this->area, today()),
        );
    }

    public function test_api_store_rejects_area_with_running_plan(): void
    {
        Role::findOrCreate('spv_gos', 'web');

        $spv = User::factory()->create(['status' => true]);
        $spv->assignRole('spv_gos');
        $this->actingAs($spv);
        $this->area->update(['sla_days' => 14]);

        HousekeepingPlan::create([
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => today()->subDay(),
            'status' => 'In Progress',
        ]);

        $this->postJson('/api/housekeeping-plans', [
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => today()->toDateString(),
        ])->assertStatus(422)->assertJsonPath('success', false);

        $this->assertSame(1, HousekeepingPlan::count());
    }

    public function test_approval_is_blocked_when_area_has_running_plan(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->user);
        $this->area->update(['sla_days' => 14]);

        HousekeepingPlan::create([
            'housekeeping_area_id' => $this->area->id,
            'plan_date' => today()->subDay(),
            'status' => 'In Progress',
        ]);

        $request = HousekeepingRequest::create([
            'housekeeping_area_id' => $this->area->id,
            'housekeeping_facility_id' => $this->facility->id,
            'request_date' => today()->toDateString(),
            'description' => 'Gazebo kotor',
            'status' => HousekeepingRequest::STATUSES['Open'],
        ]);

        Livewire::test(ViewHousekeepingRequest::class, ['record' => $request->getKey()])
            ->callAction('approve', data: ['plan_date' => today()->toDateString()])
            ->assertNotified('Tidak bisa membuat Plan Harian');

        $this->assertSame(
            HousekeepingRequest::STATUSES['Open'],
            $request->fresh()->status,
        );
        $this->assertSame(1, HousekeepingPlan::count());
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

    public function test_approval_assigns_patra_jasa_users_to_plan(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->user);

        Role::findOrCreate('patra-jasa', 'web');

        $petugas = User::factory()->create(['name' => 'Petugas Satu', 'status' => true]);
        $petugas->assignRole('patra-jasa');

        $bukanPetugas = User::factory()->create(['name' => 'Bukan Petugas', 'status' => true]);

        $request = HousekeepingRequest::create([
            'housekeeping_area_id' => $this->area->id,
            'request_date' => today()->toDateString(),
            'description' => 'Area perlu dibersihkan',
            'status' => HousekeepingRequest::STATUSES['Open'],
        ]);

        Livewire::test(ViewHousekeepingRequest::class, ['record' => $request->getKey()])
            ->callAction('approve', data: [
                'plan_date' => today()->toDateString(),
                'user_ids' => [$petugas->id],
            ])
            ->assertHasNoActionErrors();

        $plan = HousekeepingPlan::firstOrFail();

        $this->assertSame([$petugas->id], $plan->users()->pluck('users.id')->all());
        $this->assertNotContains($bukanPetugas->id, $plan->users()->pluck('users.id')->all());
    }
}
