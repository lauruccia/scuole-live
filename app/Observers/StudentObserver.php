<?php

namespace App\Observers;

use App\Models\Student;
use App\Models\User;
use App\Services\EmailTemplateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class StudentObserver
{
    public function created(Student $student): void
    {
        if (empty($student->email)) {
            return;
        }

        $existingUser = User::where('email', $student->email)->first();

        if ($existingUser) {
            if (Schema::hasColumn('students', 'user_id') && empty($student->user_id)) {
                $student->forceFill([
                    'user_id' => $existingUser->id,
                ])->saveQuietly();
            }

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

        // Se nella tabella users esiste first_name / last_name, li valorizza
        if (Schema::hasColumn('users', 'first_name')) {
            $userData['first_name'] = $student->first_name;
        }

        if (Schema::hasColumn('users', 'last_name')) {
            $userData['last_name'] = $student->last_name;
        }

        if (Schema::hasColumn('users', 'phone')) {
            $userData['phone'] = $student->phone;
        }

        $user = User::create($userData);

        if (Schema::hasColumn('students', 'user_id')) {
            $student->forceFill([
                'user_id' => $user->id,
            ])->saveQuietly();
        }

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

        if (Schema::hasColumn('users', 'name')) {
            $name = trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));
            $data['name'] = $name !== '' ? $name : $student->email;
        }

        if (Schema::hasColumn('users', 'first_name')) {
            $data['first_name'] = $student->first_name;
        }

        if (Schema::hasColumn('users', 'last_name')) {
            $data['last_name'] = $student->last_name;
        }

        if (Schema::hasColumn('users', 'email')) {
            $data['email'] = $student->email;
        }

        if (Schema::hasColumn('users', 'phone')) {
            $data['phone'] = $student->phone;
        }

        $user->update($data);

        if (empty($student->user_id) && Schema::hasColumn('students', 'user_id')) {
            $student->forceFill([
                'user_id' => $user->id,
            ])->saveQuietly();
        }
    }
}