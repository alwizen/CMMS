<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings;
use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\MaintenancePlan;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceSchedule;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkOrder;
use App\Support\DocumentNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentNumberTest extends TestCase
{
    use RefreshDatabase;

    protected Equipment $equipment;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();

        $this->user = User::factory()->create();

        $company = Company::create(['code' => 'CO', 'name' => 'Demo Company']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'AR', 'name' => 'Demo Area']);
        $type = EquipmentType::create(['code' => 'MOV', 'name' => 'MOV']);

        $this->equipment = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $type->id,
            'tag_number' => 'MOV-TGL-502B',
        ]);
    }

    public function test_preview_resolves_date_tokens_and_sequence_padding(): void
    {
        $this->assertSame(
            'WO-'.now()->format('Ymd').'-0007',
            DocumentNumber::preview('WO-{YYYY}{MM}{DD}-{SEQ}', 7),
        );

        $this->assertSame(
            now()->format('M').'-000042',
            DocumentNumber::preview('{MONTH}-{SEQ:6}', 42),
        );
    }

    public function test_setting_model_stores_and_reads_values(): void
    {
        Setting::set('numbering.work_order.format', 'WO/{YYYY}/{SEQ:5}');

        $this->assertSame('WO/{YYYY}/{SEQ:5}', Setting::get('numbering.work_order.format'));
        $this->assertSame('fallback', Setting::get('missing.key', 'fallback'));
    }

    public function test_work_order_uses_configured_format_and_increments_sequence(): void
    {
        DocumentNumber::setFormat('work_order', 'SPK/{YYYY}/{SEQ:3}');

        $first = WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->user->id,
            'classification' => 'Preventive',
            'start_at' => now(),
            'status' => 'Pending',
        ]);

        $second = WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->user->id,
            'classification' => 'Preventive',
            'start_at' => now(),
            'status' => 'Pending',
        ]);

        $this->assertSame('SPK/'.now()->format('Y').'/001', $first->work_order_number);
        $this->assertSame('SPK/'.now()->format('Y').'/002', $second->work_order_number);
    }

    public function test_plan_schedule_and_request_numbers_are_generated(): void
    {
        $plan = MaintenancePlan::create([
            'equipment_id' => $this->equipment->id,
            'maintenance_classification' => 'Preventive',
            'interval' => 'monthly',
            'start_date' => now()->toDateString(),
            'created_by' => $this->user->id,
            'status' => 'Active',
        ]);

        $schedule = MaintenanceSchedule::create([
            'maintenance_plan_id' => $plan->id,
            'equipment_id' => $this->equipment->id,
            'scheduled_date' => now()->toDateString(),
            'status' => MaintenanceSchedule::STATUSES['Scheduled'],
        ]);

        $request = MaintenanceRequest::create([
            'equipment_id' => $this->equipment->id,
            'reported_by' => $this->user->id,
            'operation_status' => 'running',
            'description' => 'Kebocoran seal',
            'damage_date' => now()->toDateString(),
            'status' => 'Open',
        ]);

        $this->assertStringStartsWith('MP-', $plan->plan_number);
        $this->assertStringStartsWith('MS-', $schedule->schedule_number);
        $this->assertStringStartsWith('MR-', $request->request_number);
    }

    public function test_invalid_format_falls_back_to_default(): void
    {
        DocumentNumber::setFormat('work_order', 'TANPA-SEQUENCE');

        $this->assertSame(DocumentNumber::DEFAULTS['work_order'], DocumentNumber::format('work_order'));
    }

    public function test_site_settings_page_is_reachable(): void
    {
        config(['app.env' => 'local']);
        Gate::before(fn (): bool => true);

        $this->actingAs($this->user)
            ->get('/admin/site-settings')
            ->assertSuccessful()
            ->assertSee('Site Setting');
    }

    public function test_site_settings_page_saves_formats(): void
    {
        config(['app.env' => 'local']);
        Gate::before(fn (): bool => true);

        $this->actingAs($this->user);

        Livewire::test(SiteSettings::class)
            ->assertOk()
            ->fillForm([
                'work_order' => 'WO/{YY}/{SEQ:4}',
                'maintenance_plan' => 'PLAN/{YYYY}/{SEQ:4}',
                'maintenance_schedule' => 'SCH/{YYYY}/{SEQ:4}',
                'maintenance_request' => 'REQ/{YYYY}/{SEQ:4}',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('WO/{YY}/{SEQ:4}', Setting::get(DocumentNumber::settingKey('work_order')));
        $this->assertSame('PLAN/{YYYY}/{SEQ:4}', DocumentNumber::format('maintenance_plan'));
    }

    public function test_site_settings_page_rejects_format_without_sequence_token(): void
    {
        config(['app.env' => 'local']);
        Gate::before(fn (): bool => true);

        $this->actingAs($this->user);

        Livewire::test(SiteSettings::class)
            ->fillForm([
                'work_order' => 'TANPA-SEQ',
            ])
            ->call('save')
            ->assertHasFormErrors(['work_order']);
    }
}
