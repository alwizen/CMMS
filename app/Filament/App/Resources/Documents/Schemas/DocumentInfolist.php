<?php

namespace App\Filament\App\Resources\Documents\Schemas;

use App\Models\Document;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class DocumentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Dokumen')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('document_number')
                            ->label('Document Number')
                            ->copyable(),
                        TextEntry::make('title')
                            ->label('Judul'),
                        TextEntry::make('document_type')
                            ->label('Tipe')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => Document::TYPES[$state] ?? $state),
                        TextEntry::make('revision')
                            ->label('Revisi')
                            ->placeholder('-'),
                        TextEntry::make('issuer')
                            ->label('Penerbit')
                            ->placeholder('-'),
                        TextEntry::make('issue_date')
                            ->label('Tanggal Terbit')
                            ->date('d M Y')
                            ->placeholder('-'),
                        TextEntry::make('expiry_date')
                            ->label('Tanggal Kedaluwarsa')
                            ->date('d M Y')
                            ->placeholder('-'),
                        TextEntry::make('expiry_status')
                            ->label('Status Kedaluwarsa')
                            ->state(fn (Document $record): string => $record->expiryStatus())
                            ->formatStateUsing(fn (string $state): string => Document::EXPIRY_STATUSES[$state] ?? $state)
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'valid' => 'success',
                                'expiring_soon' => 'warning',
                                'expired' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('description')
                            ->label('Deskripsi')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Equipment')
                    ->icon(Heroicon::OutlinedWrenchScrewdriver)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('equipment.tag_number')
                            ->label('Tag Number')
                            ->placeholder('-'),
                        TextEntry::make('equipment.equipmentType.name')
                            ->label('Equipment Type')
                            ->placeholder('-'),
                        TextEntry::make('equipment.area.name')
                            ->label('Area')
                            ->placeholder('-'),
                    ])
                    ->columns(3),

                Section::make('File')
                    ->icon(Heroicon::OutlinedPaperClip)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('file_path')
                            ->label('File')
                            ->placeholder('Tidak ada file')
                            ->helperText('Gunakan tombol "Buka File" di kanan atas untuk membuka dokumen.'),
                        TextEntry::make('uploadedBy.name')
                            ->label('Diunggah Oleh')
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->label('Diunggah Pada')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                    ])
                    ->columns(3),
            ]);
    }
}
