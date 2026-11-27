<?php

namespace App\Filament\Resources\WorkOrders\Actions;

use App\Models\MaintenanceRequest;
use App\Models\WorkOrderFinding;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Ajukan maintenance request perbaikan dari sebuah temuan checklist.
 *
 * Request dibuat otomatis (prefill dari temuan) agar petugas tidak perlu
 * mengetik ulang, lalu temuan ditandai "Sudah Diajukan".
 */
class SubmitFindingRequestAction
{
    public static function make(): Action
    {
        return Action::make('submitFindingRequest')
            ->label('Ajukan Request Perbaikan')
            ->icon(Heroicon::OutlinedWrenchScrewdriver)
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Ajukan Request Perbaikan')
            ->modalDescription('Maintenance request akan dibuat dari temuan ini dan dikirim ke SPV untuk ditindaklanjuti.')
            ->visible(fn (WorkOrderFinding $record): bool => $record->isOpen()
                && $record->equipment_id !== null)
            ->action(function (WorkOrderFinding $record): void {
                if (! $record->isOpen()) {
                    return;
                }

                $maintenanceRequest = MaintenanceRequest::create([
                    'equipment_id' => $record->equipment_id,
                    'reported_by' => auth()->id() ?? $record->reported_by,
                    'operation_status' => 'Faulty',
                    'description' => $record->description
                        ?: $record->displayName().' — hasil inspeksi: '.$record->result,
                    'damage_date' => now()->toDateString(),
                    'damage_time' => now()->format('H:i'),
                    'equipment_condition' => $record->result,
                    'early_action' => $record->notes,
                    'status' => MaintenanceRequest::STATUSES['Open'],
                    'photo' => $record->photos,
                ]);

                $record->update([
                    'status' => WorkOrderFinding::STATUSES['Requested'],
                    'maintenance_request_id' => $maintenanceRequest->id,
                ]);

                Notification::make()
                    ->title('Request perbaikan diajukan')
                    ->body('Maintenance request '.$maintenanceRequest->request_number.' dibuat dari temuan '.$record->finding_number.'.')
                    ->success()
                    ->send();
            });
    }
}
