<?php

namespace App\Filament\Studente\Resources\StudentInstallmentResource\Pages;

use App\Filament\Studente\Resources\StudentInstallmentResource;
use Filament\Resources\Pages\ListRecords;

class ListStudentInstallments extends ListRecords
{
    protected static string $resource = StudentInstallmentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}