<?php

namespace App\Services;

use App\Models\ClosureDay;
use App\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LessonRecoveryService
{
    public function cancelAndCreateAutoRecovery(Lesson $lesson, Carbon $cancelledAt, string $reason = ''): Lesson
    {
        $lesson->refresh();

        if (! $lesson->is_recoverable) {
            throw new \RuntimeException('Lezione non recuperabile.');
        }

        if ($lesson->recoveryLesson()->exists()) {
            throw new \RuntimeException('Recupero già creato per questa lezione.');
        }

        if (! $lesson->starts_at || ! $lesson->ends_at) {
            throw new \RuntimeException('Lezione originale senza date.');
        }

        $originalStarts = Carbon::parse($lesson->starts_at);
        $originalEnds = Carbon::parse($lesson->ends_at);

        $durationMinutes = $originalStarts->diffInMinutes($originalEnds, false);

        if ($durationMinutes <= 0) {
            $durationMinutes = (int) ($lesson->duration_minutes ?: 60);
        }

        /**
         * REGOLA:
         * Il recupero parte SEMPRE dalla settimana successiva
         * rispetto alla lezione annullata, stesso giorno e stessa ora.
         */
        $candidate = $originalStarts->copy()->addWeek();

        [$candidate, $movedReason] = $this->findFirstAvailableRecoverySlot(
            lesson: $lesson,
            candidate: $candidate,
            originalStarts: $originalStarts,
        );

        return DB::transaction(function () use ($lesson, $candidate, $durationMinutes, $movedReason) {
            $recovery = Lesson::create([
                'contract_id' => $lesson->contract_id,
                'contract_student_id' => $lesson->contract_student_id,
                'student_id' => $lesson->student_id,
                'teacher_id' => $lesson->teacher_id,
                'language_id' => $lesson->language_id,

                'starts_at' => $candidate,
                'ends_at' => $candidate->copy()->addMinutes($durationMinutes),
                'duration_minutes' => $durationMinutes,

                'cancelled_at' => null,
                'cancelled_by' => null,
                'cancellation_reason' => null,
                'is_recoverable' => false,
                'counts_as_consumed' => false,

                'recovery_of_lesson_id' => $lesson->id,
                'is_auto_recovery' => true,

                'meet_url' => $lesson->meet_url,
                'google_calendar_id' => null,
                'google_event_id' => null,

                'notes' => $movedReason,
                'homework' => null,
            ]);

            if ($movedReason) {
                $recovery->setAttribute('_moved_reason', $movedReason);
            }

            return $recovery;
        });
    }

    private function findFirstAvailableRecoverySlot(Lesson $lesson, Carbon $candidate, Carbon $originalStarts): array
    {
        $originalCandidate = $candidate->copy();
        $time = $originalStarts->format('H:i:s');

        $moves = 0;
        $reasons = [];

        for ($i = 0; $i < 104; $i++) {
            $candidate = $this->applyTime($candidate, $time);

            $closure = $this->getClosureForDay($candidate);
            $busyLesson = $this->getBusyLessonForSlot($lesson, $candidate);

            if (! $closure && ! $busyLesson) {
                if ($moves === 0) {
                    return [$candidate, null];
                }

                $msg = 'Recupero creato automaticamente nella prima settimana libera disponibile. '
                    . 'La prima data proposta era '
                    . $originalCandidate->format('d/m/Y H:i')
                    . ', ma non era disponibile. '
                    . 'Nuova data: '
                    . $candidate->format('d/m/Y H:i')
                    . '. Spostamento totale: +'
                    . $moves
                    . ' settimana/e.';

                if (! empty($reasons)) {
                    $msg .= ' Motivi degli spostamenti: ' . implode(' | ', $reasons);
                }

                return [$candidate, $msg];
            }

            $reasonParts = [];

            if ($closure) {
                $reasonParts[] = 'giorno di chiusura'
                    . (($closure->reason ?? null) ? ' - ' . $closure->reason : '');
            }

            if ($busyLesson) {
                $reasonParts[] = 'slot già occupato dalla lezione ID '
                    . $busyLesson->id
                    . ' del '
                    . Carbon::parse($busyLesson->starts_at)->format('d/m/Y H:i');
            }

            $reasons[] = $candidate->format('d/m/Y H:i') . ': ' . implode(', ', $reasonParts);

            $candidate->addWeek();
            $moves++;
        }

        throw new \RuntimeException('Impossibile creare il recupero: nessuno slot libero trovato nelle prossime 104 settimane.');
    }

    private function getBusyLessonForSlot(Lesson $lesson, Carbon $candidate): ?Lesson
    {
        return Lesson::query()
            ->where('id', '!=', $lesson->id)
            ->where('contract_student_id', $lesson->contract_student_id)
            ->where('starts_at', $candidate->format('Y-m-d H:i:s'))
            ->first();
    }

    private function applyTime(Carbon $dt, string $time): Carbon
    {
        try {
            $dt->setTimeFromTimeString($time);
        } catch (\Throwable) {
            //
        }

        return $dt;
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
