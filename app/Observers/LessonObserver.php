<?php

namespace App\Observers;

use App\Models\Contract;
use App\Models\Lesson;
use App\Services\EmailTemplateService;
use Illuminate\Support\Facades\Log;

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
     * Invia email quando una lezione viene annullata.
     *
     * Routing:
     *  - To:  studente (se ha email valida), altrimenti intestatario del contratto.
     *  - CC:  intestatario, se ha email valida e diversa da quella dello studente.
     *
     * Scenari:
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

        $student  = $lesson->student;
        $contract = $lesson->contract;

        // Risolvi i recipients tramite il contratto (gestisce fallback + CC)
        if ($contract && $student) {
            ['to' => $to, 'cc' => $cc] = $contract->lessonNotificationRecipients($student);
        } elseif ($student && filter_var(trim((string) $student->email), FILTER_VALIDATE_EMAIL)) {
            // Nessun contratto: manda solo allo studente
            $to = ['email' => $student->email, 'name' => $student->full_name ?: $student->email];
            $cc = [];
        } else {
            Log::info("LessonObserver: lezione #{$lesson->id} — nessun destinatario valido per notifica cancellazione.");
            return;
        }

        if (! $to) {
            Log::info("LessonObserver: lezione #{$lesson->id} — nessun indirizzo email valido (studente e intestatario).");
            return;
        }

        if ((bool) $lesson->is_recoverable) {
            $type = 'recoverable';
        } elseif ((bool) $lesson->counts_as_consumed) {
            $type = 'consumed';
        } else {
            $type = 'permanent';
        }

        $event = match ($type) {
            'recoverable' => 'lesson.cancelled.recoverable',
            'consumed'    => 'lesson.cancelled.consumed',
            default       => 'lesson.cancelled.permanent',
        };

        $startsAt = $lesson->starts_at
            ? \Illuminate\Support\Carbon::parse($lesson->starts_at)
            : null;
        $endsAt = $lesson->ends_at
            ? \Illuminate\Support\Carbon::parse($lesson->ends_at)
            : null;

        $docente = '';
        if ($lesson->teacher_id) {
            $teacher = \App\Models\User::find($lesson->teacher_id);
            $docente = $teacher?->name ?? '';
        }

        // 'nome' usa il nome dello studente anche quando il To è l'intestatario
        // (l'intestatario deve sapere di quale studente si tratta)
        $nomeStudente = $student
            ? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')) ?: $to['name']
            : $to['name'];

        $variables = [
            'nome'         => $nomeStudente,
            'data_lezione' => $startsAt?->format('d/m/Y') ?? '—',
            'ora_inizio'   => $startsAt?->format('H:i') ?? '—',
            'ora_fine'     => $endsAt?->format('H:i') ?? '—',
            'lingua'       => $lesson->language_id ?? '—',
            'docente'      => $docente,
            'motivo'       => '',
        ];

        try {
            app(EmailTemplateService::class)->sendByEvent(
                $event,
                $to['email'],
                $to['name'],
                $variables,
                [],
                $cc
            );
        } catch (\Throwable $e) {
            Log::warning(
                "Impossibile inviare notifica cancellazione lezione #{$lesson->id} "
                . "a {$to['email']}: " . $e->getMessage()
            );
        }
    }
}
