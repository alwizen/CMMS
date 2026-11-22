<?php

namespace App\Filament\Resources\WorkOrders\Actions;

use App\Models\WorkOrder;
use App\Notifications\WorkOrderReviewRejectedNotification;
use App\Support\Notify;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/**
 * Review hasil pekerjaan teknisi oleh SPV MOS.
 *
 * Teknisi menandai WO selesai -> WO masuk antrean review. Setelah disetujui,
 * maintenance history dibuat dan maintenance request ikut tuntas. Bila ditolak,
 * WO dibuka kembali agar teknisi memperbaiki pekerjaan.
 */
class ReviewWorkOrderAction
{
    public static function approve(): Action
    {
        return Action::make('approveWorkOrder')
            ->label('Approve')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->visible(fn (WorkOrder $record): bool => static::canReview($record))
            ->modalHeading('Setujui Hasil Pekerjaan')
            ->modalDescription('Setelah disetujui, maintenance history dibuat dan request terkait ikut tuntas.')
            ->form([
                Textarea::make('approval_notes')
                    ->label('Catatan Review')
                    ->rows(3)
                    ->nullable()
                    ->default(fn (WorkOrder $record): ?string => $record->approval_notes),
            ])
            ->action(function (WorkOrder $record, array $data): void {
                $record->update([
                    'approval_status' => WorkOrder::APPROVAL_APPROVED,
                    'approval_notes' => $data['approval_notes'] ?? null,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);

                Notification::make()
                    ->title('Work order disetujui')
                    ->body('WO '.$record->work_order_number.' disetujui. Maintenance history sudah dibuat.')
                    ->success()
                    ->send();
            });
    }

    public static function reject(): Action
    {
        return Action::make('rejectWorkOrder')
            ->label('Reject')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->visible(fn (WorkOrder $record): bool => static::canReview($record))
            ->modalHeading('Tolak Hasil Pekerjaan')
            ->modalDescription('WO akan dibuka kembali agar teknisi memperbaiki pekerjaan, lalu menandai selesai lagi.')
            ->form([
                Textarea::make('approval_notes')
                    ->label('Alasan Penolakan')
                    ->rows(3)
                    ->required()
                    ->default(fn (WorkOrder $record): ?string => $record->approval_notes),
            ])
            ->action(function (WorkOrder $record, array $data): void {
                $record->update([
                    'approval_status' => WorkOrder::APPROVAL_REJECTED,
                    'approval_notes' => $data['approval_notes'],
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'status' => WorkOrder::STATUSES['In Progress'],
                    'finish_at' => null,
                ]);

                $recipients = Notify::forWorkOrder($record->fresh());

                if ($recipients->isNotEmpty()) {
                    NotificationFacade::send(
                        $recipients,
                        new WorkOrderReviewRejectedNotification($record, $data['approval_notes']),
                    );
                }

                Notification::make()
                    ->title('Work order ditolak')
                    ->body('WO '.$record->work_order_number.' dikembalikan ke teknisi untuk diperbaiki.')
                    ->warning()
                    ->send();
            });
    }

    protected static function canReview(WorkOrder $record): bool
    {
        $user = auth()->user();

        if (! $user || ! $user->hasAnyRole(['spv_mos', 'super_admin'])) {
            return false;
        }

        return $record->isAwaitingReview();
    }
}
