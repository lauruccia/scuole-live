<?php

namespace App\Filament\Resources\ClosureDayResource\Pages;

use App\Filament\Resources\ClosureDayResource;
use App\Models\ClosureDay;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateClosureDay extends CreateRecord
{
    protected static string $resource = ClosureDayResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Normalizza end_date
        if (empty($data['end_date'])) {
            $data['end_date'] = $data['start_date'] ?? null;
        }

        // Sicurezza range invertito
        if (!empty($data['start_date']) && !empty($data['end_date']) && $data['end_date'] < $data['start_date']) {
            $data['end_date'] = $data['start_date'];
        }

        // ✅ BLOCCO SOVRAPPOSIZIONI
        $start = $data['start_date'] ?? null;
        $end   = $data['end_date'] ?? null;

        if ($start && $end) {
            $overlap = ClosureDay::query()
                ->whereDate('start_date', '<=', $end)
                ->whereDate('end_date', '>=', $start)
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'start_date' => 'Intervallo in sovrapposizione con un altro giorno di chiusura già inserito.',
                    'end_date'   => 'Intervallo in sovrapposizione con un altro giorno di chiusura già inserito.',
                ]);
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var ClosureDay $record */
        $record = $this->record;
        $count = ClosureDayResource::countAffectedLessons($record);

        if ($count > 0) {
            Notification::make()
                ->title('Periodo di chiusura salvato')
                ->body("⚠️ {$count} lezioni future cadono in questo periodo. Usa il pulsante «Rigenera lezioni» nella lista per spostarle automaticamente.")
                ->warning()
                ->persistent()
                ->send();
        }
    }
}
