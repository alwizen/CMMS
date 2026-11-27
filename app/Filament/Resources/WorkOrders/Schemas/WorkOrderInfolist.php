<?php

namespace App\Filament\Resources\WorkOrders\Schemas;

use App\Models\Activity;
use App\Models\WorkOrder;
use App\Models\WorkOrderActivity;
use App\Models\WorkOrderFinding;
use App\Support\Media;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WorkOrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sedang Dikerjakan')
                    ->icon('heroicon-o-lock-closed')
                    ->columnSpanFull()
                    ->description('Work order ini sudah diambil petugas lain. Anda hanya dapat melihat sampai pekerjaan selesai atau ditunda.')
                    ->schema([
                        TextEntry::make('technicianCoordinator.name')
                            ->label('Dikerjakan oleh')
                            ->badge()
                            ->color('warning')
                            ->placeholder('-'),
                        TextEntry::make('start_at')
                            ->label('Mulai')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'In Progress' => 'warning',
                                default => 'gray',
                            }),
                    ])
                    ->columns(3)
                    ->visible(fn (WorkOrder $record): bool => $record->technician_coordinator_id !== null
                        && $record->status === WorkOrder::STATUSES['In Progress']),

                Section::make('Informasi Work Order')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('issuedBy.name')
                            ->label('Pemberi Tugas')
                            ->placeholder('-'),
                        TextEntry::make('work_order_number')
                            ->label('Work Order Number')
                            ->placeholder('-')
                            ->copyable(),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'Completed' => 'success',
                                'In Progress' => 'info',
                                'Cancelled' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('classification')
                            ->label('Classification')
                            ->badge(),
                        TextEntry::make('interval')
                            ->label('Interval')
                            ->formatStateUsing(fn (?string $state): ?string => $state ? (Activity::INTERVALS[$state] ?? $state) : null)
                            ->placeholder('-'),
                        TextEntry::make('equipment.tag_number')
                            ->label('Equipment')
                            ->placeholder('-'),
                        TextEntry::make('equipment.equipmentType.name')
                            ->label('Equipment Type')
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

                Section::make('Personel & Jadwal')
                    ->icon('heroicon-o-user-group')
                    ->columnSpanFull()
                    ->schema([

                        TextEntry::make('fungsi')
                            ->label('Fungsi')
                            ->badge()
                            ->placeholder('-'),
                        TextEntry::make('technicianCoordinator.name')
                            ->label('Performed By')
                            ->placeholder('Belum diambil'),
                        TextEntry::make('start_at')
                            ->label('Mulai')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('finish_at')
                            ->label('Selesai')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('expired_at')
                            ->label('Expired')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-')
                            ->badge()
                            ->color(fn ($record): string => $record?->isExpired() ? 'danger' : 'gray'),
                        TextEntry::make('note')
                            ->label('Catatan')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(4),

                Section::make('Review SPV MOS')
                    ->icon('heroicon-o-shield-check')
                    ->columnSpanFull()
                    ->description('Work order yang selesai dikerjakan teknisi harus direview SPV MOS sebelum dianggap tuntas.')
                    ->schema([
                        TextEntry::make('approval_status')
                            ->label('Status Review')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => WorkOrder::APPROVAL_STATUSES[$state] ?? $state)
                            ->color(fn (string $state): string => match ($state) {
                                'Approved' => 'success',
                                'Rejected' => 'danger',
                                default => 'warning',
                            }),
                        TextEntry::make('approvedBy.name')
                            ->label('Direview Oleh')
                            ->placeholder('-'),
                        TextEntry::make('approved_at')
                            ->label('Waktu Review')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('approval_notes')
                            ->label('Catatan Review')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->visible(fn (WorkOrder $record): bool => $record->status === WorkOrder::STATUSES['Completed']
                        || $record->approval_status !== WorkOrder::APPROVAL_PENDING),

                Section::make('Checklist & Hasil Eksekusi')
                    ->icon('heroicon-o-check-circle')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('workOrderActivities')
                            ->hiddenLabel()
                            ->placeholder('Tidak ada item checklist.')
                            ->table([
                                TableColumn::make('Activity'),
                                TableColumn::make('Reference'),
                                TableColumn::make('Pre Inspection'),
                                TableColumn::make('Final Result'),
                                TableColumn::make('Unit'),
                                TableColumn::make('Executed'),
                                TableColumn::make('Note'),
                                TableColumn::make('Foto'),
                                TableColumn::make('Waktu Eksekusi'),
                            ])
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Activity')
                                    ->state(fn (WorkOrderActivity $record): string => $record->displayName())
                                    ->placeholder('-')
                                    ->bulleted(),
                                TextEntry::make('reference')
                                    ->label('Reference')
                                    ->placeholder('-'),
                                TextEntry::make('pre_inspection')
                                    ->label('Pre Inspection')
                                    ->placeholder('-'),
                                TextEntry::make('final_result')
                                    ->label('Result')
                                    ->placeholder('-'),
                                TextEntry::make('unit')
                                    ->label('Unit')
                                    ->placeholder('-'),
                                IconEntry::make('executed')
                                    ->label('Executed')
                                    ->boolean(),
                                TextEntry::make('note')
                                    ->label('Note')
                                    ->placeholder('-'),
                                ImageEntry::make('photos')
                                    ->label('Foto')
                                    ->disk(Media::disk())
                                    ->stacked()
                                    ->limit(3),
                                TextEntry::make('executed_at')
                                    ->label('Waktu Eksekusi')
                                    ->dateTime('d M Y H:i')
                                    ->placeholder('-'),
                            ]),
                    ]),

                Section::make('Temuan')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->columnSpanFull()
                    ->description('Temuan terbentuk otomatis dari hasil checklist bermasalah (Poor/Not Working atau di luar batas).')
                    ->schema([
                        RepeatableEntry::make('findings')
                            ->hiddenLabel()
                            ->placeholder('Tidak ada temuan.')
                            ->columnSpanFull()
                            ->table([
                                TableColumn::make('No. Temuan'),
                                TableColumn::make('Aktivitas'),
                                TableColumn::make('Hasil'),
                                TableColumn::make('Severity'),
                                TableColumn::make('Status'),
                                TableColumn::make('Tindak Lanjut'),
                            ])
                            ->schema([
                                TextEntry::make('finding_number')
                                    ->label('No. Temuan'),
                                TextEntry::make('activity_name')
                                    ->label('Aktivitas')
                                    ->state(fn (WorkOrderFinding $record): string => $record->displayName()),
                                TextEntry::make('result')
                                    ->label('Hasil')
                                    ->placeholder('-'),
                                TextEntry::make('severity')
                                    ->label('Severity')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'Critical' => 'danger',
                                        'High' => 'warning',
                                        'Medium' => 'info',
                                        default => 'gray',
                                    }),
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => WorkOrderFinding::STATUSES[$state] ?? $state)
                                    ->color(fn (string $state): string => match ($state) {
                                        'Open' => 'warning',
                                        'Requested' => 'info',
                                        'Resolved' => 'success',
                                        default => 'gray',
                                    }),
                                TextEntry::make('maintenanceRequest.request_number')
                                    ->label('Tindak Lanjut')
                                    ->placeholder('-'),
                            ]),
                    ]),

                Section::make('Pelaksana')
                    ->icon('heroicon-o-users')
                    ->columnSpanFull()
                    ->description('Terisi otomatis saat petugas menekan "Mulai Pekerjaan" di panel lapangan.')
                    ->schema([
                        TextEntry::make('technicianCoordinator.name')
                            ->label('Performed By')
                            ->placeholder('Belum diambil'),
                        RepeatableEntry::make('workOrderWorkers')
                            ->hiddenLabel()
                            ->placeholder('Belum ada pelaksana tercatat.')
                            ->columnSpanFull()
                            ->table([
                                TableColumn::make('Pelaksana'),
                                TableColumn::make('Fungsi'),
                                TableColumn::make('Mulai'),
                                TableColumn::make('Selesai'),
                                TableColumn::make('Note'),
                            ])
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('Pelaksana')
                                    ->placeholder('-'),
                                TextEntry::make('user.fungsi')
                                    ->label('Fungsi')
                                    ->placeholder('-'),
                                TextEntry::make('started_at')
                                    ->label('Mulai')
                                    ->dateTime('d M Y H:i')
                                    ->placeholder('-'),
                                TextEntry::make('finished_at')
                                    ->label('Selesai')
                                    ->dateTime('d M Y H:i')
                                    ->placeholder('-'),
                                TextEntry::make('note')
                                    ->label('Note')
                                    ->placeholder('-'),
                            ]),
                    ]),
            ]);
    }
}
