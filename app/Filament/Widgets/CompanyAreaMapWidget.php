<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\MaintenanceHistories\MaintenanceHistoryResource;
use App\Models\Area;
use App\Models\Company;
use Filament\Widgets\Widget;

class CompanyAreaMapWidget extends Widget
{
    protected string $view = 'filament.widgets.company-area-map';

    protected static ?string $heading = 'Peta Area';

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
    public function getCompaniesProperty(): array
    {
        $colors = ['amber', 'blue', 'green', 'red', 'purple', 'cyan', 'orange', 'pink'];

        return Company::query()
            ->where('is_active', true)
            ->whereNotNull('geofence')
            ->with(['areas' => function ($query) {
                $query->where('is_active', true)
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->withCount('equipment');
            }])
            ->get()
            ->values()
            ->map(function (Company $company, int $index) use ($colors) {
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'code' => $company->code,
                    'geofence' => $company->geofence,
                    'color' => $colors[$index % count($colors)],
                    'areas' => $company->areas->map(fn ($area) => [
                        'id' => $area->id,
                        'name' => $area->name,
                        'code' => $area->code,
                        'lat' => (float) $area->latitude,
                        'lng' => (float) $area->longitude,
                        'equipment_count' => $area->equipment_count,
                    ])->values()->all(),
                ];
            })
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getAreaEquipment(int $areaId): ?array
    {
        $area = Area::query()
            ->with('company:id,name')
            ->find($areaId);

        if (! $area) {
            return null;
        }

        $equipments = $area->equipment()
            ->select('id', 'tag_number', 'status', 'criticality')
            ->orderBy('tag_number')
            ->limit(20)
            ->get()
            ->map(fn ($equipment) => [
                'tag_number' => $equipment->tag_number,
                'status' => $equipment->status,
                'criticality' => $equipment->criticality,
            ])
            ->all();

        return [
            'area' => $area->name,
            'company' => $area->company?->name,
            'total' => $area->equipment()->count(),
            'equipments' => $equipments,
            'history_url' => MaintenanceHistoryResource::getUrl('index', [
                'tableFilters' => ['area_id' => ['value' => $area->id]],
            ]),
        ];
    }
}
