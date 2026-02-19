<?php

namespace App\Services;

use App\Models\ClosureDay;
use App\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LessonRecoveryService
{
    /**
     * Annulla la lezione (già annullata in Resource) e crea la lezione di recupero.
     *
     * Regola:
     * - la nuova lezione deve essere in coda: settimana successiva all'ultima lezione schedulata
     * - stesso giorno della settimana e stessa ora della lezione originale
     * - se cade in giorno di chiusura -> sposta di +1 settimana (ripeti finché libero)
     * - evita doppio recupero
     */
    public function cancelAndCreateAutoRecovery(Lesson $lesson, Carbon $cancelledAt, string $reason = ''): Lesson
    {
        $lesson->refresh();

        if (! $lesson->is_recoverable) {
            throw new \RuntimeException('Lezione non recuperabile (entro 24h).');
        }

        // Evita doppio recupero
        if ($lesson->recoveryLesson()->exists()) {
            throw new \RuntimeException('Recupero già creato per questa lezione.');
        }

        if (! $lesson->starts_at || ! $lesson->ends_at) {
            throw new \RuntimeException('Lezione originale senza date (starts_at/ends_at).');
        }

        $originalStarts = Carbon::parse($lesson->starts_at);
        $originalEnds   = Carbon::parse($lesson->ends_at);

        $durationMinutes = max(1, $originalStarts->diffInMinutes($originalEnds, false));
        if ($durationMinutes <= 0) {
            $durationMinutes = (int) ($lesson->duration_minutes ?: 60);
        }

        // Calcola candidata in coda:
        // "settimana successiva all'ultima lezione schedulata" ma mantenendo stesso DOW e ora dell'originale
        $candidate = $this->computeCandidateAfterLastScheduled($lesson, $originalStarts);

        // Se giorno di chiusura: sposta di +1 settimana finché libero
        [$candidate, $movedReason] = $this->moveForwardByWeeksIfClosed($candidate, $originalStarts);

        return DB::transaction(function () use ($lesson, $candidate, $durationMinutes, $movedReason) {

            $recovery = Lesson::create([
                'contract_id'            => $lesson->contract_id,
                'contract_student_id'    => $lesson->contract_student_id,
                'student_id'             => $lesson->student_id,
                'teacher_id'             => $lesson->teacher_id,

                'starts_at'              => $candidate,
                'ends_at'                => $candidate->copy()->addMinutes($durationMinutes),
                'duration_minutes'       => $durationMinutes,

                // stato lezione recupero (è programmata, non annullata)
                'cancelled_at'           => null,
                'cancelled_by'           => null,
                'cancellation_reason'    => null,
                'is_recoverable'         => false,
                'counts_as_consumed'     => false,

                // riferimenti
                'recovery_of_lesson_id'  => $lesson->id,
                'is_auto_recovery'       => true,

                // copia eventuali dati utili
                'meet_url'               => $lesson->meet_url,
                'google_calendar_id'     => $lesson->google_calendar_id,
                'google_event_id'        => null, // nuovo evento
                'notes'                  => null,
            ]);

            // Info UX da mostrare nella notifica
            if ($movedReason) {
                $recovery->setAttribute('_moved_reason', $movedReason);
            }

            return $recovery;
        });
    }

    /**
     * Candidata:
     * - prende l'ultima lezione schedulata (non annullata) per lo stesso contratto/studente
     * - piazza il recupero al "prossimo" stesso DOW+ora della lezione originale, ma comunque DOPO l'ultima.
     */
    private function computeCandidateAfterLastScheduled(Lesson $lesson, Carbon $originalStarts): Carbon
{
    $dow  = (int) $originalStarts->dayOfWeek;          // 0..6
    $time = $originalStarts->format('H:i:s');          // mantieni anche i secondi se vuoi

    $lastStarts = Lesson::query()
        ->where('contract_student_id', $lesson->contract_student_id)
        ->whereNull('cancelled_at')
        ->whereNotNull('starts_at')
        ->max('starts_at');

    $base = $lastStarts ? Carbon::parse($lastStarts) : $originalStarts->copy();

    // prendo il "prossimo" giorno della settimana dopo l'ultima lezione
    $candidate = $base->copy()->next($dow);

    // ✅ FORZA SEMPRE l’ora dell’originale
    $candidate->setTimeFromTimeString($time);

    // sicurezza
    if ($candidate->lte($base)) {
        $candidate = $candidate->copy()->addWeek();
        $candidate->setTimeFromTimeString($time);
    }

    return $candidate;
}

    /**
     * Se la candidata cade in un periodo di chiusura:
     * - sposta di +1 settimana (stesso giorno/ora) finché non è libero
     * - MAX 52 tentativi
     *
     * end_date può essere NULL: vale start_date.
     */
    private function moveForwardByWeeksIfClosed(Carbon $candidate, Carbon $originalStarts): array
{
    $original = $candidate->copy();
    $moves = 0;
    $firstReason = null;

    $time = $originalStarts->format('H:i:s');

    for ($i = 0; $i < 52; $i++) {
        // ✅ forza l’ora anche prima del check
        $candidate->setTimeFromTimeString($time);

        $closure = $this->getClosureForDay($candidate);

        if (! $closure) {
            if ($moves === 0) {
                return [$candidate, null];
            }

            $msg = 'Data recupero cadeva in un giorno di chiusura'
                . ($firstReason ? " ({$firstReason})" : '')
                . ". Spostata di +{$moves} settimana/e: "
                . $original->format('d/m/Y') . ' → ' . $candidate->format('d/m/Y')
                . ' alle ' . $candidate->format('H:i') . '.';

            return [$candidate, $msg];
        }

        if (! $firstReason) {
            $firstReason = $closure->reason ?? 'Chiusura';
        }

        // ✅ regola cliente: +1 settimana, ma mantieni ora
        $candidate->addWeek();
        $candidate->setTimeFromTimeString($time);

        $moves++;
    }

    return [$candidate, 'Recupero spostato automaticamente di molte settimane per giorni di chiusura.'];
}

    private function getClosureForDay(Carbon $dateTime): ?ClosureDay
    {
        $day = $dateTime->toDateString();

        return ClosureDay::query()
            ->whereDate('start_date', '<=', $day)
            ->whereRaw('COALESCE(end_date, start_date) >= ?', [$day])
            ->first();
    }
}
