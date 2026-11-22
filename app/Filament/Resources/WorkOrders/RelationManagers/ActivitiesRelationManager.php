<?php

namespace App\Filament\Resources\WorkOrders\RelationManagers;

use App\Models\Activity;
use App\Models\WorkOrderActivity;
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
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'workOrderActivities';

    protected static ?string $title = 'Checklist & Hasil Eksekusi';

    protected static ?string $modelLabel = 'activity result';

    protected static bool $isLazy = false;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Item Pekerjaan')
                    ->schema([
                        Select::make('activity_id')
                            ->label('Activity (checklist)')
                            ->options(fn (): array => Activity::query()
                                ->with('equipmentType')
                                ->where('status', true)
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (Activity $activity): array => [
                                    $activity->id => $activity->name.' ('.$activity->equipmentType?->name.')',
                                ])
                                ->all())
                            ->searchable()
                            ->live()
                            ->nullable()
                            ->placeholder('Pilih dari checklist atau isi nama manual di samping'),
                        TextInput::make('name')
                            ->label('Nama Manual')
                            ->nullable()
                            ->placeholder('Isi bila bukan dari checklist')
                            ->helperText('Dipakai bila aktivitas diketik sendiri.'),
                        TextInput::make('reference')
                            ->label('Reference')
                            ->nullable(),
                        TextInput::make('unit')
                            ->label('Unit')
                            ->nullable(),
                    ])
                    ->columns(2),
                Section::make('Hasil Pengerjaan')
                    ->description('Checklist kualitatif memakai pilihan kondisi; kuantitatif memakai isian bebas.')
                    ->schema([
                        Select::make('pre_inspection')
                            ->label('Pre Inspection')
                            ->options(fn (Get $get, ?WorkOrderActivity $record): array => WorkOrderActivity::qualitativeOptions(
                                $get('pre_inspection') ?? $record?->pre_inspection,
                            ))
                            ->searchable()
                            ->nullable()
                            ->visible(fn (Get $get): bool => WorkOrderActivity::isQualitative($this->activityIdFrom($get))),
                        TextInput::make('pre_inspection')
                            ->label('Pre Inspection')
                            ->nullable()
                            ->visible(fn (Get $get): bool => ! WorkOrderActivity::isQualitative($this->activityIdFrom($get))),
                        Select::make('final_result')
                            ->label('Final Result')
                            ->options(fn (Get $get, ?WorkOrderActivity $record): array => WorkOrderActivity::qualitativeOptions(
                                $get('final_result') ?? $record?->final_result,
                            ))
                            ->searchable()
                            ->nullable()
                            ->visible(fn (Get $get): bool => WorkOrderActivity::isQualitative($this->activityIdFrom($get))),
                        TextInput::make('final_result')
                            ->label('Final Result')
                            ->nullable()
                            ->visible(fn (Get $get): bool => ! WorkOrderActivity::isQualitative($this->activityIdFrom($get))),
                        TextInput::make('follow_up')
                            ->label('Follow Up')
                            ->nullable(),
                        Toggle::make('executed')
                            ->label('Executed')
                            ->default(false),
                        Textarea::make('note')
                            ->label('Note')
                            ->nullable()
                            ->columnSpanFull(),
                        FileUpload::make('photos')
                            ->label('Foto Dokumentasi (opsional, maks. 3)')
                            ->multiple()
                            ->maxFiles(3)
                            ->image()
                            ->disk('public')
                            ->directory('work-order-activities')
                            ->visibility('public')
                            ->reorderable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Activity yang sedang dipilih pada form (null = aktivitas manual).
     */
    protected function activityIdFrom(Get $get): ?int
    {
        $value = $get('activity_id');

        return filled($value) ? (int) $value : null;
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
            ->headerActions([
                CreateAction::make(),
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
