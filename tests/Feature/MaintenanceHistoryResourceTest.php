<?php

namespace Tests\Feature;

use App\Filament\Resources\MaintenanceHistories\Pages\ViewMaintenanceHistory;
use App\Models\Activity;
use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\MaintenanceHistory;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class MaintenanceHistoryResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.env' => 'local']);
        Gate::before(fn (): bool => true);

        $this->user = User::factory()->create();

        $company = Company::create(['code' => 'CO', 'name' => 'Demo Company']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'AR', 'name' => 'Demo Area']);
        $type = EquipmentType::create(['code' => 'pompa', 'name' => 'Pompa']);

        $this->equipment = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $type->id,
            'tag_number' => 'P-101',
        ]);
    }

    public function test_maintenance_history_view_page_renders(): void
    {
        $history = MaintenanceHistory::create([
            'equipment_id' => $this->equipment->id,
            'maintenance_type' => 'Preventive',
            'classification' => 'Preventive',
            'description' => 'Pengecekan rutin pompa',
            'maintenance_date' => now(),
            'performed_by' => $this->user->id,
            'findings' => 'Bearing aus ringan',
            'actions_taken' => 'Pelumasan ulang',
            'status' => 'Completed',
            'duration_minutes' => 90,
            'notes' => 'Pantau pada inspeksi berikutnya',
        ]);

        $this->actingAs($this->user)
            ->get("/admin/maintenance-histories/{$history->id}")
            ->assertSuccessful();
    }

    public function test_maintenance_history_view_shows_checklist_from_related_work_order(): void
    {
        $workOrder = WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->user->id,
            'classification' => 'Preventive',
            'start_at' => now()->subHours(2),
            'finish_at' => now(),
            'status' => 'Completed',
        ]);

        $activity = Activity::create([
            'equipment_type_id' => $this->equipment->equipment_type_id,
            'name' => 'Inspeksi Kebocoran Seal',
            'type' => 'visual_check',
            'maintenance_classification' => 'preventive',
            'interval' => 'monthly',
            'answer_type' => 'qualitative',
            'status' => true,
        ]);

        WorkOrderActivity::create([
            'work_order_id' => $workOrder->id,
            'activity_id' => $activity->id,
            'reference' => 'REF-01',
            'pre_inspection' => 'Kondisi bersih',
            'final_result' => 'Tidak ada kebocoran',
            'follow_up' => 'Tidak perlu tindak lanjut',
            'executed' => true,
            'note' => 'Selesai dikerjakan',
        ]);

        $history = MaintenanceHistory::create([
            'equipment_id' => $this->equipment->id,
            'work_order_id' => $workOrder->id,
            'work_order_number' => $workOrder->work_order_number,
            'maintenance_type' => 'Preventive',
            'classification' => 'Preventive',
            'description' => 'Pengecekan rutin pompa',
            'maintenance_date' => now(),
            'performed_by' => $this->user->id,
            'status' => 'Completed',
        ]);

        $this->actingAs($this->user);

        Livewire::test(ViewMaintenanceHistory::class, ['record' => $history->getRouteKey()])
            ->assertOk()
            ->assertSee('Inspeksi Kebocoran Seal')
            ->assertSee('Tidak ada kebocoran')
            ->assertSee('Selesai dikerjakan');
    }
}
