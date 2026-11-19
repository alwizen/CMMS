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
                    ->description('Foto before/after diunggah per fasilitas pada tab "Dokumentasi Fasilitas".')
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
                Action::make('start')
                    ->label('Mulai Kerja')
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->visible(fn (HousekeepingPlanTask $record): bool => $record->status === 'Pending'
                        && HousekeepingRole::canExecute(auth()->user()))
                    ->action(function (HousekeepingPlanTask $record): void {
                        $planFacility = $record->planFacility;

                        if ($planFacility !== null && empty($planFacility->photo_before)) {
                            Notification::make()
                                ->title('Foto before belum ada')
                                ->body('Upload foto before fasilitas pada tab "Dokumentasi Fasilitas" terlebih dahulu.')
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
                        $planFacility = $record->planFacility;

                        if ($planFacility !== null && empty($planFacility->photo_after)) {
                            Notification::make()
                                ->title('Foto after belum ada')
                                ->body('Upload foto after fasilitas pada tab "Dokumentasi Fasilitas" terlebih dahulu.')
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
}
