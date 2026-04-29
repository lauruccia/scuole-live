<?php

namespace App\Filament\Studente\Concerns;

use App\Models\Contract;
use App\Models\Student;

trait HasStudentScope
{
    /**
     * Verifica se l'utente loggato può accedere al pannello studente.
     * Vero se ha il ruolo 'Studente' oppure se è superadmin.
     */
    public static function canAccessStudentPanel(): bool
    {
        if (! auth()->check()) {
            return false;
        }
        $user = auth()->user();
        return $user->hasRole('Studente')
            || $user->hasAnyRole(['superadmin', 'Superadmin', 'super_admin']);
    }

    /**
     * Restituisce gli ID di tutti gli Student collegati all'utente loggato.
     */
    public static function studentIds(): array
    {
        if (! auth()->check()) {
            return [];
        }

        // Prima cerca tramite relazione user → students (via user_id)
        $ids = auth()->user()->students()->pluck('id')->map(fn ($id) => (int) $id)->all();

        // Fallback: cerca per email se non ci sono student collegati
        if (empty($ids)) {
            $student = Student::where('email', auth()->user()->email)->first();
            if ($student) {
                $ids = [(int) $student->id];
            }
        }

        return $ids;
    }

    /**
     * Restituisce il primo Student collegato all'utente loggato.
     */
    public function getStudent(): ?Student
    {
        if (! auth()->check()) {
            return null;
        }

        // Prima cerca tramite relazione user → students (via user_id)
        $student = auth()->user()->students()->first();

        // Fallback: cerca per email
        if (! $student) {
            $student = Student::where('email', auth()->user()->email)->first();
        }

        return $student;
    }

    /**
     * Restituisce il contratto attivo (o piu recente) dello studente loggato.
     */
    public function getActiveContract(): ?Contract
    {
        $student = $this->getStudent();
        if (! $student) {
            return null;
        }

        return Contract::query()
            ->whereHas('students', fn ($q) => $q->where('students.id', $student->id))
            ->where('status', 'active')
            ->latest('id')
            ->first()
            ?? Contract::query()
                ->whereHas('students', fn ($q) => $q->where('students.id', $student->id))
                ->latest('id')
                ->first();
    }
}
