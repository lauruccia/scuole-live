<?php

namespace App\Filament\Resources\LessonResource\Pages;

use App\Filament\Resources\LessonResource;
use Filament\Resources\Pages\ListRecords;

class ListLessons extends ListRecords
{
    protected static string $resource = LessonResource::class;

    protected function shouldPersistTableFiltersInSession(): bool
    {
        return false; // ogni volta riparte dai default
    }

    protected function getDefaultTableFilters(): ?array
    {
        return [
            'upcoming' => [
                'isActive' => true,
            ],
        ];
    }
}
