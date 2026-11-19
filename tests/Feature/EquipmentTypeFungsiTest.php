<?php

namespace Tests\Feature;

use App\Filament\Resources\EquipmentTypes\Pages\CreateEquipmentType;
use App\Filament\Resources\EquipmentTypes\Pages\EditEquipmentType;
use App\Models\EquipmentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class EquipmentTypeFungsiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn (): bool => true);
        $this->actingAs(User::factory()->create());
    }

    public function test_create_equipment_type_stores_fungsi(): void
    {
        Livewire::test(CreateEquipmentType::class)
            ->fillForm([
                'name' => 'Pompa',
                'code' => 'pompa',
                'status' => true,
                'fungsi' => 'MOS LTSA',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $type = EquipmentType::where('code', 'pompa')->firstOrFail();

        $this->assertSame('MOS LTSA', $type->fungsi);
    }

    public function test_edit_equipment_type_can_change_fungsi(): void
    {
        $type = EquipmentType::create([
            'code' => 'pompa',
            'name' => 'Pompa',
            'fungsi' => 'MOS LTSA',
            'status' => true,
        ]);

        Livewire::test(EditEquipmentType::class, ['record' => $type->getKey()])
            ->fillForm(['fungsi' => 'HSSE'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('HSSE', $type->fresh()->fungsi);
    }

    public function test_fungsi_users_relation_matches_users(): void
    {
        $user = User::factory()->create(['fungsi' => 'MOS TAD']);

        $type = EquipmentType::create([
            'code' => 'pompa',
            'name' => 'Pompa',
            'fungsi' => 'MOS TAD',
            'status' => true,
        ]);

        $this->assertSame([$user->id], $type->fungsiUsers()->pluck('users.id')->all());
    }
}
