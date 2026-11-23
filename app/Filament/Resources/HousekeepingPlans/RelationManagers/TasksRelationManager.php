<?php

namespace App\Filament\Resources\HousekeepingPlans\RelationManagers;

use App\Models\HousekeepingFacility;
use App\Models\HousekeepingPlanTask;
use App\Models\HousekeepingTask;
use App\Support\HousekeepingRole;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static ?string $title = 'Checklist Pekerjaan';

    protected static ?string $modelLabel = 'task';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Item Pekerjaan')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('housekeeping_task_id')
                            ->columnSpanFull()
                            ->label('Master Checklist')
                            ->options(fn (): array => HousekeepingTask::query()
                                ->where('is_active', true)
                                ->whereHas('facility', function ($query): void {
                                    $query->where('housekeeping_area_id', $this->getOwnerRecord()->housekeeping_area_id);
                                })
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if ($state) {
                                    $task = HousekeepingTask::find($state);
                                    $set('name', $task?->name);

                                    if ($task?->housekeeping_facility_id) {
                                        $set('housekeeping_facility_id', $task->housekeeping_facility_id);
                                    }
                                }
                            })
                            ->placeholder('Pilih dari master checklist (opsional)'),
                        TextInput::make('name')
                            ->label('Nama Pekerjaan')
                            ->required()
                            ->placeholder('e.g., Pemotongan Rumput'),
                        Select::make('housekeeping_facility_id')
                            ->label('Fasilitas / Objek (opsional)')
                            ->options(fn (Get $get): array => HousekeepingFacility::query()
                                ->where('housekeeping_area_id', $this->getOwnerRecord()->housekeeping_area_id)
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->nullable()
                            ->placeholder('Select facility'),
                    ])
                    ->columns(2),
                Section::make('Catatan')
                    ->columnSpanFull()
                    ->description('Foto before/after diunggah per item checklist lewat tombol Upload Before / Upload After.')
                    ->schema([
                        Textarea::make('note')
                            ->label('Note')
                            ->nullable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Pekerjaan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('facility.name')
                    ->label('Fasilitas')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Done' => 'success',
                        'In Progress' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('photo_before')
                    ->label('Before')
                    ->badge()
                    ->getStateUsing(fn (HousekeepingPlanTask $record): string => count($record->photo_before ?? []).' foto')
                    ->color(fn (HousekeepingPlanTask $record): string => count($record->photo_before ?? []) > 0 ? 'success' : 'gray'),
                TextColumn::make('photo_after')
                    ->label('After')
                    ->badge()
                    ->getStateUsing(fn (HousekeepingPlanTask $record): string => count($record->photo_after ?? []).' foto')
                    ->color(fn (HousekeepingPlanTask $record): string => count($record->photo_after ?? []) > 0 ? 'success' : 'gray'),
                TextColumn::make('started_at')
                    ->label('Mulai')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('completed_at')
                    ->label('Selesai')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->headerActions([
                CreateAction::make()
                    // Struktur checklist dibuat SPV GOS, bukan dieksekusi petugas.
                    ->visible(fn (): bool => HousekeepingRole::canManagePlan(auth()->user()))
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['status'] = 'Pending';

                        return $data;
                    }),
            ])
            ->recordActions([
                Action::make('upload_before')
                    ->label('Upload Before')
                    ->icon('heroicon-o-photo')
                    ->color('warning')
                    ->visible(fn (): bool => HousekeepingRole::canExecute(auth()->user()))
                    ->form([
                        FileUpload::make('photos')
                            ->label('Foto Before')
                            ->image()
                            ->multiple()
                            ->maxFiles(5)
                            ->disk('public')
                            ->directory('housekeeping/plan-tasks/before')
                            ->visibility('public')
                            ->required(),
                    ])
                    ->action(function (HousekeepingPlanTask $record, array $data): void {
                        $existing = $record->photo_before ?? [];
                        $record->update([
                            'photo_before' => array_values(array_slice([...$existing, ...($data['photos'] ?? [])], 0, 5)),
                        ]);
                    })
                    ->successNotificationTitle('Foto before tersimpan'),
                Action::make('start')
                    ->label('Mulai Kerja')
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->visible(fn (HousekeepingPlanTask $record): bool => $record->status === 'Pending'
                        && HousekeepingRole::canExecute(auth()->user()))
                    ->action(function (HousekeepingPlanTask $record): void {
                        if (! $record->hasPhotoBefore()) {
                            Notification::make()
                                ->title('Foto before belum ada')
                                ->body('Upload foto before item checklist ini terlebih dahulu lewat tombol "Upload Before".')
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->update(['status' => 'In Progress']);

                        Notification::make()
                            ->title('Pekerjaan dimulai (In Progress)')
                            ->success()
                            ->send();
                    }),
                Action::make('complete')
                    ->label('Selesaikan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (HousekeepingPlanTask $record): bool => $record->status === 'In Progress'
                        && HousekeepingRole::canExecute(auth()->user()))
                    ->form([
                        Textarea::make('note')
                            ->label('Note')
                            ->nullable()
                            ->default(fn (HousekeepingPlanTask $record): ?string => $record->note),
                    ])
                    ->action(function (HousekeepingPlanTask $record, array $data): void {
                        if (! $record->hasPhotoAfter()) {
                            Notification::make()
                                ->title('Foto after belum ada')
                                ->body('Upload foto after item checklist ini terlebih dahulu lewat tombol "Upload After".')
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->update([
                            'status' => 'Done',
                            'note' => $data['note'] ?? $record->note,
                        ]);

                        Notification::make()
                            ->title('Pekerjaan selesai (Done)')
                            ->success()
                            ->send();
                    }),
                Action::make('upload_after')
                    ->label('Upload After')
                    ->icon('heroicon-o-photo')
                    ->color('success')
                    ->visible(fn (): bool => HousekeepingRole::canExecute(auth()->user()))
                    ->form([
                        FileUpload::make('photos')
                            ->label('Foto After')
                            ->image()
                            ->multiple()
                            ->maxFiles(5)
                            ->disk('public')
                            ->directory('housekeeping/plan-tasks/after')
                            ->visibility('public')
                            ->required(),
                    ])
                    ->action(function (HousekeepingPlanTask $record, array $data): void {
                        $existing = $record->photo_after ?? [];
                        $record->update([
                            'photo_after' => array_values(array_slice([...$existing, ...($data['photos'] ?? [])], 0, 5)),
                        ]);
                    })
                    ->successNotificationTitle('Foto after tersimpan'),
                Action::make('view_before')
                    ->label('Lihat Before')
                    ->icon('heroicon-o-eye')
                    ->visible(fn (HousekeepingPlanTask $record): bool => count($record->photo_before ?? []) > 0)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (HousekeepingPlanTask $record) => $this->renderPhotoModal($record, 'before')),
                Action::make('view_after')
                    ->label('Lihat After')
                    ->icon('heroicon-o-eye')
                    ->visible(fn (HousekeepingPlanTask $record): bool => count($record->photo_after ?? []) > 0)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (HousekeepingPlanTask $record) => $this->renderPhotoModal($record, 'after')),
                EditAction::make()
                    ->visible(fn (HousekeepingPlanTask $record): bool => HousekeepingRole::canManagePlan(auth()->user())),
                DeleteAction::make()
                    ->visible(fn (HousekeepingPlanTask $record): bool => HousekeepingRole::canManagePlan(auth()->user())),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => HousekeepingRole::canManagePlan(auth()->user())),
                ]),
            ]);
    }

    public function renderPhotoModal(HousekeepingPlanTask $record, string $type): View
    {
        return view('filament.pages.housekeeping-task-documentation', [
            'record' => $record,
            'type' => $type,
        ]);
    }
}
