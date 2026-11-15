<?php

namespace App\Filament\Actions;

use Closure;
use Filament\Actions\CreateAction;

/**
 * CreateAction yang membuat model "terkait" (bukan model tabel) lewat relationship.
 *
 * Berguna untuk relation manager di mana tabel menampilkan model induk (mis. Facility)
 * tetapi aksi membuat model anak (mis. HousekeepingTask) melalui relasi.
 */
class CreateRelatedAction extends CreateAction
{
    protected Closure|string|null $relatedModel = null;

    /**
     * Tentukan model yang akan dibuat (class-string model terkait).
     */
    public function relatedModel(Closure|string|null $model): static
    {
        $this->relatedModel = $model;

        return $this;
    }

    public function getModel(bool $withDefault = true): ?string
    {
        if (filled($model = $this->evaluate($this->relatedModel))) {
            return $model;
        }

        return parent::getModel($withDefault);
    }
}
