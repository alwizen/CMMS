<?php

namespace App\Filament\Resources\KpiSettings\Pages;

use App\Filament\Resources\KpiSettings\KpiSettingResource;
use App\Models\KpiMetric;
use App\Support\Kpi\KpiRegistry;
use Filament\Resources\Pages\ListRecords;

class ListKpiSettings extends ListRecords
{
    protected static string $resource = KpiSettingResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->seedDefaults();
    }

    /**
     * Pastikan setiap metrik di katalog punya baris konfigurasi.
     */
    protected function seedDefaults(): void
    {
        foreach (KpiRegistry::DEFINITIONS as $source => $definitions) {
            foreach ($definitions as $key => $definition) {
                KpiMetric::query()->firstOrCreate(
                    ['source' => $source, 'key' => $key],
                    [
                        'target_value' => $definition['target'],
                        'weight' => $definition['weight'],
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
