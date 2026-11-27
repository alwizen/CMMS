<?php

namespace App\Filament\Resources\Findings\Schemas;

use App\Models\WorkOrderFinding;
use App\Support\Media;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FindingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Temuan')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('finding_number')
                            ->label('No. Temuan')
                            ->copyable(),
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
                        TextEntry::make('severity')
                            ->label('Severity')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Critical' => 'danger',
                                'High' => 'warning',
                                'Medium' => 'info',
                                default => 'gray',
                            }),
                        TextEntry::make('answer_type')
                            ->label('Tipe Jawaban')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => WorkOrderFinding::ANSWER_TYPES[$state] ?? $state),
                        TextEntry::make('result')
                            ->label('Hasil Inspeksi')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'Poor' => 'warning',
                                'Not Working' => 'danger',
                                default => 'gray',
                            })
                            ->placeholder('-'),
                        TextEntry::make('description')
                            ->label('Deskripsi')
                            ->columnSpanFull()
                            ->placeholder('-'),
                        TextEntry::make('notes')
                            ->label('Catatan Tindak Lanjut')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ])
                    ->columns(3),

                Section::make('Asal & Tindak Lanjut')
                    ->icon('heroicon-o-link')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('workOrder.work_order_number')
                            ->label('Work Order Asal')
                            ->placeholder('-'),
                        TextEntry::make('equipment.tag_number')
                            ->label('Equipment')
                            ->placeholder('-'),
                        TextEntry::make('reportedBy.name')
                            ->label('Dilaporkan Oleh')
                            ->placeholder('-'),
                        TextEntry::make('maintenanceRequest.request_number')
                            ->label('Request Perbaikan')
                            ->placeholder('-'),
                        TextEntry::make('resolutionWorkOrder.work_order_number')
                            ->label('WO Perbaikan')
                            ->placeholder('-'),
                        TextEntry::make('resolved_at')
                            ->label('Selesai Pada')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                    ])
                    ->columns(3),

                Section::make('Foto Dokumentasi')
                    ->icon('heroicon-o-photo')
                    ->columnSpanFull()
                    ->schema([
                        ImageEntry::make('photos')
                            ->hiddenLabel()
                            ->disk(Media::disk())
                            ->stacked()
                            ->limit(3)
                            ->placeholder('Tidak ada foto.'),
                    ]),
            ]);
    }
}
