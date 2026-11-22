<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings;
use App\Models\Activity;
use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkOrder;
use App\Support\SystemUser;
use App\Support\WorkOrderGenerator;
use App\Support\WorkOrderSettings;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ScheduledWorkOrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $technician;

    protected EquipmentType $type;

    protected Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();
        Carbon::setTestNow(Carbon::parse('2026-11-16 08:00:00'));

        Role::findOrCreate('mos', 'web');

        $this->technician = User::factory()->create([
            'status' => true,
            'fungsi' => 'MOS LTSA',
        ]);
        $this->technician->assignRole('mos');

        $company = Company::create(['code' => 'CO', 'name' => 'Demo Company']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'AR', 'name' => 'Demo Area']);

        $this->type = EquipmentType::create([
            'code' => 'pump',
            'name' => 'Pump',
            'fungsi' => 'MOS LTSA',
            'status' => true,
        ]);

        $this->equipment = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $this->type->id,
            'tag_number' => 'P-101',
        ]);

        $this->makeActivity('Cek Harian', 'daily');
        $this->makeActivity('Cek Bulanan', 'monthly');
    }

    protected function makeActivity(string $name, string $interval): Activity
    {
        return Activity::create([
            'equipment_type_id' => $this->type->id,
            'name' => $name,
            'type' => 'maintenance',
            'maintenance_classification' => 'preventive',
            'interval' => $interval,
            'answer_type' => 'qualitative',
            'status' => true,
        ]);
    }

    protected function enableAutoGenerate(array $settings = []): void
    {
        Setting::setMany([
            WorkOrderSettings::AUTO_GENERATE => true,
            WorkOrderSettings::LEAD_DAYS => 3,
            WorkOrderSettings::START_TIME => '08:00',
            WorkOrderSettings::SKIP_WEEKEND => true,
            WorkOrderSettings::BACKFILL_DAYS => 0,
            ...$settings,
        ]);
    }

    public function test_auto_generate_is_disabled_by_default(): void
    {
        $this->assertFalse(WorkOrderSettings::autoGenerate());
        $this->assertSame(0, WorkOrderSettings::backfillDays());
    }

    public function test_command_does_nothing_while_toggle_is_off(): void
    {
        $this->artisan('work-orders:generate-scheduled')
            ->expectsOutputToContain('nonaktif')
            ->assertSuccessful();

        $this->assertSame(0, WorkOrder::count());
    }

    public function test_command_creates_work_orders_when_enabled(): void
    {
        $this->enableAutoGenerate();

        $this->artisan('work-orders:generate-scheduled')->assertSuccessful();

        // Senin 16 Nov s/d Kamis 19 Nov: 4 hari kerja.
        $daily = WorkOrder::where('interval', 'daily')->get();
        $monthly = WorkOrder::where('interval', 'monthly')->get();

        $this->assertCount(4, $daily);
        $this->assertCount(1, $monthly);

        $this->assertEqualsCanonicalizing(
            ['2026-11-16', '2026-11-17', '2026-11-18', '2026-11-19'],
            $daily->map(fn (WorkOrder $wo): string => $wo->start_at->toDateString())->all(),
        );

        $workOrder = $daily->first();

        $this->assertSame(WorkOrder::STATUSES['Pending'], $workOrder->status);
        $this->assertSame('MOS LTSA', $workOrder->fungsi);
        $this->assertSame('08:00', $workOrder->start_at->format('H:i'));
        $this->assertSame(SystemUser::id(), $workOrder->issued_by);
        $this->assertNull($workOrder->technician_coordinator_id);
        $this->assertSame(1, $workOrder->workOrderActivities()->count());
    }

    public function test_command_is_idempotent(): void
    {
        $this->enableAutoGenerate();

        $this->artisan('work-orders:generate-scheduled')->assertSuccessful();
        $firstCount = WorkOrder::count();

        $this->artisan('work-orders:generate-scheduled')->assertSuccessful();

        $this->assertSame($firstCount, WorkOrder::count());
    }

    public function test_command_skips_fungsi_without_active_user(): void
    {
        $this->enableAutoGenerate();
        $this->technician->update(['status' => false]);

        $this->artisan('work-orders:generate-scheduled')->assertSuccessful();

        $this->assertSame(0, WorkOrder::count());
    }

    public function test_command_skips_type_without_fungsi(): void
    {
        $this->enableAutoGenerate();
        $this->type->update(['fungsi' => null]);

        $this->artisan('work-orders:generate-scheduled')->assertSuccessful();

        $this->assertSame(0, WorkOrder::count());
    }

    public function test_skip_weekend_is_respected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 08:00:00')); // Jumat
        $this->enableAutoGenerate();

        $this->artisan('work-orders:generate-scheduled')->assertSuccessful();

        // Jumat 20 Nov + Senin 23 Nov (Sabtu/Minggu dilewati).
        $this->assertEqualsCanonicalizing(
            ['2026-11-20', '2026-11-23'],
            WorkOrder::where('interval', 'daily')
                ->get()
                ->map(fn (WorkOrder $wo): string => $wo->start_at->toDateString())
                ->all(),
        );
    }

    public function test_weekend_is_used_when_skip_weekend_is_off(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 08:00:00')); // Jumat
        $this->enableAutoGenerate([WorkOrderSettings::SKIP_WEEKEND => false]);

        $this->artisan('work-orders:generate-scheduled')->assertSuccessful();

        $this->assertEqualsCanonicalizing(
            ['2026-11-20', '2026-11-21', '2026-11-22', '2026-11-23'],
            WorkOrder::where('interval', 'daily')
                ->get()
                ->map(fn (WorkOrder $wo): string => $wo->start_at->toDateString())
                ->all(),
        );
    }

    public function test_backfill_fills_missed_schedules(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 08:00:00')); // Jumat
        $this->enableAutoGenerate([WorkOrderSettings::BACKFILL_DAYS => 3]);

        $this->artisan('work-orders:generate-scheduled')->assertSuccessful();

        // 17, 18, 19, 20, 23 Nov (weekend dilewati).
        $this->assertEqualsCanonicalizing(
            ['2026-11-17', '2026-11-18', '2026-11-19', '2026-11-20', '2026-11-23'],
            WorkOrder::where('interval', 'daily')
                ->get()
                ->map(fn (WorkOrder $wo): string => $wo->start_at->toDateString())
                ->all(),
        );
    }

    public function test_monthly_interval_creates_one_work_order_per_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-28 08:00:00')); // Sabtu
        $this->enableAutoGenerate([WorkOrderSettings::LEAD_DAYS => 5]);

        $this->artisan('work-orders:generate-scheduled')->assertSuccessful();

        // Hari kerja: 30 Nov, 1-3 Des. Satu WO bulanan untuk Nov dan Des.
        $months = WorkOrder::where('interval', 'monthly')
            ->get()
            ->map(fn (WorkOrder $wo): string => $wo->start_at->format('Y-m'))
            ->sort()
            ->values()
            ->all();

        $this->assertSame(['2026-11', '2026-12'], $months);
    }

    public function test_site_setting_generate_now_action_creates_work_orders(): void
    {
        $this->enableAutoGenerate();

        $page = new SiteSettings;
        $page->generateScheduledWorkOrders();

        $this->assertGreaterThan(0, WorkOrder::count());
    }

    public function test_site_setting_generate_now_action_respects_disabled_toggle(): void
    {
        $page = new SiteSettings;
        $page->generateScheduledWorkOrders();

        $this->assertSame(0, WorkOrder::count());
    }

    public function test_site_setting_generate_now_action_only_shown_when_enabled(): void
    {
        Gate::before(fn (): bool => true);
        $this->actingAs(User::factory()->create());

        Livewire::test(SiteSettings::class)
            ->assertOk()
            ->assertDontSee('Generate WO Sekarang');

        $this->enableAutoGenerate();

        Livewire::test(SiteSettings::class)
            ->assertOk()
            ->assertSee('Generate WO Sekarang');
    }

    public function test_generator_window_is_limited_to_lead_days(): void
    {
        $this->enableAutoGenerate();

        $result = app(WorkOrderGenerator::class)->generateScheduled(
            now()->startOfDay(),
            now()->addDays(1)->endOfDay(),
        );

        // 2 WO harian (16 & 17 Nov) + 1 WO bulanan (16 Nov).
        $this->assertSame(3, $result['created']);
        $this->assertSame(1, $result['types']);
        $this->assertSame(1, $result['equipment']);
    }
}
