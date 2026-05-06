<?php

namespace App\Observers;

use App\Models\Student;
use App\Models\User;
use App\Services\EmailTemplateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class StudentObserver
{
    public function created(Student $student): void
    {
        if (empty($student->email)) {
            return;
        }

        $existingUser = User::where('email', $student->email)->first();

        if ($existingUser) {
            // Esiste già — assicurati almeno che abbia il ruolo Studente
            $this->ensureStudenteRole($existingUser);
            return;
        }

        $name = trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));
        if ($name === '') {
            $name = $student->email;
        }

        // SICUREZZA: password casuale per ogni studente.
        // Str::password(16) genera 16 char con upper/lower/digits/symbols.
        // L'utente DEVE cambiarla al primo login (flag must_change_password).
        $plainPassword = Str::password(16);

        $userData = [
            'name' => $name,
            'email' => $student->email,
            'password' => Hash::make($plainPassword),
        ];

        // must_change_password forza il reset al primo login (vedi middleware
        // ForceChangePassword). La colonna è opzionale: se non c'è ancora la
        // migrazione, non blocchiamo la creazione.
        if (Schema::hasColumn('users', 'must_change_password')) {
            $userData['must_change_password'] = true;
        }

        $user = User::create($userData);

        // Assegna il ruolo Studente in modo idempotente.
        $this->ensureStudenteRole($user);

        // Invia email di benvenuto DOPO il commit della transazione
        // (evita l'invio se la transazione va in rollback)
        $studentEmail  = $student->email;
        $studentFirst  = $student->first_name ?? $name;
        $studentLast   = $student->last_name ?? '';
        $welcomePass   = $plainPassword;

        DB::afterCommit(function () use ($studentEmail, $studentFirst, $studentLast, $welcomePass) {
            try {
                app(EmailTemplateService::class)->sendByEvent(
                    'student.created',
                    $studentEmail,
                    $studentFirst,
                    [
                        'nome'        => $studentFirst,
                        'cognome'     => $studentLast,
                        'email'       => $studentEmail,
                        'password'    => $welcomePass,
                        'portale_url' => url('/'),
                        'app_name'    => config('app.name', 'A&A Language Center'),
                    ]
                );
            } catch (\Throwable $e) {
                // CRITICO: la password e' casuale e non e' salvata da nessuna
                // parte. Se la mail fallisce, lo studente non potra' loggarsi
                // finche' non gli si manda manualmente un reset password.
                // Logghiamo come ERROR (non warning) per intercettarlo in Sentry.
                Log::error('Email benvenuto NON inviata: studente ' . $studentEmail . ' bloccato finche\' non si invia un reset password manuale. Errore: ' . $e->getMessage());
                report($e);
            }
        });
    }

    /**
     * Assegna il ruolo Studente in modo idempotente.
     *
     * Skipped silenziosamente se:
     *  - il pacchetto spatie/laravel-permission non e' caricato (es. in test minimali)
     *  - la tabella roles non esiste ancora
     *  - il ruolo "Studente" non e' stato seedato
     *
     * Cio' garantisce che la creazione dello studente non si rompa in casi limite,
     * e che il ruolo venga riassegnato se per qualche motivo era stato rimosso.
     */
    private function ensureStudenteRole(User $user): void
    {
        try {
            if (! Schema::hasTable('roles')) {
                return;
            }

            $roleExists = Role::where('name', 'Studente')->exists();
            if (! $roleExists) {
                Log::warning('Ruolo "Studente" non presente in DB — skip assignRole per ' . $user->email);
                return;
            }

            if (! $user->hasRole('Studente')) {
                $user->assignRole('Studente');
            }
        } catch (\Throwable $e) {
            Log::warning('assignRole(Studente) fallito per ' . $user->email . ': ' . $e->getMessage());
        }
    }

    public function updated(Student $student): void
    {
        if (empty($student->email)) {
            return;
        }

        $user = null;

        if (!empty($student->user_id)) {
            $user = User::find($student->user_id);
        }

        if (!$user) {
            $user = User::where('email', $student->email)->first();
        }

        if (!$user) {
            return;
        }

        $data = [];

        $name = trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));
        $data['name'] = $name !== '' ? $name : $student->email;

        $data['email'] = $student->email;

        $user->update($data);
    }
}