<?php

namespace App\Filament\Imports;

use App\Models\Area;
use App\Models\Company;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class AreaImporter extends Importer
{
    protected static ?string $model = Area::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('company_id')
                ->requiredMapping()
                ->rules(['required', 'max:100']),
            ImportColumn::make('code')
                ->requiredMapping()
                ->rules(['required', 'max:20']),
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:100']),
            ImportColumn::make('description')
                ->rules(['nullable']),
            ImportColumn::make('is_active')
                ->rules(['nullable', 'boolean']),
        ];
    }

    public function resolveRecord(): ?Area
    {
        return new Area();
    }

    public function mutateBeforeCreate(): array
    {
        $data = $this->data;

        $company = Company::where('name', $data['company'])
            ->orWhere('code', $data['company'])
            ->first();

        $data['company_id'] = $company?->id;
        unset($data['company']);

        if (isset($data['is_active'])) {
            $data['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        return $data;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your area import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
