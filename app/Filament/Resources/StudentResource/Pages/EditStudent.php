<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Models\Student;
use Filament\Actions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),

            // ── GDPR: Anonimizza studente ─────────────────────────────────
            Actions\Action::make('anonimizza')
                ->label('Anonimizza (GDPR)')
                ->icon('heroicon-o-shield-exclamation')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Anonimizza dati studente')
                ->modalIcon('heroicon-o-shield-exclamation')
                ->modalIconColor('danger')
                ->modalDescription(null)
                ->modalSubmitActionLabel('Anonimizza definitivamente')
                ->modalCancelActionLabel('Annulla')
                ->form([
                    Placeholder::make('avviso')
                        ->label('')
                        ->content('⚠️  Operazione IRREVERSIBILE. Tutti i dati personali verranno azzerati: nome, cognome, email, telefono, codice fiscale, dati di nascita, indirizzo di residenza, dati del genitore e note. Contratti e lezioni rimarranno ma senza riferimento ai dati personali.'),

                    TextInput::make('conferma')
                        ->label('Per confermare digita esattamente: ANONIMIZZA')
                        ->placeholder('ANONIMIZZA')
                        ->required()
                        ->rules(['in:ANONIMIZZA'])
                        ->validationMessages([
                            'in'       => 'Devi digitare esattamente "ANONIMIZZA" per confermare.',
                            'required' => 'La conferma è obbligatoria.',
                        ]),
                ])
                ->visible(function (): bool {
                    $u = auth()->user();
                    return $u?->hasAnyRole(['Superadmin', 'superadmin', 'super_admin']) ?? false;
                })
                ->action(function (array $data): void {
                    /** @var Student $student */
                    $student = $this->getRecord();
                    $anonId  = $student->id;

                    $student->update([
                        'first_name'         => 'Studente',
                        'last_name'          => 'Anonimizzato_' . $anonId,
                        'email'              => 'anonimizzato_' . $anonId . '@rimosso.invalid',
                        'phone'              => null,
                        'fiscal_code'        => null,
                        'birth_date'         => null,
                        'birth_place'        => null,
                        'birth_province'     => null,
                        'birth_country'      => null,
                        'residence_address'  => null,
                        'residence_zip'      => null,
                        'residence_city'     => null,
                        'residence_province' => null,
                        'residence_country'  => null,
                        'parent_first_name'  => null,
                        'parent_last_name'   => null,
                        'parent_email'       => null,
                        'parent_phone'       => null,
                        'notes'              => null,
                    ]);

                    // Log GDPR via spatie/laravel-activitylog
                    activity()
                        ->causedBy(auth()->user())
                        ->performedOn($student)
                        ->withProperties([
                            'gdpr_action' => 'anonymization',
                            'student_id'  => $anonId,
                            'operator'    => auth()->user()?->email,
                        ])
                        ->log('Studente anonimizzato (GDPR) — dati personali rimossi');

                    Notification::make()
                        ->title('Studente anonimizzato')
                        ->body('Tutti i dati personali sono stati cancellati in conformità al GDPR.')
                        ->success()
                        ->send();

                    // Aggiorna il form con i nuovi valori
                    $this->refreshFormData([
                        'first_name', 'last_name', 'email', 'phone',
                        'fiscal_code', 'birth_date', 'birth_place', 'birth_province', 'birth_country',
                        'residence_address', 'residence_zip', 'residence_city', 'residence_province', 'residence_country',
                        'parent_first_name', 'parent_last_name', 'parent_email', 'parent_phone',
                        'notes',
                    ]);
                }),
        ];
    }
}
