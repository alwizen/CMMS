<?php

namespace App\Filament\Widgets;

use App\Models\HousekeepingArea;
use Filament\Widgets\Widget;

class HousekeepingAreaMapWidget extends Widget
{
    protected string $view = 'filament.widgets.housekeeping-area-map';

    protected static ?string $heading = 'Peta Area Housekeeping';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    /**
     * Widget ini standalone: tidak terpengaruh filter dashboard.
     *
     * @var array<string, mixed>|null
     */
    public ?array $filters = null;

    public function mount(): void
    {
        $this->filters = [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAreasProperty(): array
    {
        $colors = ['amber', 'blue', 'green', 'red', 'purple', 'cyan', 'orange', 'pink'];

        return HousekeepingArea::query()
            ->where('is_active', true)
            ->with(['company:id,name', 'facilities' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
            ->withCount(['facilities' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('name')
            ->get()
            ->values()
            ->map(function (HousekeepingArea $area, int $index) use ($colors) {
                return [
                    'id' => $area->id,
                    'name' => $area->name,
                    'code' => $area->code,
                    'company' => $area->company?->name,
                    'polygon' => $area->polygon,
                    'area_size' => $area->area_size,
                    'lat' => $area->latitude !== null ? (float) $area->latitude : null,
                    'lng' => $area->longitude !== null ? (float) $area->longitude : null,
                    'color' => $colors[$index % count($colors)],
                    'facilities_count' => $area->facilities_count,
                    'facilities' => $area->facilities->map(fn ($facility) => [
                        'id' => $facility->id,
                        'name' => $facility->name,
                        'code' => $facility->code,
                    ])->values()->all(),
                ];
            })
            ->all();
    }
}
