<?php

namespace App\Filament\Resources\HousekeepingPlans\RelationManagers;

use App\Models\HousekeepingFacility;
use App\Models\HousekeepingPlanFacility;
use App\Models\HousekeepingPlanTask;
use App\Support\HousekeepingRole;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;

class FacilitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'facilities';

    protected static ?string $title = 'Dokumentasi Fasilitas';

    protected static ?string $modelLabel = 'fasilitas';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Fasilitas')
                    ->schema([
                        Select::make('housekeeping_facility_id')
                            ->label('Fasilitas')
                            ->options(fn (): array => HousekeepingFacility::query()
                                ->where('housekeeping_area_id', $this->getOwnerRecord()->housekeeping_area_id)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required()
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn ($rule) => $rule->where(
                                    'housekeeping_plan_id',
                                    $this->getOwnerRecord()->getKey(),
                                ),
                            ),
                    ]),
            ]);
    }

    public function renderPhotoModal(HousekeepingPlanFacility $record, string $type): View
    {
        return view('filament.pages.housekeeping-facility-documentation', [
            'record' => $record,
            'type' => $type,
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('facility.name')
                    ->label('Fasilitas')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('facility.code')
                    ->label('Kode')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('tasks_count')
                    ->label('Pekerjaan')
                    ->state(fn (HousekeepingPlanFacility $record): int => HousekeepingPlanTask::query()
                        ->where('housekeeping_plan_id', $record->housekeeping_plan_id)
                        ->where('housekeeping_facility_id', $record->housekeeping_facility_id)
                        ->count())
                    ->badge()
                    ->color('gray'),
                TextColumn::make('photo_before')
                    ->label('Foto Before')
                    ->badge()
                    ->getStateUsing(fn (HousekeepingPlanFacility $record): string => count($record->photo_before ?? []).' foto')
                    ->color(fn (HousekeepingPlanFacility $record): string => count($record->photo_before ?? []) > 0 ? 'success' : 'danger'),
                TextColumn::make('photo_after')
                    ->label('Foto After')
                    ->badge()
                    ->getStateUsing(fn (HousekeepingPlanFacility $record): string => count($record->photo_after ?? []).' foto')
                    ->color(fn (HousekeepingPlanFacility $record): string => count($record->photo_after ?? []) > 0 ? 'success' : 'danger'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Fasilitas')
                    ->visible(fn (): bool => HousekeepingRole::canManagePlan(auth()->user())),
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
                            ->directory('housekeeping/plan-facilities/before')
                            ->visibility('public')
                            ->required(),
                    ])
                    ->action(function (HousekeepingPlanFacility $record, array $data): void {
                        $existing = $record->photo_before ?? [];
                        $record->update([
                            'photo_before' => array_values(array_slice([...$existing, ...($data['photos'] ?? [])], 0, 5)),
                        ]);
                    })
                    ->successNotificationTitle('Foto before tersimpan'),
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
                            ->directory('housekeeping/plan-facilities/after')
                            ->visibility('public')
                            ->required(),
                    ])
                    ->action(function (HousekeepingPlanFacility $record, array $data): void {
                        $existing = $record->photo_after ?? [];
                        $record->update([
                            'photo_after' => array_values(array_slice([...$existing, ...($data['photos'] ?? [])], 0, 5)),
                        ]);
                    })
                    ->successNotificationTitle('Foto after tersimpan'),
                Action::make('view_before')
                    ->label('Lihat Before')
                    ->icon('heroicon-o-eye')
                    ->visible(fn (HousekeepingPlanFacility $record): bool => count($record->photo_before ?? []) > 0)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (HousekeepingPlanFacility $record) => $this->renderPhotoModal($record, 'before')),
                Action::make('view_after')
                    ->label('Lihat After')
                    ->icon('heroicon-o-eye')
                    ->visible(fn (HousekeepingPlanFacility $record): bool => count($record->photo_after ?? []) > 0)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (HousekeepingPlanFacility $record) => $this->renderPhotoModal($record, 'after')),
            ])
            ->emptyStateHeading('Belum ada fasilitas terdokumentasi')
            ->emptyStateDescription('Fasilitas muncul otomatis dari item checklist plan.');
    }
}
