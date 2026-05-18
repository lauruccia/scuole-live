<?php

namespace App\Filament\Resources\CourseMaterialResource\Pages;

use App\Filament\Resources\CourseMaterialResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCourseMaterial extends CreateRecord
{
    protected static string $resource = CourseMaterialResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
