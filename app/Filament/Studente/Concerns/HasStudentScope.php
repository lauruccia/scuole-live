<?php

namespace App\Filament\Studente\Concerns;

use App\Models\Student;

trait HasStudentScope
{
    public static function studentIds(): array
    {
        return auth()->check()
            ? auth()->user()->students()->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];
    }

    /**
     * Restituisce il primo Student collegato all'utente loggato.
     */
    public function getStudent(): ?Student
    {
        $user = auth()->user();
        if (! $user) return null;

        // Prova tramite relazione diretta user → student
        if (method_exists($user, 'student') && $user->student) {
            return $user->student;
        }

        // Fallback: cerca per email
        return Student::where('email', $user->email)->first();
    }
}