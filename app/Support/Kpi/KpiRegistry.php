<?php

namespace App\Support\Kpi;

use App\Models\KpiMetric;

/**
 * Katalog metrik KPI yang didukung aplikasi.
 *
 * Definisi (label, unit, arah) dikunci di kode agar formula tidak bisa
 * diubah sembarangan dari UI. Yang dapat diatur lewat UI hanya target,
 * bobot, dan status aktif (disimpan di tabel `kpi_metrics`).
 */
class KpiRegistry
{
    public const SOURCE_MAINTENANCE = 'maintenance';

    public const SOURCE_HOUSEKEEPING = 'housekeeping';

    public const SOURCE_HOUSEKEEPING_INDOOR = 'housekeeping_indoor';

    public const SOURCE_HOUSEKEEPING_OUTDOOR = 'housekeeping_outdoor';

    public const HOUSEKEEPING_SOURCES = [
        self::SOURCE_HOUSEKEEPING_INDOOR,
        self::SOURCE_HOUSEKEEPING_OUTDOOR,
    ];

    public const OBJECT_USER = 'user';

    public const OBJECT_AREA = 'area';

    /**
     * @var array<string, array<string, array{label: string, unit: string, direction: string, target: float|int, weight: float|int, description: string, decimals: int}>>
     */
    public const DEFINITIONS = [
        self::SOURCE_MAINTENANCE => [
            'wo_completion_rate' => [
                'label' => 'WO Completion Rate',
                'unit' => '%',
                'direction' => 'higher',
                'target' => 90,
                'weight' => 30,
                'description' => 'WO selesai dibagi seluruh WO yang dikerjakan (performed by, tanpa Cancelled) pada periode.',
                'decimals' => 0,
            ],
            'wo_on_time_rate' => [
                'label' => 'On-Time Completion',
                'unit' => '%',
                'direction' => 'higher',
                'target' => 85,
                'weight' => 25,
                'description' => 'WO selesai dengan finish_at tidak melewati akhir hari start_at.',
                'decimals' => 0,
            ],
            'checklist_compliance' => [
                'label' => 'Checklist Compliance',
                'unit' => '%',
                'direction' => 'higher',
                'target' => 90,
                'weight' => 25,
                'description' => 'Item checklist yang tereksekusi dibagi seluruh item checklist WO.',
                'decimals' => 0,
            ],
            'preventive_ratio' => [
                'label' => 'Preventive Ratio',
                'unit' => '%',
                'direction' => 'higher',
                'target' => 70,
                'weight' => 10,
                'description' => 'WO preventive dibagi seluruh WO pada periode.',
                'decimals' => 0,
            ],
            'overdue_open_wo' => [
                'label' => 'WO Terlambat',
                'unit' => 'WO',
                'direction' => 'lower',
                'target' => 2,
                'weight' => 5,
                'description' => 'WO yang belum selesai padahal start_at sudah lewat (maksimal yang ditoleransi).',
                'decimals' => 0,
            ],
            'expired_wo' => [
                'label' => 'WO Expired',
                'unit' => 'WO',
                'direction' => 'lower',
                'target' => 2,
                'weight' => 5,
                'description' => 'WO yang melewati batas expired_at dan belum selesai (maksimal yang ditoleransi).',
                'decimals' => 0,
            ],
        ],
        self::SOURCE_HOUSEKEEPING_INDOOR => [
            'task_completion_rate' => [
                'label' => 'Task Completion Rate',
                'unit' => '%',
                'direction' => 'higher',
                'target' => 90,
                'weight' => 25,
                'description' => 'Item checklist berstatus Done dibagi seluruh item pada plan di periode.',
                'decimals' => 0,
            ],
            'sla_compliance' => [
                'label' => 'SLA Compliance',
                'unit' => '%',
                'direction' => 'higher',
                'target' => 90,
                'weight' => 25,
                'description' => 'Plan selesai tidak melewati batas SLA area (plan_date + SLA hari) dibagi plan selesai.',
                'decimals' => 0,
            ],
            'plan_consistency' => [
                'label' => 'Plan Consistency',
                'unit' => '%',
                'direction' => 'higher',
                'target' => 90,
                'weight' => 20,
                'description' => 'Jumlah hari pada periode yang memiliki plan harian dibagi jumlah hari periode.',
                'decimals' => 0,
            ],
            'documentation_compliance' => [
                'label' => 'Dokumentasi Foto',
                'unit' => '%',
                'direction' => 'higher',
                'target' => 95,
                'weight' => 15,
                'description' => 'Fasilitas pada plan yang memiliki foto after dibagi seluruh fasilitas terdokumentasi.',
                'decimals' => 0,
            ],
            'backlog' => [
                'label' => 'Backlog',
                'unit' => 'Task',
                'direction' => 'lower',
                'target' => 2,
                'weight' => 15,
                'description' => 'Task berstatus Pending atau In Progress pada plan di periode (maksimal yang ditoleransi).',
                'decimals' => 0,
            ],
        ],
        self::SOURCE_HOUSEKEEPING_OUTDOOR => [
            'task_completion_rate' => [
                'label' => 'Task Completion Rate',
                'unit' => '%',
                'direction' => 'higher',
                'target' => 90,
                'weight' => 25,
                'description' => 'Item checklist berstatus Done dibagi seluruh item pada plan di periode.',
                'decimals' => 0,
            ],
            'sla_compliance' => [
                'label' => 'SLA Compliance',
                'unit' => '%',
                'direction' => 'higher',
                'target' => 90,
                'weight' => 25,
                'description' => 'Plan selesai tidak melewati batas SLA area (plan_date + SLA hari) dibagi plan selesai.',
                'decimals' => 0,
            ],
            'sla_efficiency' => [
                'label' => 'SLA Efficiency',
                'unit' => '%',
                'direction' => 'higher',
                'target' => 80,
                'weight' => 20,
                'description' => 'Efisiensi waktu: SLA area dibagi jumlah hari penyelesaian plan (maksimal 100% per plan).',
                'decimals' => 0,
            ],
            'window_coverage' => [
                'label' => 'Window Coverage',
                'unit' => '%',
                'direction' => 'higher',
                'target' => 80,
                'weight' => 15,
                'description' => 'Hari pada periode yang tercakup plan (plan menutup SLA hari) dibagi jumlah hari periode.',
                'decimals' => 0,
            ],
            'documentation_compliance' => [
                'label' => 'Dokumentasi Foto',
                'unit' => '%',
                'direction' => 'higher',
                'target' => 95,
                'weight' => 15,
                'description' => 'Fasilitas pada plan yang memiliki foto after dibagi seluruh fasilitas terdokumentasi.',
                'decimals' => 0,
            ],
        ],
    ];

