<?php

namespace Tests\Feature;

use App\Filament\Pages\MaintenanceCalendar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class CalendarPageSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders(): void
    {
        Gate::before(fn () => true);
        $u = User::factory()->create();
        $this->actingAs($u);
        Livewire::test(MaintenanceCalendar::class)->assertOk()->assertSee('maintenance-calendar');
    }
}
