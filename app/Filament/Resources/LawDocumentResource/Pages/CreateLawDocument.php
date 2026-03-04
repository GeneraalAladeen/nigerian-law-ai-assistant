<?php

namespace App\Filament\Resources\LawDocumentResource\Pages;

use App\Filament\Resources\LawDocumentResource;
use App\Jobs\ProcessLawDocument;
use Filament\Resources\Pages\CreateRecord;

class CreateLawDocument extends CreateRecord
{
    protected static string $resource = LawDocumentResource::class;

    protected function afterCreate(): void
    {
        ProcessLawDocument::dispatch($this->record->id);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = 'pending';

        return $data;
    }
}
