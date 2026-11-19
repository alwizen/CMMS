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
use App\Models\HousekeepingPlanFacility;
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
            'approval_status' => HousekeepingPlan::APPROVAL_APPROVED,
            'approved_at' => now(),
        ]);
        HousekeepingPlanTask::create(['housekeeping_plan_id' => $plan->id, 'name' => 'Sapu', 'status' => 'Done']);
        HousekeepingPlanTask::create(['housekeeping_plan_id' => $plan->id, 'name' => 'Pel', 'status' => 'Pending']);

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
        Storage::disk('public')->put('housekeeping/plan-facilities/before/a.jpg', UploadedFile::fake()->image('a.jpg')->getContent());
        Storage::disk('public')->put('housekeeping/plan-facilities/before/b.jpg', UploadedFile::fake()->image('b.jpg')->getContent());

        $uri = ImageData::thumbnailDataUri('housekeeping/plan-facilities/before/a.jpg', 160);

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
                'housekeeping/plan-facilities/before/a.jpg',
                'housekeeping/plan-facilities/before/b.jpg',
            ], 4)),
        );

        $this->assertNull(ImageData::thumbnailDataUri('housekeeping/plan-facilities/before/missing.jpg'));
        $this->assertNull(ImageData::thumbnailDataUri(null));
    }

    public function test_housekeeping_pdf_embeds_before_after_photos(): void
    {
        Storage::fake('public');

        $before = UploadedFile::fake()->image('before.jpg')->store('housekeeping/plan-facilities/before', 'public');
        $after = UploadedFile::fake()->image('after.jpg')->store('housekeeping/plan-facilities/after', 'public');

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
        ]);

        // Approval diberikan setelah pekerjaan tersusun: perubahan checklist
        // membatalkan approval sebelumnya.
        $plan->update([
            'approval_status' => HousekeepingPlan::APPROVAL_APPROVED,
            'approved_at' => now(),
        ]);

        HousekeepingPlanFacility::create([
            'housekeeping_plan_id' => $plan->id,
            'photo_before' => [$before],
            'photo_after' => [$after],
        ]);

        $report = app(DailyReport::class)->build('2026-11-16');

        $html = view('pdf.daily-report', ['report' => $report, 'scope' => 'housekeeping'])->render();

        $this->assertSame(2, substr_count($html, 'data:image/'), 'Foto before & after harus tampil di laporan.');
        $this->assertStringContainsString('Foto Before', $html);
        $this->assertStringContainsString('Foto After', $html);
        $this->assertStringNotContainsString('Seluruh pekerjaan hari ini', $html);

        // Pekerjaan & dokumentasi foto digabung dalam satu tabel: header kolom
        // foto ada di tabel pekerjaan, bukan tabel terpisah.
        $this->assertStringContainsString('task-table', $html);
        $this->assertStringNotContainsString('facility-doc-title', $html);
        $this->assertSame(2, substr_count($html, 'rowspan='), 'Foto fasilitas hanya dirender sekali per grup.');

        $this->actingAs($this->user);

        $response = $this->get('/admin/daily-report/pdf?date=2026-11-16&scope=housekeeping')->assertOk();

        $this->assertStringContainsString('/Image', $response->getContent(), 'PDF harus memuat objek gambar.');
    }

    public function test_plan_detail_merges_tasks_and_photos_in_one_table(): void
    {
        Storage::fake('public');

        $before = UploadedFile::fake()->image('before.jpg')->store('housekeeping/plan-facilities/before', 'public');
        $after = UploadedFile::fake()->image('after.jpg')->store('housekeeping/plan-facilities/after', 'public');

        $area = HousekeepingArea::create([
            'company_id' => $this->equipment->area->company_id,
            'code' => 'gabung',
            'name' => 'Area Gabung',
        ]);

        $documented = HousekeepingFacility::create([
            'housekeeping_area_id' => $area->id,
            'name' => 'Fasilitas Terdokumentasi',
        ]);
        $withoutDocs = HousekeepingFacility::create([
            'housekeeping_area_id' => $area->id,
            'name' => 'Fasilitas Tanpa Dokumentasi',
        ]);

        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $area->id,
            'plan_date' => '2026-11-16',
            'status' => 'Draft',
        ]);

        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'housekeeping_facility_id' => $documented->id,
            'name' => 'Pekerjaan A',
            'status' => 'Done',
        ]);
        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'housekeeping_facility_id' => $documented->id,
            'name' => 'Pekerjaan B',
            'status' => 'In Progress',
        ]);
        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'name' => 'Pekerjaan Tanpa Fasilitas',
            'status' => 'Pending',
        ]);

        // Baris dokumentasi fasilitas dibuat otomatis saat checklist dibuat,
        // jadi foto diunggah dengan memperbarui baris tersebut.
        HousekeepingPlanFacility::query()
            ->where('housekeeping_plan_id', $plan->id)
            ->where('housekeeping_facility_id', $documented->id)
            ->update([
                'photo_before' => [$before],
                'photo_after' => [$after],
            ]);

        // Fasilitas terdokumentasi tetapi belum punya checklist.
        HousekeepingPlanFacility::create([
            'housekeeping_plan_id' => $plan->id,
            'housekeeping_facility_id' => $withoutDocs->id,
        ]);

        $report = app(DailyReport::class)->build('2026-11-16');
        $html = view('pdf.daily-report', ['report' => $report, 'scope' => 'housekeeping'])->render();

        // Satu tabel pekerjaan yang memuat kolom foto.
        $this->assertStringContainsString('task-table', $html);
        $this->assertStringContainsString('Foto Before', $html);
        $this->assertStringContainsString('Foto After', $html);
        $this->assertStringNotContainsString('facility-doc-title', $html);

        // Foto dirender sekali per fasilitas meskipun fasilitas punya 2 checklist.
        $this->assertSame(2, substr_count($html, 'data:image/'));

        // Semua pekerjaan dan fasilitas muncul.
        $this->assertStringContainsString('Pekerjaan A', $html);
        $this->assertStringContainsString('Pekerjaan B', $html);
        $this->assertStringContainsString('Pekerjaan Tanpa Fasilitas', $html);
        $this->assertStringContainsString('Fasilitas Terdokumentasi', $html);
        $this->assertStringContainsString('Fasilitas Tanpa Dokumentasi', $html);

        // Fasilitas dengan 2 checklist memakai rowspan=2 pada sel foto.
        $this->assertStringContainsString('rowspan="2"', $html);
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
