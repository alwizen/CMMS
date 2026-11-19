<?php

namespace App\Filament\Resources\HousekeepingPlans\Actions;

use App\Models\HousekeepingPlan;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Review pekerjaan Plan Harian oleh SPV GOS (supervisor housekeeping).
 *
 * Plan dibuat SPV GOS lalu langsung dikerjakan patra-jasa. SPV GOS dapat
 * me-review pekerjaan yang sedang berjalan maupun yang sudah selesai, lalu
 * approve/reject. Approval inilah trigger laporan harian: selama belum
 * di-approve, laporan tanggal terkait ditahan.
 */
class ApproveHousekeepingPlanAction
{
    public static function approve(): Action
    {
        return Action::make('approvePlan')
            ->label('Approve')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->visible(fn (HousekeepingPlan $record): bool => static::canDecide($record))
            ->modalHeading('Review & Setujui Pekerjaan')
            ->modalDescription('Periksa pekerjaan yang sudah dikerjakan patra-jasa. Setelah disetujui, laporan harian tanggal plan ini bisa digenerate.')
            ->form([
                Textarea::make('approval_notes')
                    ->label('Catatan Approval')
                    ->rows(3)
                    ->nullable()
                    ->default(fn (HousekeepingPlan $record): ?string => $record->approval_notes),
            ])
            ->action(function (HousekeepingPlan $record, array $data): void {
                $record->update([
                    'approval_status' => HousekeepingPlan::APPROVAL_APPROVED,
                    'approval_notes' => $data['approval_notes'] ?? null,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);

                Notification::make()
                    ->title('Plan disetujui')
                    ->body('Plan '.$record->plan_number.' disetujui. Laporan harian tanggal '.$record->plan_date?->format('d M Y').' sudah bisa digenerate.')
                    ->success()
                    ->send();
            });
    }

    public static function reject(): Action
    {
        return Action::make('rejectPlan')
            ->label('Reject')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->visible(fn (HousekeepingPlan $record): bool => static::canDecide($record))
            ->modalHeading('Review: Tolak Pekerjaan')
            ->form([
                Textarea::make('approval_notes')
                    ->label('Alasan Penolakan')
                    ->rows(3)
                    ->required()
                    ->default(fn (HousekeepingPlan $record): ?string => $record->approval_notes),
            ])
            ->action(function (HousekeepingPlan $record, array $data): void {
                $record->update([
                    'approval_status' => HousekeepingPlan::APPROVAL_REJECTED,
                    'approval_notes' => $data['approval_notes'] ?? null,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);

                Notification::make()
                    ->title('Plan ditolak')
                    ->body('Plan '.$record->plan_number.' ditolak. Laporan harian tanggal ini belum bisa digenerate.')
                    ->warning()
                    ->send();
            });
    }

    protected static function canDecide(HousekeepingPlan $record): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        // Yang berhak memutuskan: SPV GOS (housekeeping) atau super admin.
        if (! $user->hasAnyRole(['spv_gos', 'super_admin'])) {
            return false;
        }

        // Review baru relevan setelah pekerjaan dimulai (bukan Draft kosong),
        // baik masih berjalan maupun sudah selesai.
        if (! $record->hasStartedWork()) {
            return false;
        }

        return $record->approval_status !== HousekeepingPlan::APPROVAL_APPROVED;
    }
}
