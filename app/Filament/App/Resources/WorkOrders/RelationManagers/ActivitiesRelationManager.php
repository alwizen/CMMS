<?php

namespace App\Filament\App\Resources\WorkOrders\RelationManagers;

use App\Models\WorkOrder;
use App\Models\WorkOrderActivity;
use App\Support\ImageCompressor;
use App\Support\Media;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'workOrderActivities';

    protected static ?string $title = 'Checklist & Hasil Eksekusi';

    protected static ?string $modelLabel = 'activity result';

    protected static bool $isLazy = false;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    /**
     * Checklist dikerjakan langsung dari halaman detail WO (View page).
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Hasil Pengerjaan')
                    ->columnSpanFull()
                    ->description('Checklist kualitatif memakai pilihan kondisi; kuantitatif memakai isian bebas.')
                    ->schema([
                        Select::make('pre_inspection')
                            ->label('Pre Inspection')
                            ->options(fn (?WorkOrderActivity $record): array => WorkOrderActivity::qualitativeOptions(
                                $record?->pre_inspection,
                            ))
                            ->searchable()
                            ->nullable()
                            ->visible(fn (?WorkOrderActivity $record): bool => WorkOrderActivity::isQualitative($record?->activity_id)),
                        TextInput::make('pre_inspection')
                            ->label('Pre Inspection')
                            ->maxLength(100)
                            ->nullable()
                            ->visible(fn (?WorkOrderActivity $record): bool => ! WorkOrderActivity::isQualitative($record?->activity_id)),
                        Select::make('final_result')
                            ->label('Final Result')
                            ->options(fn (?WorkOrderActivity $record): array => WorkOrderActivity::qualitativeOptions(
                                $record?->final_result,
                            ))
                            ->searchable()
                            ->nullable()
                            ->visible(fn (?WorkOrderActivity $record): bool => WorkOrderActivity::isQualitative($record?->activity_id)),
                        TextInput::make('final_result')
                            ->label('Final Result')
                            ->maxLength(100)
                            ->nullable()
                            ->visible(fn (?WorkOrderActivity $record): bool => ! WorkOrderActivity::isQualitative($record?->activity_id)),
                        Toggle::make('executed')
                            ->label('Tandai sudah dikerjakan')
                            ->default(true),
                        Textarea::make('note')
                            ->label('Catatan')
                            ->nullable()
                            ->columnSpanFull(),
                        FileUpload::make('photos')
                            ->label('Foto Dokumentasi (opsional, maks. 3)')
                            ->multiple()
                            ->maxFiles(3)
                            ->image()
                            ->disk(Media::disk())
                            ->directory('work-order-activities')
                            ->saveUploadedFileUsing(ImageCompressor::storeFilamentUpload(...))
                            ->reorderable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Activity')
                    ->state(fn (WorkOrderActivity $record): string => $record->displayName())
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reference')
                    ->label('Reference')
                    ->toggleable(),
                TextColumn::make('pre_inspection')
                    ->label('Pre Inspection')
                    ->toggleable(),
                TextColumn::make('final_result')
                    ->label('Final Result')
                    ->toggleable(),
                TextColumn::make('unit')
                    ->label('Unit')
                    ->toggleable(),
                IconColumn::make('executed')
                    ->label('Executed')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('photos')
                    ->label('Foto')
                    ->badge()
                    // State kolom array dirender per item, jadi hitung lewat record.
                    ->state(fn (WorkOrderActivity $record): string => count($record->photos ?? []).' foto')
                    ->color(fn (WorkOrderActivity $record): string => count($record->photos ?? []) > 0 ? 'success' : 'gray')
                    // Klik badge foto membuka modal "Lihat Foto".
                    ->action(Action::make('photos')),
            ])
            ->recordActions([
                Action::make('photos')
                    ->label('Lihat Foto')
                    ->icon('heroicon-o-photo')
                    ->color('gray')
                    ->modalHeading('Foto Dokumentasi')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalWidth(Width::ThreeExtraLarge)
                    ->modalContent(fn (WorkOrderActivity $record) => view('filament.work-order-activity-photos', [
                        'record' => $record,
                    ]))
                    ->visible(fn (WorkOrderActivity $record): bool => count($record->photos ?? []) > 0),
                EditAction::make()
                    ->label('Isi Checklist')
                    ->icon('heroicon-o-pencil-square')
                    ->button()
                    ->visible(fn (WorkOrderActivity $record): bool => (bool) auth()->user()?->can('Update:WorkOrder')
                        && (bool) $record->workOrder?->isExecutable()
                        && in_array($record->workOrder?->status, [
                            WorkOrder::STATUSES['Pending'],
                            WorkOrder::STATUSES['In Progress'],
                        ], true)
                        && ! $record->workOrder?->isLockedFor(auth()->user()))
                    ->after(function (WorkOrderActivity $record): void {
                        if ($record->executed) {
                            $record->workOrder?->claimBy(auth()->id());
                        }
                    }),
            ])
            ->emptyStateHeading('Belum ada item checklist')
            ->emptyStateDescription('Checklist diisi SPV saat approval request. Hubungi SPV bila item pekerjaan belum ada.');
    }
}
