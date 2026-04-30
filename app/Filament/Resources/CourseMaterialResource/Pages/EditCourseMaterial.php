<?php

namespace App\Filament\Resources\CourseMaterialResource\Pages;

use App\Filament\Resources\CourseMaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCourseMaterial extends EditRecord
{
    protected static string $resource = CourseMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Precompila il radio button in base a ciò che esiste nel record
        $data['content_type'] = ! empty($data['external_url']) ? 'link' : 'file';
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['content_type']);

        if (! empty($data['external_url'])) {
            // È un link: svuota i campi file
            $data['file_path'] = null;
            $data['file_name'] = null;
            $data['file_size'] = null;
            $data['file_mime'] = null;
        } elseif (! empty($data['file_path'])) {
            $data['file_name'] = basename($data['file_path']);
            $fullPath = storage_path('app/public/' . $data['file_path']);
            if (file_exists($fullPath)) {
                $data['file_size'] = filesize($fullPath);
                $data['file_mime'] = mime_content_type($fullPath) ?: null;
            }
            $data['external_url'] = null;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
