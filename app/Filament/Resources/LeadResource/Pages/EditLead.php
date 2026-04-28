<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Models\Lead;
use App\Models\LeadActivity;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    /** Stato prima del salvataggio, per rilevare cambio status */
    protected ?string $statusBefore = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->statusBefore = $this->record->status;
        return $data;
    }

    protected function afterSave(): void
    {
        $newStatus = $this->record->status;

        // Registra automaticamente cambio di stato nella timeline
        if ($this->statusBefore && $this->statusBefore !== $newStatus) {
            LeadActivity::create([
                'lead_id'     => $this->record->id,
                'user_id'     => auth()->id(),
                'type'        => 'status_change',
                'subject'     => 'Cambio stato',
                'body'        => sprintf(
                    'Stato cambiato da "%s" a "%s".',
                    Lead::STATUSES[$this->statusBefore] ?? $this->statusBefore,
                    Lead::STATUSES[$newStatus] ?? $newStatus,
                ),
                'from_status' => $this->statusBefore,
                'to_status'   => $newStatus,
                'occurred_at' => now(),
            ]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            // Action "Converti in studente" — visibile solo se non già convertito
            Actions\Action::make('convert')
                ->label('Converti in studente')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->visible(fn () => ! $this->record->isConverted() && $this->record->status !== 'lost')
                ->requiresConfirmation()
                ->modalHeading('Converti lead in studente')
                ->modalDescription('Verrà creato un nuovo studente con i dati del lead. Potrai poi creare il contratto dalla scheda studente.')
                ->action(function () {
                    $lead = $this->record;

                    // Crea lo studente
                    $student = \App\Models\Student::create([
                        'first_name' => $lead->first_name,
                        'last_name'  => $lead->last_name,
                        'email'      => $lead->email,
                        'phone'      => $lead->phone,
                        'notes'      => $lead->notes,
                    ]);

                    // Aggiorna il lead
                    $lead->update([
                        'status'               => 'enrolled',
                        'converted_student_id' => $student->id,
                        'converted_at'         => now(),
                    ]);

                    // Registra attività
                    LeadActivity::create([
                        'lead_id'     => $lead->id,
                        'user_id'     => auth()->id(),
                        'type'        => 'status_change',
                        'subject'     => 'Convertito in studente',
                        'body'        => "Lead convertito in studente (ID: {$student->id}).",
                        'from_status' => $this->statusBefore ?? $lead->status,
                        'to_status'   => 'enrolled',
                        'occurred_at' => now(),
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Lead convertito!')
                        ->body("Studente #{$student->id} creato con successo.")
                        ->success()
                        ->send();

                    // Redirect alla scheda studente
                    $this->redirect(
                        \App\Filament\Resources\StudentResource::getUrl('edit', ['record' => $student])
                    );
                }),

            Actions\DeleteAction::make(),
        ];
    }
}
