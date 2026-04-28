<?php

namespace App\Filament\Teacher\Resources\TeacherMaterialResource\Pages;

use App\Filament\Teacher\Resources\TeacherMaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeacherMaterials extends ListRecords
{
    protected static string $resource = TeacherMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Carica materiale'),
        ];
    }
}
