<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\HousekeepingDashboard;
use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingFacility;
use App\Models\HousekeepingPlan;
use App\Models\HousekeepingPlanTask;
use App\Models\MaintenanceRequest;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderActivity;
use App\Support\Report\DailyReport;
use App\Support\Report\ImageData;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DailyReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();
        Carbon::setTestNow(Carbon::parse('2026-11-16 10:00:00'));

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findOrCreate('super_admin', 'web'));

        $company = Company::create(['code' => 'CO', 'name' => 'Demo']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'AR', 'name' => 'Area']);
        $type = EquipmentType::create(['code' => 'pump', 'name' => 'Pompa']);

        $this->equipment = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $type->id,
            'tag_number' => 'P-101',
        ]);
    }

    public function test_builder_summarizes_maintenance_housekeeping_and_requests(): void
    {
        $completed = WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->user->id,
            'technician_coordinator_id' => $this->user->id,
            'classification' => 'Preventive',
            'start_at' => '2026-11-16 08:00:00',
            'finish_at' => '2026-11-16 09:00:00',
            'status' => WorkOrder::STATUSES['Completed'],
        ]);
        WorkOrderActivity::create([
            'work_order_id' => $completed->id,
            'name' => 'Cek tekanan',
            'executed' => true,
        ]);

        WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->user->id,
            'technician_coordinator_id' => $this->user->id,
            'classification' => 'Corrective',
            'start_at' => '2026-11-16 10:00:00',
            'status' => WorkOrder::STATUSES['In Progress'],
        ]);

        $hkArea = HousekeepingArea::create([
            'company_id' => $this->equipment->area->company_id,
            'code' => 'taman',
            'name' => 'Taman',
        ]);
        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $hkArea->id,
            'plan_date' => '2026-11-16',
            'status' => 'Draft',
        ]);
        HousekeepingPlanTask::create(['housekeeping_plan_id' => $plan->id, 'name' => 'Sapu', 'status' => 'Done']);
        HousekeepingPlanTask::create(['housekeeping_plan_id' => $plan->id, 'name' => 'Pel', 'status' => 'Pending']);

        // Approval diberikan setelah checklist tersusun: perubahan checklist
        // membatalkan approval sebelumnya.
        $plan->update([
            'approval_status' => HousekeepingPlan::APPROVAL_APPROVED,
            'approved_at' => now(),
        ]);

        MaintenanceRequest::create([
            'equipment_id' => $this->equipment->id,
            'reported_by' => $this->user->id,
            'operation_status' => 'Faulty',
            'description' => 'Bearing rusak',
            'damage_date' => '2026-11-16',
            'status' => MaintenanceRequest::STATUSES['Open'],
        ]);

        $report = app(DailyReport::class)->build('2026-11-16');

        $this->assertSame(2, $report['maintenance']['total']);
        $this->assertSame(1, $report['maintenance']['completed']);
        $this->assertSame(1, $report['maintenance']['checklist_total']);
        $this->assertSame(1, $report['maintenance']['checklist_done']);
        $this->assertSame(50, $report['maintenance']['completion_rate']);

        $this->assertSame(1, $report['housekeeping']['plans']);
        $this->assertSame(2, $report['housekeeping']['tasks_total']);
        $this->assertSame(1, $report['housekeeping']['tasks_done']);
        $this->assertSame(50, $report['housekeeping']['completion_rate']);

        $this->assertSame(1, $report['requests']['maintenance_total']);
        $this->assertSame(1, $report['requests']['maintenance_open']);
    }

    public function test_housekeeping_report_includes_running_plans_and_completed_tasks(): void
    {
        $companyId = $this->equipment->area->company_id;

        $area = HousekeepingArea::create([
            'company_id' => $companyId,
            'code' => 'luar',
            'name' => 'Taman Luar',
            'sla_days' => 14,
            'kpi_class' => 'outdoor',
        ]);

        $started = HousekeepingPlan::create([
            'housekeeping_area_id' => $area->id,
            'plan_date' => '2026-11-16',
            'status' => 'In Progress',
        ]);
        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $started->id,
            'name' => 'Sapu Pagi',
            'status' => 'Done',
            'completed_at' => '2026-11-16 08:00:00',
        ]);

        $running = HousekeepingPlan::create([
            'housekeeping_area_id' => $area->id,
            'plan_date' => '2026-11-05',
            'status' => 'In Progress',
        ]);
        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $running->id,
            'name' => 'Potong Rumput',
            'status' => 'In Progress',
        ]);

        $finished = HousekeepingPlan::create([
            'housekeeping_area_id' => $area->id,
            'plan_date' => '2026-11-02',
            'status' => 'Completed',
        ]);
        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $finished->id,
            'name' => 'Bersih Selokan',
            'status' => 'Done',
            'completed_at' => '2026-11-16 10:00:00',
        ]);

        $old = HousekeepingPlan::create([
            'housekeeping_area_id' => $area->id,
            'plan_date' => '2026-10-01',
            'status' => 'In Progress',
        ]);
        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $old->id,
            'name' => 'Plan Lampau',
            'status' => 'In Progress',
        ]);

        // Approval diberikan setelah checklist tersusun (perubahan checklist
        // membatalkan approval sebelumnya).
        foreach ([$started, $running, $finished, $old] as $approvedPlan) {
            $approvedPlan->update([
                'approval_status' => HousekeepingPlan::APPROVAL_APPROVED,
                'approved_at' => now(),
            ]);
        }

        $report = app(DailyReport::class)->build('2026-11-16');
        $hk = $report['housekeeping'];

        // Plan dimulai hari ini (statusnya sudah Completed karena satu-satunya
        // task Done).
        $this->assertSame([$started->id], $hk['plans_started']->pluck('id')->all());

        // Semua plan yang masih berjalan ikut tampil, termasuk yang sudah lewat
        // SLA, agar pekerjaan tidak "hilang" dari laporan harian.
        $this->assertSame(
            [$old->id, $running->id],
            $hk['plans_active']->pluck('id')->all(),
        );

        // Plan yang selesai pada tanggal laporan. Plan `$started` hanya punya
        // satu task dan sudah Done, sehingga statusnya otomatis Completed.
        $this->assertSame(
            [$started->id, $finished->id],
            $hk['plans_completed_today']->pluck('id')->sort()->values()->all(),
        );

        // Task selesai hari ini dari plan mana pun (termasuk plan yang sudah selesai).
        $this->assertSame(
            ['Bersih Selokan', 'Sapu Pagi'],
            $hk['tasks_completed_today']->pluck('name')->sort()->values()->all(),
        );

        // Ringkasan memakai daftar pekerjaan hari ini (dipakai pada kartu
        // ringkasan PDF, bukan sebagai tabel tersendiri).
        $this->assertSame(
            ['Bersih Selokan', 'Plan Lampau', 'Potong Rumput', 'Sapu Pagi'],
            $hk['tasks_today']->pluck('name')->sort()->values()->all(),
        );
    }

    public function test_housekeeping_report_only_includes_approved_plans_and_groups_by_pj(): void
    {
        $companyId = $this->equipment->area->company_id;

        $pjA = User::factory()->create(['name' => 'PJ Alfa']);
        $pjB = User::factory()->create(['name' => 'PJ Bravo']);

        $areaA = HousekeepingArea::create([
            'company_id' => $companyId,
            'code' => 'pj-a',
            'name' => 'Area PJ Alfa',
            'supervisor_id' => $pjA->id,
        ]);
        $areaB = HousekeepingArea::create([
            'company_id' => $companyId,
            'code' => 'pj-b',
            'name' => 'Area PJ Bravo',
            'supervisor_id' => $pjB->id,
        ]);

        $approvedA = HousekeepingPlan::create([
            'housekeeping_area_id' => $areaA->id,
            'plan_date' => '2026-11-16',
            'status' => 'In Progress',
        ]);
        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $approvedA->id,
            'name' => 'Tugas PJ Alfa',
            'status' => 'In Progress',
        ]);
        $approvedA->update([
            'approval_status' => HousekeepingPlan::APPROVAL_APPROVED,
            'approved_at' => now(),
        ]);

        // Plan PJ lain yang belum disetujui: harus dilewati, bukan menahan laporan.
        $pendingB = HousekeepingPlan::create([
            'housekeeping_area_id' => $areaB->id,
            'plan_date' => '2026-11-16',
            'status' => 'In Progress',
        ]);
        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $pendingB->id,
            'name' => 'Tugas PJ Bravo',
            'status' => 'In Progress',
        ]);

        $report = app(DailyReport::class)->build('2026-11-16');
        $hk = $report['housekeeping'];

        $this->assertSame(1, $hk['plans']);
        $this->assertSame([$approvedA->id], $hk['plans_active']->pluck('id')->all());

        $groups = $hk['plans_by_supervisor'];
        $this->assertCount(1, $groups);
        $this->assertSame('PJ Alfa', $groups[0]['supervisor']);
        $this->assertSame([$approvedA->id], $groups[0]['active']->pluck('id')->all());

        $html = view('pdf.daily-report', ['report' => $report, 'scope' => 'housekeeping'])->render();

        $this->assertStringContainsString('PJ: PJ Alfa', $html);
        $this->assertStringContainsString('Tugas PJ Alfa', $html);
        $this->assertStringNotContainsString('Tugas PJ Bravo', $html);
    }

    public function test_report_uses_black_text_only(): void
    {
        $report = app(DailyReport::class)->build('2026-11-16');

        foreach (['housekeeping', 'maintenance', 'all'] as $scope) {
            $html = view('pdf.daily-report', ['report' => $report, 'scope' => $scope])->render();

            $this->assertStringNotContainsString('#015E4E', $html);
            $this->assertStringNotContainsString('#16a34a', $html);
            $this->assertStringNotContainsString('#2563eb', $html);
            $this->assertStringNotContainsString('#d97706', $html);
            $this->assertStringNotContainsString('#dc2626', $html);
        }
    }

    public function test_housekeeping_report_shows_ppn_logo_in_header(): void
    {
        $report = app(DailyReport::class)->build('2026-11-16');

        $html = view('pdf.daily-report', ['report' => $report, 'scope' => 'housekeeping'])->render();

        $this->assertStringContainsString('alt="PPN"', $html);
        $this->assertStringContainsString('header-logo', $html);
        $this->assertStringContainsString('data:image/png;base64,', $html);
    }

    public function test_maintenance_only_report_has_no_ppn_logo(): void
    {
        $report = app(DailyReport::class)->build('2026-11-16');

        $html = view('pdf.daily-report', ['report' => $report, 'scope' => 'maintenance'])->render();

        $this->assertStringNotContainsString('alt="PPN"', $html);
    }

    public function test_pdf_endpoint_returns_pdf_for_selected_date(): void
    {
        $this->actingAs($this->user);

        $response = $this->get('/admin/daily-report/pdf?date=2026-11-16');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_pdf_endpoint_supports_scope(): void
    {
        $this->actingAs($this->user);

        foreach (['maintenance', 'housekeeping'] as $scope) {
            $response = $this->get('/admin/daily-report/pdf?date=2026-11-16&scope='.$scope);

            $response->assertOk();
            $response->assertHeader('content-type', 'application/pdf');
        }
    }

    public function test_image_data_returns_data_uri_and_skips_missing(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('housekeeping/plan-tasks/before/a.jpg', UploadedFile::fake()->image('a.jpg')->getContent());

        $this->assertStringStartsWith('data:image/', (string) ImageData::dataUri('housekeeping/plan-tasks/before/a.jpg'));
        $this->assertNull(ImageData::dataUri('housekeeping/plan-tasks/before/missing.jpg'));
        $this->assertNull(ImageData::dataUri(null));
    }

    public function test_image_data_builds_square_thumbnail(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('housekeeping/plan-tasks/before/a.jpg', UploadedFile::fake()->image('a.jpg')->getContent());
        Storage::disk('public')->put('housekeeping/plan-tasks/before/b.jpg', UploadedFile::fake()->image('b.jpg')->getContent());

        $uri = ImageData::thumbnailDataUri('housekeeping/plan-tasks/before/a.jpg', 160);

        $this->assertNotNull($uri);
        $this->assertStringStartsWith('data:image/jpeg;base64,', (string) $uri);

        $decoded = base64_decode(substr((string) $uri, strlen('data:image/jpeg;base64,')));
        $image = imagecreatefromstring($decoded);

        $this->assertNotFalse($image);
        $this->assertSame(160, imagesx($image));
        $this->assertSame(160, imagesy($image));

        $this->assertSame(
            2,
            count(ImageData::thumbnailUris([
                'housekeeping/plan-tasks/before/a.jpg',
                'housekeeping/plan-tasks/before/b.jpg',
            ], 4)),
        );

        $this->assertNull(ImageData::thumbnailDataUri('housekeeping/plan-tasks/before/missing.jpg'));
        $this->assertNull(ImageData::thumbnailDataUri(null));
    }

    public function test_housekeeping_pdf_embeds_before_after_photos(): void
    {
        Storage::fake('public');

        $before = UploadedFile::fake()->image('before.jpg')->store('housekeeping/plan-tasks/before', 'public');
        $after = UploadedFile::fake()->image('after.jpg')->store('housekeeping/plan-tasks/after', 'public');

        $hkArea = HousekeepingArea::create([
            'company_id' => $this->equipment->area->company_id,
            'code' => 'taman',
            'name' => 'Taman',
        ]);
        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $hkArea->id,
            'plan_date' => '2026-11-16',
            'status' => 'Draft',
        ]);
        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Sapu',
            'status' => 'Done',
            'photo_before' => [$before],
            'photo_after' => [$after],
        ]);

        // Approval diberikan setelah pekerjaan tersusun: perubahan checklist
        // membatalkan approval sebelumnya.
        $plan->update([
            'approval_status' => HousekeepingPlan::APPROVAL_APPROVED,
            'approved_at' => now(),
        ]);

        $report = app(DailyReport::class)->build('2026-11-16');

        $html = view('pdf.daily-report', ['report' => $report, 'scope' => 'housekeeping'])->render();

        // Logo PPN di header + foto before & after.
        $this->assertSame(3, substr_count($html, 'data:image/'), 'Logo PPN dan foto before & after harus tampil di laporan.');
        $this->assertStringContainsString('Foto Before', $html);
        $this->assertStringContainsString('Foto After', $html);
        $this->assertStringNotContainsString('Seluruh pekerjaan hari ini', $html);

        // Pekerjaan & dokumentasi foto digabung dalam satu tabel: header kolom
        // foto ada di tabel pekerjaan, bukan tabel terpisah.
        $this->assertStringContainsString('task-table', $html);
        $this->assertStringNotContainsString('facility-doc-title', $html);

        $this->actingAs($this->user);

        $response = $this->get('/admin/daily-report/pdf?date=2026-11-16&scope=housekeeping')->assertOk();

        $this->assertStringContainsString('/Image', $response->getContent(), 'PDF harus memuat objek gambar.');
    }

    public function test_housekeeping_pdf_shows_approval_note_with_spv_gos_name(): void
    {
        $approver = User::factory()->create(['name' => 'Budi Santoso']);

        $area = HousekeepingArea::create([
            'company_id' => $this->equipment->area->company_id,
            'code' => 'approval',
            'name' => 'Area Approval',
        ]);

        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $area->id,
            'plan_date' => '2026-11-16',
            'status' => 'Draft',
        ]);

        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Sapu',
            'status' => 'Done',
        ]);

        $plan->update([
            'approval_status' => HousekeepingPlan::APPROVAL_APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        $report = app(DailyReport::class)->build('2026-11-16');

        $html = view('pdf.daily-report', ['report' => $report, 'scope' => 'housekeeping'])->render();

        $this->assertStringContainsString('Sudah di review dan disetujui oleh', $html);
        $this->assertStringContainsString('Budi Santoso', $html);
        $this->assertStringContainsString('(SPV GOS)', $html);
    }

    public function test_plan_detail_shows_photos_per_checklist(): void
    {
        Storage::fake('public');

        $before = UploadedFile::fake()->image('before.jpg')->store('housekeeping/plan-tasks/before', 'public');
        $after = UploadedFile::fake()->image('after.jpg')->store('housekeeping/plan-tasks/after', 'public');

        $area = HousekeepingArea::create([
            'company_id' => $this->equipment->area->company_id,
            'code' => 'gabung',
            'name' => 'Area Gabung',
        ]);

        $facility = HousekeepingFacility::create([
            'housekeeping_area_id' => $area->id,
            'name' => 'Fasilitas Satu',
        ]);

        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $area->id,
            'plan_date' => '2026-11-16',
            'status' => 'Draft',
        ]);

        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'housekeeping_facility_id' => $facility->id,
            'name' => 'Pekerjaan A',
            'status' => 'Done',
            'photo_before' => [$before],
            'photo_after' => [$after],
        ]);
        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'housekeeping_facility_id' => $facility->id,
            'name' => 'Pekerjaan B',
            'status' => 'In Progress',
        ]);
        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Pekerjaan Tanpa Fasilitas',
            'status' => 'Pending',
        ]);

        $plan->update([
            'approval_status' => HousekeepingPlan::APPROVAL_APPROVED,
            'approved_at' => now(),
        ]);

        $report = app(DailyReport::class)->build('2026-11-16');
        $html = view('pdf.daily-report', ['report' => $report, 'scope' => 'housekeeping'])->render();

        // Satu tabel pekerjaan yang memuat kolom foto.
        $this->assertStringContainsString('task-table', $html);
        $this->assertStringContainsString('Foto Before', $html);
        $this->assertStringContainsString('Foto After', $html);
        $this->assertStringNotContainsString('facility-doc-title', $html);

        // Logo PPN di header + hanya checklist Pekerjaan A yang punya foto.
        $this->assertSame(3, substr_count($html, 'data:image/'));

        // Semua pekerjaan muncul.
        $this->assertStringContainsString('Pekerjaan A', $html);
        $this->assertStringContainsString('Pekerjaan B', $html);
        $this->assertStringContainsString('Pekerjaan Tanpa Fasilitas', $html);
        $this->assertStringContainsString('Fasilitas Satu', $html);
    }

    public function test_both_dashboards_have_print_actions(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->user);

        Livewire::test(Dashboard::class)
            ->assertOk()
            ->assertActionExists('printDailyReport_maintenance');

        Livewire::test(HousekeepingDashboard::class)
            ->assertOk()
            ->assertActionExists('printDailyReport_housekeeping');
    }
}
