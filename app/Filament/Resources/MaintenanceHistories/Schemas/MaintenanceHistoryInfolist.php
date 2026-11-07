<?php

namespace App\Filament\Resources\MaintenanceHistories\Schemas;

use App\Filament\Resources\WorkOrders\WorkOrderResource;
use App\Models\MaintenanceHistory;
use App\Support\Downtime;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class MaintenanceHistoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Utama')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->schema([
                        TextEntry::make('work_order_number')
                            ->label('WO Number')
                            ->placeholder('-')
                            ->url(fn (MaintenanceHistory $record): ?string => $record->work_order_id
                                ? WorkOrderResource::getUrl('edit', ['record' => $record->work_order_id])
                                : null)
                            ->color('primary'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (?string $state): string => match (strtolower((string) $state)) {
                                'completed' => 'success',
                                'in progress' => 'warning',
                                'pending' => 'info',
                                default => 'gray',
                            }),
                        TextEntry::make('maintenance_type')
                            ->label('Maintenance Type'),
                        TextEntry::make('classification')
                            ->badge()
                            ->color(fn (?string $state): string => match (strtolower((string) $state)) {
                                'preventive' => 'info',
                                'corrective' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('maintenance_date')
                            ->label('Maintenance Date')
                            ->dateTime('d M Y H:i'),
                        TextEntry::make('duration_minutes')
                            ->label('Duration')
                            ->formatStateUsing(fn ($state): string => static::formatDuration($state))
                            ->placeholder('-'),
                    ])
                    ->columns(3),

                Section::make('Equipment')
                    ->icon(Heroicon::OutlinedWrenchScrewdriver)
                    ->schema([
                        TextEntry::make('equipment.tag_number')
                            ->label('Tag Number'),
                        TextEntry::make('equipment.equipmentType.name')
                            ->label('Tipe')
                            ->placeholder('-'),
                        TextEntry::make('equipment.area.name')
                            ->label('Area')
                            ->placeholder('-'),
                        TextEntry::make('equipment.description')
                            ->label('Deskripsi Equipment')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Pelaksana')
                    ->icon(Heroicon::OutlinedUser)
                    ->schema([
                        TextEntry::make('performedBy.name')
                            ->label('Performed By')
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime('d M Y H:i'),
                        TextEntry::make('updated_at')
                            ->label('Diperbarui')
                            ->dateTime('d M Y H:i'),
                    ])
                    ->columns(3),

                Section::make('Pekerjaan')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->schema([
                        TextEntry::make('description')
                            ->label('Deskripsi')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make('Temuan & Tindakan')
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('findings')
                            ->label('Findings')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('actions_taken')
                            ->label('Actions Taken')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('notes')
                            ->label('Notes')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make('Downtime')
                    ->icon(Heroicon::OutlinedClock)
                    ->columnSpanFull()
                    ->description('Dihitung otomatis dari laporan kerusakan sampai work order selesai.')
                    ->schema([
                        TextEntry::make('downtime_start')
                            ->label('Mulai')
                            ->state(fn (MaintenanceHistory $record): ?string => $record->downtimeStart()?->format('d M Y H:i'))
                            ->placeholder('-'),
                        TextEntry::make('downtime_end')
                            ->label('Selesai')
                            ->state(fn (MaintenanceHistory $record): ?string => $record->downtimeEnd()?->format('d M Y H:i'))
                            ->placeholder('-'),
                        TextEntry::make('downtime_minutes')
                            ->label('Durasi')
                            ->state(fn (MaintenanceHistory $record): string => Downtime::formatMinutes($record->downtimeMinutes()))
                            ->badge()
                            ->color('danger'),
                    ])
                    ->columns(3)
                    ->visible(fn (MaintenanceHistory $record): bool => $record->downtimeMinutes() !== null),

                Section::make('Checklist & Hasil Eksekusi')
                    ->icon(Heroicon::OutlinedListBullet)
                    ->columnSpanFull()
                    ->description('Hasil pengerjaan per aktivitas dari Work Order terkait.')
                    ->schema([
                        RepeatableEntry::make('workOrder.workOrderActivities')
                            ->hiddenLabel()
                            ->placeholder('Tidak ada data checklist.')
                            ->table([
                                TableColumn::make('Activity'),
                                TableColumn::make('Reference'),
                                TableColumn::make('Executed'),
                                TableColumn::make('Pre Inspection'),
                                TableColumn::make('Final Result'),
                                TableColumn::make('Follow Up'),
                                TableColumn::make('Note'),
                                TableColumn::make('Foto Dokumentasi'),
                            ])
                            ->schema([
                                TextEntry::make('activity.name')
                                    ->label('Activity'),
                                TextEntry::make('reference')
                                    ->label('Reference')
                                    ->placeholder('-'),
                                IconEntry::make('executed')
                                    ->label('Executed')
                                    ->boolean(),
                                TextEntry::make('pre_inspection')
                                    ->label('Pre Inspection')
                                    ->placeholder('-'),
                                TextEntry::make('final_result')
                                    ->label('Final Result')
                                    ->placeholder('-'),
                                TextEntry::make('follow_up')
                                    ->label('Follow Up')
                                    ->placeholder('-'),
                                TextEntry::make('note')
                                    ->label('Note')
                                    ->placeholder('-')
                                    ->columnSpanFull(),
                                ImageEntry::make('photos')
                                    ->label('Foto Dokumentasi')
                                    ->disk('public')
                                    ->size(160)
                                    ->placeholder('-')
                                    ->columnSpanFull(),
                            ])
                            ->columns(6),
                    ])
                    ->visible(fn (MaintenanceHistory $record): bool => (bool) $record->workOrder?->workOrderActivities->isNotEmpty()),
            ]);
    }

    protected static function formatDuration(mixed $minutes): string
    {
        if (blank($minutes)) {
            return '-';
        }

        $minutes = (int) $minutes;
        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        if ($hours === 0) {
            return $remaining.' menit';
        }

        if ($remaining === 0) {
            return $hours.' jam';
        }

        return $hours.' jam '.$remaining.' menit';
    }
}
