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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
