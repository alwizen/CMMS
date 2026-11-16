<?php

namespace App\Filament\Actions;

use App\Models\HousekeepingArea;
use App\Models\HousekeepingManpower;
use App\Support\HousekeepingPlanGenerator;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class GenerateHousekeepingPlanAction
{
    public static function make(): Action
    {
        return Action::make('generateHousekeepingPlans')
            ->label('Generate Plan Harian')
            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
            ->color('success')
            ->modalHeading('Generate Plan Harian Housekeeping')
            ->modalDescription('Membuat Plan Harian (status Draft) berisi checklist dari master checklist fasilitas di area terpilih. Area tanpa checklist aktif dilewati.')
            ->form([
                Forms\Components\Select::make('area_ids')
                    ->label('Area Housekeeping')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->required()
                    ->options(fn (): array => HousekeepingArea::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->helperText('Satu plan dibuat untuk tiap area terpilih.'),
                Forms\Components\DatePicker::make('plan_date')
                    ->label('Tanggal Plan')
                    ->required()
                    ->default(now()),
                Forms\Components\Select::make('manpower_ids')
                    ->label('Manpower')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(fn (Get $get): array => HousekeepingManpower::query()
                        ->whereIn('housekeeping_area_id', $get('area_ids') ?? [])
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->helperText('Kosongkan untuk menugaskan semua manpower aktif di tiap area.'),
                Forms\Components\Textarea::make('note')
                    ->label('Catatan')
                    ->rows(2)
                    ->nullable()
                    ->columnSpanFull(),
            ])
            ->action(function (array $data): void {
                $result = app(HousekeepingPlanGenerator::class)->generate(
                    areaIds: $data['area_ids'],
                    planDate: $data['plan_date'],
                    manpowerIds: $data['manpower_ids'] ?? [],
                    note: $data['note'] ?? null,
                    createdBy: auth()->id(),
                );

                if ($result['created'] === 0) {
                    $lines = [];

                    foreach ($result['skip_reasons'] as $area => $reason) {
                        $lines[] = $area.': '.$reason;
                    }

                    Notification::make()
                        ->title('Tidak ada Plan Harian yang dibuat')
                        ->body($lines === []
                            ? 'Tidak ada area yang memenuhi syarat.'
                            : 'Semua area dilewati — '.implode('; ', $lines).'.')
                        ->warning()
                        ->persistent()
                        ->send();

                    return;
                }

                $body = sprintf('%d Plan Harian dibuat.', $result['created']);

                if (! empty($result['skip_reasons'])) {
                    $lines = [];

                    foreach ($result['skip_reasons'] as $area => $reason) {
                        $lines[] = $area.' ('.$reason.')';
                    }

                    $body .= ' Dilewati: '.implode(', ', $lines).'.';
                }

                Notification::make()
                    ->title('Generate Plan Harian selesai')
                    ->body($body)
                    ->success()
                    ->send();
            });
    }
}
