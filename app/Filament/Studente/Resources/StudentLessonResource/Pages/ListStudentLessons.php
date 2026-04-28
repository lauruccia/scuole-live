<?php

namespace App\Filament\Studente\Resources\StudentLessonResource\Pages;

use App\Filament\Studente\Resources\StudentLessonResource;
use Filament\Resources\Pages\ListRecords;

class ListStudentLessons extends ListRecords
{
    protected static string $resource = StudentLessonResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}