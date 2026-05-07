<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use Filament\Resources\Pages\ListRecords;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        // Le azioni di export sono già definite nella table()
        return [];
    }

    public function getTitle(): string
    {
        return 'Audit log';
    }

    public function getSubheading(): ?string
    {
        return 'Tracciamento modifiche su dati personali (GDPR), pagamenti, contratti, utenti e permessi.';
    }
}
