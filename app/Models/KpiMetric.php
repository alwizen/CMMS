<?php

namespace App\Models;

use App\Support\Kpi\KpiRegistry;
use Illuminate\Database\Eloquent\Model;

class KpiMetric extends Model
{
    /**
     * @var list<string>
     */
    public const SOURCES = [
        KpiRegistry::SOURCE_MAINTENANCE,
        KpiRegistry::SOURCE_HOUSEKEEPING_INDOOR,
        KpiRegistry::SOURCE_HOUSEKEEPING_OUTDOOR,
    ];

    protected $fillable = [
        'source',
        'key',
        'target_value',
        'weight',
        'is_active',
    ];

    protected $casts = [
        'target_value' => 'float',
        'weight' => 'float',
        'is_active' => 'boolean',
    ];

    public function label(): string
    {
        $source = KpiRegistry::normalizeSource($this->source);

        return KpiRegistry::DEFINITIONS[$source][$this->key]['label'] ?? $this->key;
    }

    public function sourceLabel(): string
    {
        return KpiRegistry::label($this->source);
    }
}
