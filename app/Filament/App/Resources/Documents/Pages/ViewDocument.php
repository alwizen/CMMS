<?php

namespace App\Filament\App\Resources\Documents\Pages;

use App\Filament\App\Resources\Documents\DocumentResource;
use App\Filament\App\Resources\Documents\Schemas\DocumentInfolist;
use App\Models\Document;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\URL;

class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;

    public function resolveRecord(int|string $key): Document
    {
        return Document::query()
            ->with(['equipment.equipmentType', 'uploadedBy'])
            ->findOrFail($key);
    }

    public function infolist(Schema $schema): Schema
    {
        return DocumentInfolist::configure($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open_file')
                ->label('Buka File')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->url(fn (): string => URL::temporarySignedRoute(
                    'documents.file',
                    now()->addMinutes(30),
                    ['document' => $this->getRecord()->getKey()],
                ))
                ->openUrlInNewTab()
                ->visible(fn (): bool => filled($this->getRecord()->file_path)),
        ];
    }
}
