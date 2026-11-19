<?php

namespace App\Filament\Resources\HousekeepingRequests\Actions;

use App\Models\HousekeepingRequest;
use App\Models\User;
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
                Select::make('user_ids')
                    ->label('Petugas Housekeeping')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(fn (): array => User::query()
                        ->where('status', true)
                        ->whereHas('roles', fn ($query) => $query->where('name', 'patra-jasa'))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->helperText('Pilih user dengan role patra-jasa yang akan mengerjakan plan ini.'),
                Textarea::make('approval_notes')
                    ->label('Catatan Approval')
                    ->rows(3)
                    ->nullable(),
            ])
            ->action(function (HousekeepingRequest $record, array $data): void {
                try {
                    $plan = app(HousekeepingPlanGenerator::class)->generateFromRequest(
                        request: $record,
                        planDate: $data['plan_date'],
                        createdBy: auth()->id(),
                        userIds: $data['user_ids'] ?? [],
                    );
                } catch (\RuntimeException $exception) {
                    Notification::make()
                        ->title('Tidak bisa membuat Plan Harian')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

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
