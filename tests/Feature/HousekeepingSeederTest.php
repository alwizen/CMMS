<?php

namespace Tests\Feature;

use App\Models\HousekeepingArea;
use App\Models\HousekeepingManpower;
use Database\Seeders\CompanySeeder;
use Database\Seeders\HousekeepingSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HousekeepingSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            UserSeeder::class,
            CompanySeeder::class,
            HousekeepingSeeder::class,
        ]);
    }

    public function test_it_seeds_five_areas_with_expected_manpowers(): void
    {
        $this->assertSame(5, HousekeepingArea::count());

        $expected = [
            'Penerimaan' => ['TARWO', 'AWALUDIN', 'FARHAN WIDIDO', 'SHAHRUL AMIR M'],
            'Penimbunan' => ['SLAMET KAMBALI', 'IMAM PAOZI', 'QOLBI SABILI', 'IMAM TRI PURWANTO'],
            'Penyaluran' => ['ANGGI PUTRA LUKI', 'FAJAR ISMAIL', 'UJANG MARGONO', 'WINGGI PRIYANTO'],
            'TPS Barata' => ['ANDI SEPTIONO', 'BIMA BUDIYASA', 'DICKY PRAYOGI', 'ALPIN ABI H', 'AMAR BAYU'],
            'Area Lainnya' => ['A HOFUR', 'WAHYU FEBIYANTO', 'RASMAN'],
        ];

        foreach ($expected as $name => $manpowers) {
            $area = HousekeepingArea::where('name', $name)->firstOrFail();

            $this->assertSame(14, $area->sla_days);
            $this->assertSame('outdoor', $area->kpi_class);

            $this->assertSame(
                $manpowers,
                $area->manpowers()->orderBy('id')->pluck('name')->all(),
            );
        }

        $this->assertSame(20, HousekeepingManpower::count());
    }

    public function test_it_is_idempotent(): void
    {
        $this->seed(HousekeepingSeeder::class);

        $this->assertSame(5, HousekeepingArea::count());
        $this->assertSame(20, HousekeepingManpower::count());
    }
}
