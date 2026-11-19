<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderActivity;
use App\Support\WorkOrderGenerator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderGeneratorDirectTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected EquipmentType $type;

    protected Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();
        Carbon::setTestNow(Carbon::parse('2026-11-16 00:00:00')); // Senin

        $this->user = User::factory()->create();

        $company = Company::create(['code' => 'CO', 'name' => 'Demo Company']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'AR', 'name' => 'Demo Area']);
        $this->user->update(['fungsi' => 'MOS LTSA']);

        $this->type = EquipmentType::create([
            'code' => 'mov',
            'name' => 'MOV',
            'fungsi' => 'MOS LTSA',
            'responsible_user_id' => $this->user->id,
        ]);

        $this->equipment = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $this->type->id,
            'tag_number' => 'MOV-1',
        ]);
    }

    public function test_generates_pending_work_orders_with_checklist(): void
    {
        $this->makeActivity('Cek A', 'monthly');
        $this->makeActivity('Cek B', 'monthly');

        $result = $this->generate(quantity: 2, interval: 'monthly');

        $this->assertSame(2, $result['created']);
        $this->assertSame(2, WorkOrder::count());

        $wo = WorkOrder::first();
        $this->assertSame(WorkOrder::STATUSES['Pending'], $wo->status);
        // WO dimiliki fungsi, performed by diisi saat mulai dikerjakan.
        $this->assertNull($wo->technician_coordinator_id);
        $this->assertSame('MOS LTSA', $wo->fungsi);
        $this->assertTrue($wo->isUnclaimed());
        $this->assertSame('monthly', $wo->interval);
        $this->assertSame(2, WorkOrderActivity::where('work_order_id', $wo->id)->count());
    }

    public function test_skips_equipment_without_responsible_user(): void
    {
        $this->makeActivity('Cek', 'monthly');
        $this->type->update(['responsible_user_id' => null]);
        $this->user->update(['fungsi' => null]);

        $result = $this->generate(fungsi: null);

        $this->assertSame(0, $result['created']);
        $this->assertContains('MOV-1', $result['skipped']);
        $this->assertSame(0, WorkOrder::count());
    }

    public function test_fungsi_selects_coordinator_and_sets_expired(): void
    {
        $this->makeActivity('Cek', 'monthly');

        // Pastikan hanya satu user pada fungsi ini sebagai koordinator terpilih.
        $this->user->update(['fungsi' => 'HSSE']);

        $koordinator = User::factory()->create([
            'name' => 'Koordinator LTSA',
            'fungsi' => 'MOS LTSA',
            'status' => true,
        ]);

        $result = $this->generate(quantity: 2, expiredDays: 7, fungsi: 'MOS LTSA', startDate: '2026-11-20');

        $this->assertSame(2, $result['created']);

        $wo = WorkOrder::orderBy('start_at')->first();
        $this->assertNull($wo->technician_coordinator_id);
        $this->assertSame('MOS LTSA', $wo->fungsi);
        $this->assertSame('2026-11-20', $wo->start_at->toDateString());
        $this->assertSame(7, $wo->expired_days);
        $this->assertSame('2026-11-27', $wo->expired_at->toDateString());
    }

    public function test_skips_when_fungsi_has_no_active_user(): void
    {
        $this->makeActivity('Cek', 'monthly');

        $this->user->update(['fungsi' => null]);

        $result = $this->generate(fungsi: 'HSSE');

        $this->assertSame(0, $result['created']);
        $this->assertArrayHasKey('MOV-1', $result['skip_reasons']);
    }

    public function test_skips_duplicate_work_order_for_same_period(): void
    {
        $this->makeActivity('Cek', 'monthly');

        $first = $this->generate(interval: 'monthly');
        $this->assertSame(1, $first['created']);

        $second = $this->generate(interval: 'monthly');

        $this->assertSame(0, $second['created']);
        $this->assertContains('MOV-1', $second['skipped']);
        $this->assertArrayHasKey('MOV-1', $second['duplicates']);
        $this->assertSame(1, WorkOrder::count());
    }

    public function test_allows_work_order_on_different_period(): void
    {
        $this->makeActivity('Cek', 'monthly');

        $this->generate(interval: 'monthly', startDate: '2026-11-10');
        $result = $this->generate(interval: 'monthly', startDate: '2026-12-10');

        $this->assertSame(1, $result['created']);
        $this->assertSame(2, WorkOrder::count());
        $this->assertSame([], $result['duplicates']);
    }

    public function test_daily_duplicate_only_blocks_same_day(): void
    {
        $this->makeActivity('Cek', 'daily');

        $this->generate(interval: 'daily', startDate: '2026-11-16', skipWeekend: false);
        $sameDay = $this->generate(interval: 'daily', startDate: '2026-11-16', skipWeekend: false);
        $nextDay = $this->generate(interval: 'daily', startDate: '2026-11-17', skipWeekend: false);

        $this->assertSame(0, $sameDay['created']);
        $this->assertSame(1, $nextDay['created']);
        $this->assertSame(2, WorkOrder::count());
    }

    public function test_can_force_duplicate_when_allowed(): void
    {
        $this->makeActivity('Cek', 'monthly');

        $this->generate(interval: 'monthly');
        $result = $this->generate(interval: 'monthly', allowDuplicate: true);

        $this->assertSame(1, $result['created']);
        $this->assertSame(2, WorkOrder::count());
    }

    public function test_cancelled_work_order_does_not_block_regenerate(): void
    {
        $this->makeActivity('Cek', 'monthly');

        $this->generate(interval: 'monthly');
        WorkOrder::query()->update(['status' => WorkOrder::STATUSES['Cancelled']]);

        $result = $this->generate(interval: 'monthly');

        $this->assertSame(1, $result['created']);
        $this->assertSame(2, WorkOrder::count());
    }

    public function test_skips_equipment_without_matching_checklist_interval(): void
    {
        $this->makeActivity('Cek', 'weekly');

        $result = $this->generate(interval: 'monthly');

        $this->assertSame(0, $result['created']);
        $this->assertContains('MOV-1', $result['skipped']);
        $this->assertSame(0, WorkOrder::count());
    }

    public function test_daily_interval_advances_and_skips_weekend(): void
    {
        $this->makeActivity('Cek', 'daily');

        $this->generate(quantity: 3, interval: 'daily', skipWeekend: true);

        $dates = WorkOrder::orderBy('start_at')->pluck('start_at')->map->toDateString()->all();

        // 2026-11-16 Senin, 17 Selasa, 18 Rabu (tanpa weekend di awal)
        $this->assertSame(['2026-11-16', '2026-11-17', '2026-11-18'], $dates);
    }

    public function test_weekend_is_skipped_on_start(): void
    {
        $this->makeActivity('Cek', 'daily');
        Carbon::setTestNow(Carbon::parse('2026-11-14 00:00:00')); // Sabtu

        $this->generate(quantity: 1, interval: 'daily', skipWeekend: true);

        $this->assertSame('2026-11-16', WorkOrder::first()->start_at->toDateString());
    }

    public function test_weekend_not_skipped_when_disabled(): void
    {
        $this->makeActivity('Cek', 'daily');
        Carbon::setTestNow(Carbon::parse('2026-11-14 00:00:00')); // Sabtu

        $this->generate(quantity: 1, interval: 'daily', skipWeekend: false);

        $this->assertSame('2026-11-14', WorkOrder::first()->start_at->toDateString());
    }

    public function test_monthly_interval_uses_distinct_months(): void
    {
        $this->makeActivity('Cek', 'monthly');

        $this->generate(quantity: 3, interval: 'monthly', skipWeekend: true);

        $dates = WorkOrder::orderBy('start_at')->pluck('start_at')->map->toDateString()->all();

        // Base Senin 16 Nov 2026 → 16 Des → 18 Jan (16 Jan 2027 Sabtu, digeser Senin 18 Jan)
        $this->assertSame(['2026-11-16', '2026-12-16', '2027-01-18'], $dates);
    }

    public function test_weekly_interval_adds_seven_days(): void
    {
        $this->makeActivity('Cek', 'weekly');

        $this->generate(quantity: 3, interval: 'weekly', skipWeekend: true);

        $dates = WorkOrder::orderBy('start_at')->pluck('start_at')->map->toDateString()->all();

        $this->assertSame(['2026-11-16', '2026-11-23', '2026-11-30'], $dates);
    }

    public function test_monthly_interval_does_not_overflow_end_of_month(): void
    {
        $this->makeActivity('Cek', 'monthly');
        Carbon::setTestNow(Carbon::parse('2026-01-31 00:00:00')); // Sabtu akhir bulan

        $this->generate(quantity: 2, interval: 'monthly', skipWeekend: true);

        $dates = WorkOrder::orderBy('start_at')->pluck('start_at')->map->toDateString()->all();

        // 31 Jan 2026 Sabtu → Senin 2 Feb; berikutnya 28 Feb 2026 (Sabtu? tidak, 28 Feb 2026 = Sabtu) → Senin 2 Mar
        $this->assertSame(['2026-02-02', '2026-03-02'], $dates);
    }

    /**
     * @return array{created: int, skipped: array<int, string>}
     */
    protected function generate(
        int $quantity = 1,
        string $interval = 'monthly',
        bool $skipWeekend = true,
        int $expiredDays = 0,
        ?string $fungsi = 'MOS LTSA',
        ?string $startDate = null,
        bool $allowDuplicate = false,
    ): array {
        return app(WorkOrderGenerator::class)->generateDirect(
            equipmentIds: [$this->equipment->id],
            interval: $interval,
            quantity: $quantity,
            startTime: '08:00',
            skipWeekend: $skipWeekend,
            issuedBy: $this->user->id,
            startDate: $startDate,
            expiredDays: $expiredDays,
            fungsi: $fungsi,
            allowDuplicate: $allowDuplicate,
        );
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
}
