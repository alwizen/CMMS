<?php

namespace App\Filament\Resources\HousekeepingPlans\RelationManagers;

use App\Models\HousekeepingFacility;
use App\Models\HousekeepingPlanTask;
use App\Models\HousekeepingTask;
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
                    ->schema([
                        Textarea::make('note')
                            ->label('Note')
                            ->nullable()
                            ->columnSpanFull(),
                    ]),
                Section::make('Foto Dokumentasi')
                    ->description('Upload foto sebelum (Before) dan sesudah (After) pekerjaan.')
                    ->columnSpanFull()
                    ->schema([
                        FileUpload::make('photo_before')
                            ->label('Foto Before')
                            ->image()
                            ->multiple()
                            ->maxFiles(5)
                            ->disk('public')
                            ->directory('housekeeping/plan-tasks/before')
                            ->visibility('public')
                            ->reorderable(),
                        FileUpload::make('photo_after')
                            ->label('Foto After')
                            ->image()
                            ->multiple()
                            ->maxFiles(5)
                            ->disk('public')
                            ->directory('housekeeping/plan-tasks/after')
                            ->visibility('public')
                            ->reorderable(),
                    ])
                    ->columns(2),
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
                    ->label('Foto Before')
                    ->badge()
                    ->getStateUsing(fn (HousekeepingPlanTask $record): string => count($record->photo_before ?? []).' foto')
                    ->color(fn (HousekeepingPlanTask $record): string => count($record->photo_before ?? []) > 0 ? 'success' : 'danger'),
                TextColumn::make('photo_after')
                    ->label('Foto After')
                    ->badge()
                    ->getStateUsing(fn (HousekeepingPlanTask $record): string => count($record->photo_after ?? []).' foto')
                    ->color(fn (HousekeepingPlanTask $record): string => count($record->photo_after ?? []) > 0 ? 'success' : 'danger'),
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
                    ->visible(fn (HousekeepingPlanTask $record): bool => $record->status === 'Pending')
                    ->form([
                        FileUpload::make('photo_before')
                            ->label('Foto Before (wajib)')
                            ->image()
                            ->multiple()
                            ->maxFiles(5)
                            ->disk('public')
                            ->directory('housekeeping/plan-tasks/before')
                            ->visibility('public')
                            ->required(),
                    ])
                    ->action(function (HousekeepingPlanTask $record, array $data): void {
                        $record->update([
                            'status' => 'In Progress',
                            'photo_before' => $data['photo_before'] ?? $record->photo_before,
                        ]);
                    })
                    ->successNotificationTitle('Pekerjaan dimulai (In Progress)'),
                Action::make('complete')
                    ->label('Selesaikan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (HousekeepingPlanTask $record): bool => $record->status === 'In Progress')
                    ->form([
                        FileUpload::make('photo_after')
                            ->label('Foto After (wajib)')
                            ->image()
                            ->multiple()
                            ->maxFiles(5)
                            ->disk('public')
                            ->directory('housekeeping/plan-tasks/after')
                            ->visibility('public')
                            ->required(),
                        Textarea::make('note')
                            ->label('Note')
                            ->nullable()
                            ->default(fn (HousekeepingPlanTask $record): ?string => $record->note),
                    ])
                    ->action(function (HousekeepingPlanTask $record, array $data): void {
                        $record->update([
                            'status' => 'Done',
                            'photo_after' => $data['photo_after'] ?? $record->photo_after,
                            'note' => $data['note'] ?? $record->note,
                        ]);
                    })
                    ->successNotificationTitle('Pekerjaan selesai (Done)'),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
