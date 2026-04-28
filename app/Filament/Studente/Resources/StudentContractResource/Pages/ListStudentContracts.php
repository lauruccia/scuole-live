<?php

namespace App\Filament\Studente\Resources\StudentContractResource\Pages;

use App\Filament\Studente\Resources\StudentContractResource;
use Filament\Resources\Pages\ListRecords;

class ListStudentContracts extends ListRecords
{
    protected static string $resource = StudentContractResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}