<?php

namespace App\Filament\Resources\CourseMaterialResource\Pages;

use App\Filament\Resources\CourseMaterialResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateCourseMaterial extends CreateRecord
{
    protected static string $resource = CourseMaterialResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uploaded_by'] = Auth::id();

        // Rimuovi campo virtuale (non è una colonna DB)
        unset($data['content_type']);

        if (! empty($data['file_path'])) {
            $data['file_name'] = basename($data['file_path']);
            $fullPath = storage_path('app/public/' . $data['file_path']);
            if (file_exists($fullPath)) {
                $data['file_size'] = filesize($fullPath);
                $data['file_mime'] = mime_content_type($fullPath) ?: null;
            }
        }

        // Se è un link, azzera i campi file
        if (! empty($data['external_url'])) {
            $data['file_path'] = null;
            $data['file_name'] = null;
            $data['file_size'] = null;
            $data['file_mime'] = null;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
