<?php

namespace App\Observers;

use App\Mail\LessonCancelledMail;
use App\Models\Contract;
use App\Models\Lesson;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LessonObserver
{
    public bool $afterCommit = true;

    public function saved(Lesson $lesson): void
    {
        $this->recalcContract($lesson);
        $this->notifyCancellation($lesson);
    }

    public function deleted(Lesson $lesson): void
    {
        $this->recalcContract($lesson);
    }

    private function recalcContract(Lesson $lesson): void
    {
        if (! $lesson->contract_id) {
            return;
        }

        // Ricalcola solo se sono cambiate colonne che influenzano le ore consumate.
        // Questo evita il doppio ricalcolo che avveniva con i hook in Lesson::booted().
        // Per le delete, wasChanged() ritorna sempre false → ricalcoliamo sempre.
        $relevant = [
            'counts_as_consumed',
            'is_recoverable',
            'completed_at',
            'cancelled_at',
            'starts_at',
            'ends_at',
            'duration_minutes',
            'contract_id',
        ];

        $hasRelevantChange = ! $lesson->exists // è una delete
            || collect($relevant)->some(fn ($col) => $lesson->wasChanged($col));

        if (! $hasRelevantChange) {
            return;
        }

        Contract::recalcConsumedHours((int) $lesson->contract_id);
    }

    /**
     * Invia email allo studente quando una lezione viene annullata.
     * Tre scenari:
     *   'recoverable' → cancellazione con >24h, verrà recuperata
     *   'consumed'    → cancellazione con <24h, le ore vengono scalate
     *   'permanent'   → annullamento definitivo senza scalare ore
     */
    private function notifyCancellation(Lesson $lesson): void
    {
        if (! $lesson->wasChanged('cancelled_at')) {
            return;
        }

        if (empty($lesson->cancelled_at)) {
            return;
        }

        $student = $lesson->student;
        if (! $student || empty($student->email)) {
            return;
        }

        if ((bool) $lesson->is_recoverable) {
            $type = 'recoverable';
        } elseif ((bool) $lesson->counts_as_consumed) {
            $type = 'consumed';
        } else {
            $type = 'permanent';
        }

        $studentName = trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''))
            ?: $student->email;

        try {
            Mail::to($student->email)->send(new LessonCancelledMail(
                lesson:           $lesson,
                studentName:      $studentName,
                cancellationType: $type,
            ));
        } catch (\Throwable $e) {
            Log::warning(
                "Impossibile inviare notifica cancellazione lezione #{$lesson->id} "
                . "a {$student->email}: " . $e->getMessage()
            );
        }
    }
}
