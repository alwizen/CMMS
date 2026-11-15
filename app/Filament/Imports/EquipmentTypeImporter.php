<?php

namespace App\Filament\Imports;

use App\Filament\Imports\Concerns\ResolvesImportValues;
use App\Models\EquipmentType;
use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class EquipmentTypeImporter extends Importer
{
    use ResolvesImportValues;

    protected static ?string $model = EquipmentType::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('code')
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->rules(['required', 'string', 'max:50'])
                ->helperText('Unik. Dipakai sebagai kunci update saat import ulang. Contoh: pump, tank.')
                ->example('pump'),
            ImportColumn::make('name')
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->rules(['required', 'string', 'max:100'])
                ->example('Pompa'),
            ImportColumn::make('responsible_user_id')
                ->label('Penanggung Jawab')
                ->castStateUsing(fn (mixed $state): ?int => static::resolveUserId($state))
                ->ignoreBlankState()
                ->rules(['nullable', 'integer', 'exists:users,id'])
                ->helperText('Nama atau email penanggung jawab (harus user terdaftar). Kosongkan bila tidak ada.')
                ->example('Risna'),
            ImportColumn::make('description')
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->ignoreBlankState()
                ->rules(['nullable', 'string']),
            ImportColumn::make('status')
                ->castStateUsing(fn (mixed $state): ?bool => $state === null ? null : static::parseImportBoolean($state))
                ->ignoreBlankState()
                ->rules(['nullable', 'boolean'])
                ->helperText('1/0, true/false, active/inactive, aktif/nonaktif (default: aktif).')
                ->example('active'),
        ];
    }

    /**
     * Cari ID user berdasarkan nama atau email. Mengembalikan null bila tidak ditemukan
     * agar baris tidak gagal hanya karena penanggung jawab kosong/tidak cocok.
     */
    protected static function resolveUserId(mixed $state): ?int
    {
        if (blank($state)) {
            return null;
        }

        if (is_int($state) || (is_string($state) && ctype_digit(trim($state)))) {
            $id = (int) $state;

            return User::query()->whereKey($id)->exists() ? $id : null;
        }

        $needle = mb_strtolower(trim((string) $state));

        if ($needle === '') {
            return null;
        }

        $byName = User::query()
            ->whereRaw('LOWER(name) = ?', [$needle])
            ->value('id');

        if ($byName !== null) {
            return (int) $byName;
        }

        $byEmail = User::query()
            ->whereRaw('LOWER(email) = ?', [$needle])
            ->value('id');

        return $byEmail !== null ? (int) $byEmail : null;
    }

    public function resolveRecord(): EquipmentType
    {
        $code = $this->data['code'] ?? null;

        if (blank($code)) {
            return new EquipmentType;
        }

        return EquipmentType::query()
            ->whereRaw('LOWER(code) = ?', [mb_strtolower((string) $code)])
            ->first() ?? new EquipmentType;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import equipment type selesai: '.Number::format($import->successful_rows).' baris berhasil diproses.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' baris gagal diproses.';
        }

        return $body;
    }
}
