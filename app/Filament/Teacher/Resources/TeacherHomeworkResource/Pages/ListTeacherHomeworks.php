<?php

namespace App\Filament\Teacher\Resources\TeacherHomeworkResource\Pages;

use App\Filament\Teacher\Resources\TeacherHomeworkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeacherHomeworks extends ListRecords
{
    protected static string $resource = TeacherHomeworkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nuovo compito'),
        ];
    }
}