    /**
     * Definisi metrik beserta target/bobot efektif (gabungan default + override DB).
     *
     * @return array<string, array{label: string, unit: string, direction: string, target: float|int, weight: float|int, description: string, decimals: int, is_active: bool}>
     */
    public static function metrics(string $source): array
    {
        $source = self::normalizeSource($source);

        $definitions = self::DEFINITIONS[$source] ?? [];

        if ($definitions === []) {
            return [];
        }

        $overrides = KpiMetric::query()
            ->where('source', $source)
            ->get()
            ->keyBy('key');

        $result = [];

        foreach ($definitions as $key => $definition) {
            $override = $overrides->get($key);

            $result[$key] = [
                ...$definition,
                'target' => $override?->target_value ?? $definition['target'],
                'weight' => $override?->weight ?? $definition['weight'],
                'is_active' => $override?->is_active ?? true,
            ];
        }

        return array_filter($result, fn (array $metric): bool => $metric['is_active']);
    }

    /**
     * Semua metrik (termasuk nonaktif) untuk keperluan halaman konfigurasi.
     *
     * @return array<string, array{label: string, unit: string, direction: string, target: float|int, weight: float|int, description: string, decimals: int, is_active: bool}>
     */
    public static function allMetrics(string $source): array
    {
        $source = self::normalizeSource($source);

        $result = [];

        foreach (self::DEFINITIONS[$source] ?? [] as $key => $definition) {
            $result[$key] = [...$definition, 'is_active' => true];
        }

        $overrides = KpiMetric::query()
            ->where('source', $source)
            ->get()
            ->keyBy('key');

        foreach ($result as $key => $metric) {
            $override = $overrides->get($key);

            if (! $override) {
                continue;
            }

            $result[$key] = [
                ...$metric,
                'target' => $override->target_value,
                'weight' => $override->weight,
                'is_active' => $override->is_active,
            ];
        }

        return $result;
    }

    public static function isHousekeeping(string $source): bool
    {
        return in_array($source, self::HOUSEKEEPING_SOURCES, true) || $source === self::SOURCE_HOUSEKEEPING;
    }

    /**
     * Source housekeeping efektif. Source lama (`housekeeping`) dianggap outdoor
     * agar data/konfigurasi lama tetap terbaca.
     */
    public static function normalizeSource(string $source): string
    {
        return match ($source) {
            self::SOURCE_HOUSEKEEPING => self::SOURCE_HOUSEKEEPING_OUTDOOR,
            default => $source,
        };
    }

    public static function objectType(string $source): string
    {
        return self::isHousekeeping($source) ? self::OBJECT_AREA : self::OBJECT_USER;
    }

    public static function label(string $source): string
    {
        return match ($source) {
            self::SOURCE_HOUSEKEEPING_INDOOR => 'Housekeeping Indoor',
            self::SOURCE_HOUSEKEEPING_OUTDOOR => 'Housekeeping Outdoor',
            self::SOURCE_HOUSEKEEPING => 'Housekeeping',
            default => 'Maintenance',
        };
    }

    /**
     * Source KPI housekeeping untuk klasifikasi area tertentu.
     */
    public static function housekeepingSource(string $kpiClass): string
    {
        return $kpiClass === 'indoor'
            ? self::SOURCE_HOUSEKEEPING_INDOOR
            : self::SOURCE_HOUSEKEEPING_OUTDOOR;
    }
}
