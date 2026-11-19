<?php

namespace App\Filament\Resources\WorkOrders\Schemas;

use App\Models\Activity;
use App\Models\WorkOrderActivity;
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
                                'On Hold' => 'warning',
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
                                    ->disk('public')
                                    ->stacked()
                                    ->limit(3),
                                TextEntry::make('executed_at')
                                    ->label('Waktu Eksekusi')
                                    ->dateTime('d M Y H:i')
                                    ->placeholder('-'),
                            ]),
                    ]),

                Section::make('Teknisi Pelaksana')
                    ->icon('heroicon-o-users')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('technicianCoordinator.name')
                            ->label('Pelaksana (Penanggung Jawab Equipment Type)')
                            ->placeholder('-'),
                        RepeatableEntry::make('workOrderWorkers')
                            ->hiddenLabel()
                            ->placeholder('Tidak ada teknisi tambahan.')
                            ->columnSpanFull()
                            ->table([
                                TableColumn::make('Teknisi'),
                                TableColumn::make('Role'),
                                TableColumn::make('Mulai'),
                                TableColumn::make('Selesai'),
                                TableColumn::make('Note'),
                            ])
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('Teknisi')
                                    ->placeholder('-'),
                                TextEntry::make('role')
                                    ->label('Role')
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
