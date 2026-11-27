<?php

namespace App\Filament\App\Resources\WorkOrders\Pages;

use App\Filament\App\Resources\WorkOrders\Schemas\WorkOrderInfolist;
use App\Filament\App\Resources\WorkOrders\WorkOrderResource;
use App\Models\WorkOrder;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ViewWorkOrder extends ViewRecord
{
    protected static string $resource = WorkOrderResource::class;

    public function resolveRecord(int|string $key): WorkOrder
    {
        return WorkOrderResource::getEloquentQuery()
            ->with([
                'equipment.equipmentType',
                'equipment.area',
                'issuedBy',
                'technicianCoordinator',
                'workOrderActivities.activity',
                'workOrderWorkers.user',
                'findings',
            ])
            ->findOrFail($key);
    }

    public function infolist(Schema $schema): Schema
    {
        return WorkOrderInfolist::configure($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('report')
                ->label('Generate Laporan')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->visible(fn (): bool => $this->record->status === WorkOrder::STATUSES['Completed'])
                ->url(fn (): string => route('work-orders.report.pdf', $this->record))
                ->openUrlInNewTab(),
            Action::make('start')
                ->label('Mulai Pekerjaan')
                ->icon(Heroicon::OutlinedPlay)
                ->color('warning')
                ->visible(fn (): bool => $this->canExecute() && $this->record->status === WorkOrder::STATUSES['Pending'])
                ->action(function (): void {
                    if (! $this->record->isExecutable()) {
                        $this->notifyNotExecutable();

                        return;
                    }

                    if (! $this->record->claimBy(auth()->id())) {
                        $this->notifyLocked();

                        return;
                    }

                    $this->record->update(['status' => WorkOrder::STATUSES['In Progress']]);

                    Notification::make()
                        ->title('Pekerjaan dimulai')
                        ->success()
                        ->send();
                })
                // Muat ulang halaman agar relation manager (tombol isi checklist)
                // langsung mencerminkan status baru tanpa refresh manual.
                ->after(fn () => $this->redirectToView()),
            Action::make('complete')
                ->label('Tandai Selesai')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->canExecute() && in_array($this->record->status, [WorkOrder::STATUSES['Pending'], WorkOrder::STATUSES['In Progress']], true))
                ->disabled(fn (): bool => $this->pendingChecklistCount() > 0)
                ->tooltip(fn (): ?string => $this->pendingChecklistCount() > 0
                    ? 'Lengkapi '.$this->pendingChecklistCount().' checklist yang belum diisi sebelum menandai selesai.'
                    : null)
                ->action(function (): void {
                    if ($this->pendingChecklistCount() > 0) {
                        Notification::make()
                            ->title('Checklist belum lengkap')
                            ->body('Masih ada '.$this->pendingChecklistCount().' checklist yang belum diisi. Lengkapi terlebih dahulu sebelum menandai selesai.')
                            ->warning()
                            ->send();

                        return;
                    }

                    if (! $this->record->isExecutable()) {
                        $this->notifyNotExecutable();

                        return;
                    }

                    if (! $this->record->claimBy(auth()->id())) {
                        $this->notifyLocked();

                        return;
                    }

                    $this->record->update([
                        'status' => WorkOrder::STATUSES['Completed'],
                        'finish_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Work order selesai')
                        ->body('Hasil pekerjaan dikirim ke SPV MOS untuk direview.')
                        ->success()
                        ->send();
                })
                ->after(fn () => $this->redirectToView()),
        ];
    }

    /**
     * Kembali ke halaman detail WO dengan state terbaru (memuat ulang
     * relation manager agar visibilitas tombol checklist ikut diperbarui).
     */
    protected function redirectToView(): void
    {
        $this->redirect(
            WorkOrderResource::getUrl('view', ['record' => $this->record->getKey()]),
        );
    }

    /**
     * Eksekusi WO mengikuti izin Shield `Update:WorkOrder` dan tidak
     * sedang dikunci oleh petugas lain.
     */
    protected function canExecute(): bool
    {
        return (bool) auth()->user()?->can('Update:WorkOrder')
            && ! $this->record->isLockedFor(auth()->user());
    }

    /**
     * Jumlah item checklist yang belum ditandai dieksekusi. WO tidak boleh
     * diselesaikan selama masih ada checklist yang belum diisi.
     */
    protected function pendingChecklistCount(): int
    {
        return $this->record->workOrderActivities()
            ->where(function ($query): void {
                $query->where('executed', false)->orWhereNull('executed');
            })
            ->count();
    }

    protected function notifyLocked(): void
    {
        Notification::make()
            ->title('Work order sedang dikerjakan')
            ->body($this->record->fresh()->lockMessage())
            ->warning()
            ->send();
    }

    protected function notifyNotExecutable(): void
    {
        Notification::make()
            ->title('Belum bisa dikerjakan')
            ->body('Work order baru dapat dikerjakan mulai '.($this->record->start_at?->format('d M Y') ?? '-').'.')
            ->danger()
            ->send();
    }
}
