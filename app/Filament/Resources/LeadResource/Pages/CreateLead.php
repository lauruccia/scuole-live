<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Models\LeadActivity;
use Filament\Resources\Pages\CreateRecord;

class CreateLead extends CreateRecord
{
    protected static string $resource = LeadResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function afterCreate(): void
    {
        // Registra attività automatica di creazione
        LeadActivity::create([
            'lead_id'     => $this->record->id,
            'user_id'     => auth()->id(),
            'type'        => 'note',
            'subject'     => 'Lead creato',
            'body'        => 'Lead inserito nel sistema.',
            'occurred_at' => now(),
        ]);
    }
}
