<?php

namespace Tests\Feature;

use App\Filament\Resources\HousekeepingAreas\Pages\EditHousekeepingArea;
use App\Filament\Resources\HousekeepingAreas\RelationManagers\FacilitiesRelationManager;
use App\Models\Company;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingFacility;
use App\Models\HousekeepingTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HousekeepingFacilityChecklistTest extends TestCase
{
    use RefreshDatabase;

    protected HousekeepingArea $area;

    protected HousekeepingFacility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn (): bool => true);

        $supervisor = User::factory()->create(['status' => true]);
        $supervisor->assignRole(Role::findOrCreate('spv_gos', 'web'));
        $this->actingAs($supervisor);

        $company = Company::create(['code' => 'HK', 'name' => 'HK Co']);
        $this->area = HousekeepingArea::create([
            'company_id' => $company->id,
            'code' => 'taman',
            'name' => 'Taman Kantor',
        ]);

        $this->facility = HousekeepingFacility::create([
            'housekeeping_area_id' => $this->area->id,
            'name' => 'Selokan Depan',
        ]);
    }

    protected function facilityRelationManager()
    {
        return Livewire::test(FacilitiesRelationManager::class, [
            'ownerRecord' => $this->area,
            'pageClass' => EditHousekeepingArea::class,
        ]);
    }

    public function test_checklist_action_is_available_on_facility_row(): void
    {
        $this->facilityRelationManager()->assertTableActionVisible('checklist', $this->facility);
    }

    public function test_checklist_action_is_hidden_for_executor_role(): void
    {
        $executor = User::factory()->create(['status' => true]);
        $executor->assignRole(Role::findOrCreate('patra-jasa', 'web'));
        $this->actingAs($executor);

        $this->facilityRelationManager()->assertTableActionHidden('checklist', $this->facility);
    }

    public function test_checklist_action_creates_task_for_facility(): void
    {
        $this->facilityRelationManager()
            ->mountTableAction('checklist', $this->facility)
            ->setTableActionData([
                'name' => 'Pembersihan Selokan',
                'description' => 'Rutin',
                'is_active' => true,
            ])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('housekeeping_tasks', [
            'housekeeping_facility_id' => $this->facility->id,
            'name' => 'Pembersihan Selokan',
            'description' => 'Rutin',
        ]);
    }

    public function test_checklist_modal_exposes_create_another_button(): void
    {
        $component = $this->facilityRelationManager()
            ->mountTableAction('checklist', $this->facility);

        $action = $component->instance()->getMountedAction();

        $this->assertNotNull($action);
        $this->assertTrue(
            $action->canCreateAnother(),
            'Tombol "Buat & buat lagi" bawaan Filament harus aktif.',
        );
    }

    public function test_create_another_saves_task_and_keeps_modal_open(): void
    {
        $this->facilityRelationManager()
            ->mountTableAction('checklist', $this->facility)
            ->setTableActionData([
                'name' => 'Checklist Pertama',
                'is_active' => true,
            ])
            ->callMountedTableAction(['another' => true])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('housekeeping_tasks', [
            'housekeeping_facility_id' => $this->facility->id,
            'name' => 'Checklist Pertama',
        ]);
    }

    public function test_create_another_can_be_used_repeatedly(): void
    {
        $component = $this->facilityRelationManager()
            ->mountTableAction('checklist', $this->facility)
            ->setTableActionData([
                'name' => 'Checklist Satu',
                'is_active' => true,
            ])
            ->callMountedTableAction(['another' => true])
            ->assertHasNoTableActionErrors();

        // Modal tetap terbuka setelah "Buat & buat lagi".
        $this->assertNotNull($component->instance()->getMountedAction());

        $component
            ->setTableActionData([
                'name' => 'Checklist Dua',
                'is_active' => true,
            ])
            ->callMountedTableAction(['another' => true])
            ->assertHasNoTableActionErrors();

        $this->assertSame(
            ['Checklist Satu', 'Checklist Dua'],
            $this->facility->tasks()->orderBy('id')->pluck('name')->all(),
        );
    }

    public function test_checklist_modal_shows_existing_tasks(): void
    {
        HousekeepingTask::create([
            'housekeeping_facility_id' => $this->facility->id,
            'name' => 'Cek Saluran',
            'is_active' => true,
        ]);

        $this->facilityRelationManager()
            ->mountTableAction('checklist', $this->facility)
            ->assertMountedActionModalSee('Cek Saluran');
    }

    public function test_facility_table_shows_tasks_count(): void
    {
        HousekeepingTask::create([
            'housekeeping_facility_id' => $this->facility->id,
            'name' => 'Tugas A',
            'is_active' => true,
        ]);

        $this->assertSame(1, $this->facility->tasks()->count());
    }
}
