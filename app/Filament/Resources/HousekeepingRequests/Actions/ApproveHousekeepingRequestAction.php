<?php

namespace App\Filament\Resources\HousekeepingRequests\Actions;

use App\Models\HousekeepingRequest;
use App\Support\HousekeepingPlanGenerator;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class ApproveHousekeepingRequestAction
{
    public static function make(): Action
    {
        return Action::make('approve')
            ->label('Approve')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->modalHeading('Approve Housekeeping Request')
            ->modalDescription('Request yang disetujui otomatis dibuatkan Plan Harian berisi checklist fasilitas terkait.')
            ->form([
                DatePicker::make('plan_date')
                    ->label('Tanggal Plan Harian')
                    ->required()
                    ->default(fn (HousekeepingRequest $record) => $record->request_date ?? now()),
                Select::make('manpower_ids')
                    ->label('Manpower')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(fn (HousekeepingRequest $record): array => $record->area?->manpowers()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all() ?? [])
                    ->helperText('Kosongkan untuk menugaskan semua manpower aktif di area.'),
                Textarea::make('approval_notes')
                    ->label('Catatan Approval')
                    ->rows(3)
                    ->nullable(),
            ])
            ->action(function (HousekeepingRequest $record, array $data): void {
                $plan = app(HousekeepingPlanGenerator::class)->generateFromRequest(
                    request: $record,
                    planDate: $data['plan_date'],
                    manpowerIds: $data['manpower_ids'] ?? [],
                    createdBy: auth()->id(),
                );

                $record->update([
                    'status' => HousekeepingRequest::STATUSES['Approved'],
                    'approval_notes' => $data['approval_notes'] ?? null,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'housekeeping_plan_id' => $plan->id,
                ]);

                Notification::make()
                    ->title('Request disetujui')
                    ->body('Plan Harian '.$plan->plan_number.' berhasil dibuat.')
                    ->success()
                    ->send();
            })
            ->visible(fn (HousekeepingRequest $record): bool => $record->isOpen());
    }
}
