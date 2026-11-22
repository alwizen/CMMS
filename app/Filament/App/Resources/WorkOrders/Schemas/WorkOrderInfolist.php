<?php

namespace App\Filament\App\Resources\WorkOrders\Schemas;

use App\Filament\Resources\WorkOrders\Schemas\WorkOrderInfolist as AdminWorkOrderInfolist;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WorkOrderInfolist
{
    /**
     * Infolist panel lapangan: checklist tidak ditampilkan dua kali.
     * Item checklist diedit lewat relation manager "Checklist & Hasil Eksekusi".
     */
    public static function configure(Schema $schema): Schema
    {
        $schema = AdminWorkOrderInfolist::configure($schema);

        $hiddenHeadings = [
            'Checklist & Hasil Eksekusi',
        ];

        $components = collect($schema->getComponents(withHidden: true))
            ->reject(fn ($component): bool => $component instanceof Section
                && in_array($component->getHeading(), $hiddenHeadings, true))
            ->all();

        return $schema->components($components);
    }
}
