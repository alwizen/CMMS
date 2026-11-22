<?php

namespace App\Filament\App\Resources\Documents;

use App\Filament\App\Resources\Documents\Pages\ListDocuments;
use App\Filament\App\Resources\Documents\Pages\ViewDocument;
use App\Filament\App\Resources\Documents\Schemas\DocumentInfolist;
use App\Filament\App\Resources\Documents\Tables\DocumentsTable;
use App\Models\Document;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Arsip dokumen untuk petugas lapangan: hanya lihat & unduh.
 * Izin menu tetap diatur Shield (ViewAny:Document / View:Document).
 */
class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationLabel = 'Dokumen';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Aset';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return DocumentsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DocumentInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocuments::route('/'),
            'view' => ViewDocument::route('/{record}'),
        ];
    }
}
