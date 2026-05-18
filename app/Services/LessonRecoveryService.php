<?php

namespace App\Services;

use App\Models\ClosureDay;
use App\Models\Lesson;
use App\Models\SchoolSetting;
use App\Services\EmailTemplateService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LessonRecoveryService
{
    public function __construct(private readonly EmailTemplateService $emailService) {}

    public function cancelAndCreateAutoRecovery(Lesson $lesson, Carbon $cancelledAt, string $reason = ''): Lesson
    {
        $lesson->refresh();

        if (! $lesson->is_recoverable) {
            throw new \RuntimeException('Lezione non recuperabile.');
        }

        if ($lesson->recoveryLesson()->exists()) {
            throw new \RuntimeException('Recupero già creato per questa lezione.');
        }

        // Non creare recuperi su contratti non attivi (completati, cancellati, ecc.)
        $contractStatus = $lesson->contract?->status;
        if ($contractStatus && ! in_array($contractStatus, ['active', 'pending'], true)) {
            throw new \RuntimeException(
                "Impossibile creare il recupero: il contratto #{$lesson->contract_id} è in stato '{$contractStatus}'."
            );
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
         * Il recupero parte dalla prima settimana di calendario DOPO
         * l'ultima lezione regolare (non annullata, non di recupero) del contratto,
         * nello stesso giorno della settimana e alla stessa ora della lezione annullata.
         *
         * Se non esistono altre lezioni pianificate, fallback alla settimana successiva.
         */
        $lastOtherStart = Lesson::query()
            ->where('contract_id', $lesson->contract_id)
            ->where('id', '!=', $lesson->id)
            ->whereNull('cancelled_at')
            ->whereNull('deleted_at')
            ->whereNull('recovery_of_lesson_id')
            ->max('starts_at');

        if ($lastOtherStart) {
            // Prima settimana di calendario DOPO l'ultima lezione regolare,
            // nello stesso giorno della settimana della lezione annullata.
            $lastLesson   = Carbon::parse($lastOtherStart);
            $nextWeekStart = $lastLesson->copy()->addWeek()->startOfWeek(Carbon::MONDAY);
            $targetDow     = $originalStarts->dayOfWeek; // 0=dom, 1=lun, …, 6=sab
            $daysToAdd     = ($targetDow - $nextWeekStart->dayOfWeek + 7) % 7;
            $candidate     = $nextWeekStart->copy()->addDays($daysToAdd);
        } else {
            // Nessuna altra lezione pianificata nel contratto: fallback alla settimana successiva.
            $candidate = $originalStarts->copy()->addWeek();
        }

        [$candidate, $movedReason] = $this->findFirstAvailableRecoverySlot(
            lesson: $lesson,
            candidate: $candidate,
            originalStarts: $originalStarts,
        );

        $recovery = DB::transaction(function () use ($lesson, $candidate, $durationMinutes, $movedReason) {
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

        // Invia email di notifica allo studente (fuori dalla transaction: un errore email
        // non deve annullare la creazione del recupero già salvato nel DB).
        $this->notifyStudentOfRecovery($lesson, $recovery);

        return $recovery;
    }

    /**
     * Invia un'email informativa quando un recupero automatico viene creato.
     * Usa il template 'lesson_recovery_created'. Fallimento silenzioso (solo log).
     *
     * Routing:
     *  - To:  studente (se ha email valida), altrimenti intestatario del contratto.
     *  - CC:  intestatario, se ha email valida e diversa da quella dello studente.
     */
    private function notifyStudentOfRecovery(Lesson $originalLesson, Lesson $recoveryLesson): void
    {
        try {
            $student  = $originalLesson->student;
            $contract = $originalLesson->contract;

            // Risolvi i recipients tramite il contratto
            if ($contract && $student) {
                ['to' => $to, 'cc' => $cc] = $contract->lessonNotificationRecipients($student);
            } elseif ($student && filter_var(trim((string) $student->email), FILTER_VALIDATE_EMAIL)) {
                $to = ['email' => $student->email, 'name' => $student->full_name ?: $student->email];
                $cc = [];
            } else {
                Log::info('LessonRecoveryService: nessun destinatario valido per notifica recupero.', [
                    'lesson_id'   => $originalLesson->id,
                    'recovery_id' => $recoveryLesson->id,
                ]);
                return;
            }

            if (! $to) {
                Log::info('LessonRecoveryService: nessun indirizzo email valido per notifica recupero.', [
                    'lesson_id'   => $originalLesson->id,
                    'recovery_id' => $recoveryLesson->id,
                ]);
                return;
            }

            $teacher = $originalLesson->teacher;
            $locale  = 'it';

            $originalStarts = Carbon::parse($originalLesson->starts_at)->locale($locale);
            $recoveryStarts = Carbon::parse($recoveryLesson->starts_at)->locale($locale);

            $dataOriginale = $originalStarts->isoFormat('dddd D MMMM YYYY') . ' alle ' . $originalStarts->format('H:i');
            $dataRecupero  = $recoveryStarts->isoFormat('dddd D MMMM YYYY') . ' alle ' . $recoveryStarts->format('H:i');

            $nomeStudente = $student
                ? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''))
                : $to['name'];

            $variables = [
                'nome_studente'  => $nomeStudente,
                'data_originale' => $dataOriginale,
                'data_recupero'  => $dataRecupero,
                'docente'        => $teacher ? trim($teacher->name ?? ($teacher->first_name . ' ' . $teacher->last_name)) : '—',
                'lingua'         => $originalLesson->language?->name ?? '—',
                'nome_scuola'    => SchoolSetting::schoolName(),
            ];

            $this->emailService->sendBySlug(
                'lesson_recovery_created',
                $to['email'],
                $to['name'],
                $variables,
                [],
                $cc
            );

        } catch (\Throwable $e) {
            Log::warning('LessonRecoveryService: errore invio email notifica recupero.', [
                'lesson_id'   => $originalLesson->id,
                'recovery_id' => $recoveryLesson->id,
                'error'       => $e->getMessage(),
            ]);
        }
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
                    . 'Motivi: ' . implode('; ', $reasons);

                return [$candidate, $msg];
            }

            if ($closure) {
                $reasons[] = 'Giornata di chiusura il ' . $candidate->format('d/m/Y');
            } elseif ($busyLesson) {
                $reasons[] = 'Docente occupato il ' . $candidate->format('d/m/Y H:i');
            }

            $candidate = $candidate->copy()->addWeek();
            $moves++;
        }

        // Fallback: usa la prima data proposta anche se non ideale
        return [$originalCandidate, 'Nessuno slot libero trovato nelle prossime 104 settimane.'];
    }

    private function applyTime(Carbon $date, string $time): Carbon
    {
        [$h, $m, $s] = explode(':', $time);
        return $date->copy()->setTime((int)$h, (int)$m, (int)$s);
    }

    private function getClosureForDay(Carbon $dt): ?object
    {
        return \App\Models\ClosureDay::query()
            ->whereDate('start_date', '<=', $dt->toDateString())
            ->where(function ($q) use ($dt) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $dt->toDateString());
            })->first();
    }

    private function getBusyLessonForSlot(Lesson $lesson, Carbon $candidate): ?Lesson
    {
        $duration     = $lesson->duration_minutes ?: 60;
        $endCandidate = $candidate->copy()->addMinutes($duration);

        // ── Controllo sovrapposizione STUDENTE ────────────────────────────────
        // Verifica che lo studente non abbia già un'altra lezione attiva nello
        // stesso intervallo (su qualsiasi contratto). Usa strict < / > per non
        // considerare lezioni adiacenti (es. fine alle 11:00 / inizio alle 11:00)
        // come conflitti — stesso pattern del LessonGeneratorService.
        $studentBusy = Lesson::query()
            ->where('student_id', $lesson->student_id)
            ->where('id', '!=', $lesson->id)
            ->whereNull('cancelled_at')
            ->whereNull('deleted_at')
            ->where('starts_at', '<', $endCandidate)
            ->where('ends_at', '>', $candidate)
            ->first();

        if ($studentBusy) {
            return $studentBusy;
        }

        // ── Controllo sovrapposizione DOCENTE ─────────────────────────────────
        // Se la lezione non ha docente assegnato, questo check non è applicabile:
        // WHERE teacher_id = NULL è sempre false in SQL (serve IS NULL), quindi
        // saltiamo la query per evitare un controllo silenziosamente inutile.
        if (! $lesson->teacher_id) {
            return null;
        }

        // Strict < / > coerente con il LessonGeneratorService: evita falsi positivi
        // su lezioni adiacenti (es. una lezione che finisce esattamente quando
        // inizia il candidato non è un conflitto reale).
        return Lesson::query()
            ->where('teacher_id', (int) $lesson->teacher_id)
            ->where('id', '!=', $lesson->id)
            ->whereNull('cancelled_at')
            ->whereNull('deleted_at')
            ->where('starts_at', '<', $endCandidate)
            ->where('ends_at', '>', $candidate)
            ->first();
    }
}
