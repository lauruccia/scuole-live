<?php

namespace App\Observers;

use App\Models\Student;
use App\Models\User;
use App\Services\EmailTemplateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class StudentObserver
{
    public function created(Student $student): void
    {
        if (empty($student->email)) {
            return;
        }

        $existingUser = User::where('email', $student->email)->first();

        if ($existingUser) {
            return;
        }

        $name = trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));
        if ($name === '') {
            $name = $student->email;
        }

        $plainPassword = 'Password123!';

        $userData = [
            'name' => $name,
            'email' => $student->email,
            'password' => Hash::make($plainPassword),
        ];

        $user = User::create($userData);

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
                // Non bloccare la creazione dello studente se l'email fallisce
                Log::warning('Impossibile inviare email di benvenuto a ' . $studentEmail . ': ' . $e->getMessage());
            }
        });
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