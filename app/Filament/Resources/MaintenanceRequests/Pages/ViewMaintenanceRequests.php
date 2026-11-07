<?php

namespace App\Filament\Resources\MaintenanceRequests\Pages;

use App\Filament\Resources\MaintenanceRequests\MaintenanceRequestResource;
use App\Filament\Resources\MaintenanceRequests\Schemas\MaintenanceRequestInfolist;
use App\Models\Activity;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderActivity;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewMaintenanceRequests extends ViewRecord
{
    protected static string $resource = MaintenanceRequestResource::class;

    public function infolist(Schema $schema): Schema
    {
        return MaintenanceRequestInfolist::configure($schema);
    }

    /**
     * Aktivitas aktif untuk tipe equipment dari request ini.
     *
     * @return array<int, string>
     */
    public function activityOptions(): array
    {
        $equipmentTypeId = $this->getRecord()?->equipment?->equipment_type_id;

        if (! $equipmentTypeId) {
            return [];
        }

        return Activity::query()
            ->where('equipment_type_id', $equipmentTypeId)
            ->where('status', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->form([
                    DatePicker::make('start_date')
                        ->label('Start Date')
                        ->required()
                        ->default(now()),
                    Select::make('activity_ids')
                        ->label('Aktivitas Pemeriksaan')
                        ->options(fn ($livewire): array => $livewire->activityOptions())
                        ->default(fn ($livewire): array => array_keys($livewire->activityOptions()))
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->helperText('Checklist yang akan dikerjakan teknisi. Kosongkan bila memang tidak ada item pemeriksaan.')
                        ->columnSpanFull(),
                    Select::make('technician_coordinator_id')
                        ->label('Penanggung Jawab')
                        ->options(User::pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->placeholder('Pilih 1 penanggung jawab'),
                    Select::make('classification')
                        ->label('Classification')
                        ->options([
                            'Preventive' => 'Preventive',
                            'Corrective' => 'Corrective',
                        ])
                        ->default('Corrective')
                        ->required(),
                    Textarea::make('approval_notes')
                        ->label('Catatan Approval')
                        ->placeholder('Masukkan catatan approval...')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    /** @var MaintenanceRequest $record */
                    $record = $this->getRecord();

                    $record->update([
                        'status' => MaintenanceRequest::STATUSES['Approved'],
                        'approval_notes' => $data['approval_notes'],
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ]);

                    $workOrder = WorkOrder::create([
                        'equipment_id' => $record->equipment_id,
                        'maintenance_request_id' => $record->id,
                        'issued_by' => auth()->id(),
                        'technician_coordinator_id' => $data['technician_coordinator_id'],
                        'classification' => $data['classification'],
                        'start_at' => $data['start_date'],
                        'finish_at' => null,
                        'note' => $data['approval_notes'],
                        'status' => WorkOrder::STATUSES['Pending'],
                    ]);

                    $activityIds = array_filter($data['activity_ids'] ?? []);

                    if ($activityIds !== []) {
                        $activities = Activity::query()
                            ->whereIn('id', $activityIds)
                            ->get()
                            ->keyBy('id');

                        foreach ($activityIds as $activityId) {
                            $activity = $activities->get($activityId);

                            if (! $activity) {
                                continue;
                            }

                            WorkOrderActivity::create([
                                'work_order_id' => $workOrder->id,
                                'activity_id' => $activity->id,
                                'reference' => $activity->reference,
                                'unit' => $activity->unit,
                                'executed' => false,
                            ]);
                        }
                    }

                    Notification::make()
                        ->title('Berhasil')
                        ->body('Maintenance request telah disetujui dan Work Order telah dibuat.')
                        ->success()
                        ->send();
                })
                ->after(function (): void {
                    $this->redirect(route('filament.admin.resources.maintenance-requests.view', $this->getRecord()));
                })
                ->requiresConfirmation()
                ->modalHeading('Approve Maintenance Request')
                ->modalSubmitActionLabel('Approve')
                ->visible(fn (): bool => $this->record->status === MaintenanceRequest::STATUSES['Open']),
            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->form([
                    Textarea::make('approval_notes')
                        ->label('Catatan Penolakan')
                        ->placeholder('Masukkan alasan penolakan...')
                        ->rows(3)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    /** @var MaintenanceRequest $record */
                    $record = $this->getRecord();
                    $record->update([
                        'status' => 'Rejected',
                        'approval_notes' => $data['approval_notes'],
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ]);
                    Notification::make()
                        ->title('Berhasil')
                        ->body('Maintenance request telah ditolak.')
                        ->success()
                        ->send();
                })
                ->after(function (): void {
                    $this->redirect(route('filament.admin.resources.maintenance-requests.view', $this->getRecord()));
                })
                ->requiresConfirmation()
                ->modalHeading('Reject Maintenance Request')
                ->modalSubmitActionLabel('Reject')
                ->visible(fn (): bool => $this->record->status === MaintenanceRequest::STATUSES['Open']),
        ];
    }
}
