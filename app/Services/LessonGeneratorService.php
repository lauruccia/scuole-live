<?php

namespace App\Services;

use App\Models\ClosureDay;
use App\Models\Contract;
use App\Models\ContractLessonSlot;
use App\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LessonGeneratorService
{
    /**
     * Genera le lezioni per un contratto.
     *
     * @param  bool  $force        true = rigenera tutto da starts_at (cancella passato + futuro non consumato)
     * @param  bool  $slotChanged  true = è stata aggiunta/modificata/rimossa una slot settimanale:
     *                             cancella TUTTE le lezioni future non completate/cancellate
     *                             (più aggressivo di force=false, ma non tocca il passato)
     */
    public function generateForContract(Contract $contract, bool $force = false, bool $slotChanged = false): void
    {
        $contract->loadMissing(['course', 'beneficiaries']);

        if (! $contract->starts_at) {
            return;
        }

        // Lock da 120 s: la generazione di molte lezioni può richiedere tempo.
        // Usiamo get() invece di blockFor() per compatibilità con driver cache 'file'
        // (blockFor richiede Redis o Memcached).
        $lock = Cache::lock("contract:{$contract->id}:generate_lessons", 120);

        if (! $lock->get()) {
            return;
        }

        try {
            DB::transaction(function () use ($contract, $force, $slotChanged) {
                $today = now()->startOfDay();

                $defaultLanguage = method_exists($contract, 'getDefaultLanguage')
                    ? $contract->getDefaultLanguage()
                    : ($contract->language_id ?: null);

                if ($slotChanged && ! $force) {
                    // Slot aggiunto/modificato/rimosso: cancella TUTTE le lezioni future
                    // non ancora completate né cancellate, indipendentemente da counts_as_consumed
                    // o da eventuali modifiche manuali. Questo garantisce che il ricalcolo
                    // ridistribuisca correttamente le ore tra tutti gli slot attivi aggiornati.
                    //
                    // IMPORTANTE: forceDelete() invece di delete() perché la tabella usa soft delete.
                    // Le lezioni future non ancora svolte non devono restare come righe soft-deleted:
                    // il vincolo UNIQUE (contract_student_id, starts_at) include anche le righe con
                    // deleted_at valorizzato, quindi una delete() "morbida" seguita da create() sulla
                    // stessa slot provocherebbe un Integrity constraint violation (1062 Duplicate entry).
                    //
                    // NOTA: i segnaposto FULL (is_full_lesson = true) vengono esclusi — vengono
                    // gestiti esclusivamente da FullLessonService, mai dal generatore di slot.
                    Lesson::query()
                        ->where('contract_id', $contract->id)
                        ->where(function ($q) { $q->where('is_full_lesson', false)->orWhereNull('is_full_lesson'); })
                        ->whereNull('cancelled_at')
                        ->whereNull('completed_at')
                        ->whereDate('starts_at', '>=', $today)
                        ->forceDelete();
                } else {
                    // Comportamento standard: cancella solo lezioni auto-generate non modificate.
                    // NOTA: i segnaposto FULL (is_full_lesson = true) vengono esclusi.
                    $deleteQuery = Lesson::query()
                        ->where('contract_id', $contract->id)
                        ->where(function ($q) { $q->where('is_full_lesson', false)->orWhereNull('is_full_lesson'); })
                        ->whereNull('cancelled_at')
                        ->where(function ($q) {
                            $q->whereNull('counts_as_consumed')
                                ->orWhere('counts_as_consumed', 0);
                        })
                        ->whereColumn('updated_at', 'created_at');

                    if (! $force) {
                        $deleteQuery->whereDate('starts_at', '>=', $today);
                    }

                    // forceDelete() per lo stesso motivo del ramo sopra: il UNIQUE su
                    // (contract_student_id, starts_at) copre anche le righe soft-deleted.
                    $deleteQuery->forceDelete();
                }

                $beneficiaries = DB::table('contract_students')
                    ->where('contract_id', $contract->id)
                    ->whereNotNull('student_id')
                    ->orderBy('id')
                    ->get();

                if ($beneficiaries->isEmpty()) {
                    return;
                }

                $maxEnd = null;

                // Pre-calcola le ore di default per i beneficiari senza assigned_hours.
                // Se c'è un solo beneficiario → usa l'intero hours_purchased.
                // Se ci sono più beneficiari → dividi equamente per evitare over-assignment.
                $totalHours       = (float) ($contract->hours_purchased ?? 0);
                $countBeneficiaries = $beneficiaries->count();
                $nullCount         = $beneficiaries->whereNull('assigned_hours')->count();
                $sumAssigned       = $beneficiaries->sum(fn($b) => (float) ($b->assigned_hours ?? 0));
                $hoursLeftForNull  = max(0.0, $totalHours - $sumAssigned);
                $defaultHoursPerNull = ($nullCount > 0)
                    ? round($hoursLeftForNull / $nullCount, 2)
                    : 0.0;

                foreach ($beneficiaries as $contractStudent) {
                    $studentId = (int) $contractStudent->student_id;
                    $contractStudentId = (int) $contractStudent->id;
                    // Usa assigned_hours del beneficiario se impostato (anche se 0 esplicito).
                    // Fallback: distribuisce le ore rimanenti equamente tra i beneficiari non configurati.
                    $rawHours = $contractStudent->assigned_hours;
                    if ($rawHours === null) {
                        $assignedHours = $defaultHoursPerNull;
                    } else {
                        $assignedHours = (float) $rawHours;
                    }

                    // Se ancora zero → nessuna lezione da generare per questo beneficiario
                    if ($assignedHours <= 0) {
                        continue;
                    }

                    $slots = ContractLessonSlot::query()
                        ->where('contract_id', $contract->id)
                        ->where('student_id', $studentId)
                        ->where('is_active', true)
                        ->orderBy('weekly_day')
                        ->orderBy('weekly_time')
                        ->get();

                    if ($slots->isEmpty()) {
                        continue;
                    }

                    // Escludi i segnaposto FULL: non influenzano la generazione delle personalizzate
                    $hasAnyLessons = Lesson::query()
                        ->where('contract_id', $contract->id)
                        ->where('student_id', $studentId)
                        ->where(function ($q) { $q->where('is_full_lesson', false)->orWhereNull('is_full_lesson'); })
                        ->exists();

                    $nextLessonNumber = (int) (
                        Lesson::query()
                            ->where('contract_id', $contract->id)
                            ->where('student_id', $studentId)
                            ->max('lesson_number') ?? 0
                    ) + 1;

                    $contractStartDay = Carbon::parse($contract->starts_at)->startOfDay();

                    $baseStartDay = $contractStartDay->copy();
                    if (! $force && $hasAnyLessons && $baseStartDay->lt($today)) {
                        $baseStartDay = $today;
                    }

                    $notBefore = ($force || ! $hasAnyLessons)
                        ? Carbon::parse($contract->starts_at)->startOfDay()
                        : now();

                    // Conta le ore già coperte da lezioni PERSONALIZZATE esistenti non cancellate.
                    // Dopo la cancellazione delle lezioni future, questo valore rappresenta
                    // solo le ore delle lezioni passate (già avvenute o consumate).
                    // I segnaposto FULL vengono esclusi: non concorrono al monte ore personalizzate.
                    $existingFutureHours = Lesson::query()
                        ->where('contract_id', $contract->id)
                        ->where('student_id', $studentId)
                        ->where(function ($q) { $q->where('is_full_lesson', false)->orWhereNull('is_full_lesson'); })
                        ->whereNull('cancelled_at')
                        ->get(['duration_minutes', 'starts_at', 'ends_at'])
                        ->sum(fn (Lesson $lesson): float => Lesson::computeLessonHours(
                            $lesson->duration_minutes,
                            $lesson->starts_at,
                            $lesson->ends_at
                        ));

                    $remainingHours = max(0, $assignedHours - $existingFutureHours);

                    if ($remainingHours <= 0) {
                        continue;
                    }

                    $nextBySlot = [];
                    foreach ($slots as $slot) {
                        $nextBySlot[$slot->id] = $this->nextOccurrenceForSlot($slot, $baseStartDay, $notBefore);
                    }

                    $guard = 0;

                    while ($guard < 10000) {
                        $guard++;

                        [$slot, $startAt] = $this->pickNearest($slots, $nextBySlot);

                        if (! $slot || ! $startAt) {
                            break;
                        }

                        $duration = (int) ($slot->duration_minutes ?? 60);
                        if ($duration <= 0) {
                            $duration = 60;
                        }

                        $durationHours = $duration / 60;

                        if ($remainingHours < $durationHours) {
                            $nextBySlot[$slot->id] = null;

                            $canStillGenerate = false;
                            foreach ($slots as $candidateSlot) {
                                $candidateDuration = (int) ($candidateSlot->duration_minutes ?? 60);
                                if ($candidateDuration <= 0) {
                                    $candidateDuration = 60;
                                }

                                if (($candidateDuration / 60) <= $remainingHours && ($nextBySlot[$candidateSlot->id] ?? null)) {
                                    $canStillGenerate = true;
                                    break;
                                }
                            }

                            if (! $canStillGenerate) {
                                break;
                            }

                            continue;
                        }

                        $startAt = $this->shiftOutOfClosuresOrNull($startAt->copy());
                        if (! $startAt) {
                            $nextBySlot[$slot->id] = null;
                            continue;
                        }

                        if ($slot->starts_at && $startAt->lt(Carbon::parse($slot->starts_at)->startOfDay())) {
                            $nextBySlot[$slot->id] = $this->nextOccurrenceForSlot(
                                $slot,
                                Carbon::parse($slot->starts_at)->startOfDay(),
                                $notBefore
                            );
                            continue;
                        }

                        if ($slot->ends_at && $startAt->gt(Carbon::parse($slot->ends_at)->endOfDay())) {
                            $nextBySlot[$slot->id] = null;
                            continue;
                        }

                        $endAt = $startAt->copy()->addMinutes($duration);

                        $duplicate = Lesson::query()
                            ->where('contract_id', $contract->id)
                            ->where('student_id', $studentId)
                            ->where('starts_at', $startAt->toDateTimeString())
                            ->exists();

                        if ($duplicate) {
                            $nextBySlot[$slot->id] = $startAt->copy()->addWeek();
                            continue;
                        }

                        $studentOverlap = Lesson::query()
                            ->where('student_id', $studentId)
                            ->whereNull('cancelled_at')
                            ->where('starts_at', '<', $endAt)
                            ->where('ends_at', '>', $startAt)
                            ->exists();

                        if ($studentOverlap) {
                            $nextBySlot[$slot->id] = $startAt->copy()->addWeek();
                            continue;
                        }

                        // Controllo sovrapposizione docente: se il docente è già impegnato
                        // in un'altra lezione nello stesso orario (su qualsiasi contratto),
                        // saltiamo alla settimana successiva per questo slot.
                        // Questo evita conflitti silenziosi quando lo stesso docente
                        // è assegnato a più contratti con orari sovrapposti.
                        if ($slot->teacher_id) {
                            $teacherOverlap = Lesson::query()
                                ->where('teacher_id', (int) $slot->teacher_id)
                                ->whereNull('cancelled_at')
                                ->where('starts_at', '<', $endAt)
                                ->where('ends_at', '>', $startAt)
                                ->exists();

                            if ($teacherOverlap) {
                                $nextBySlot[$slot->id] = $startAt->copy()->addWeek();
                                continue;
                            }
                        }

                        Lesson::create([
                            'contract_id'         => $contract->id,
                            'contract_student_id' => $contractStudentId,
                            'student_id'          => $studentId,
                            'teacher_id'          => $slot->teacher_id ? (int) $slot->teacher_id : null,
                            'starts_at'           => $startAt,
                            'ends_at'             => $endAt,
                            'meet_url'            => $slot->meet_url,
                            'duration_minutes'    => $duration,
                            'lesson_number'       => $nextLessonNumber,
                            'counts_as_consumed'  => 0,
                            'is_recoverable'      => 0,
                            'language_id'         => $defaultLanguage,
                        ]);

                        $remainingHours -= $durationHours;
                        $nextLessonNumber++;
                        $maxEnd = $maxEnd ? Carbon::parse($maxEnd)->max($endAt) : $endAt;
                        $nextBySlot[$slot->id] = $startAt->copy()->addWeek();

                        if ($remainingHours <= 0) {
                            break;
                        }
                    }

                    // -------------------------------------------------------
                    // Lezione di completamento per le ore residue
                    // Esempio: contratto 10h / slot 1,5h → 6 lezioni (9h) +
                    // 1 lezione da 60 min (1h residua) = 10h esatte.
                    // Si genera solo se il residuo è ≥ 15 min.
                    // -------------------------------------------------------
                    $partialMinutes = (int) round($remainingHours * 60);

                    if ($partialMinutes >= 1 && $partialMinutes < 15) {
                        // Ore residue troppo piccole per generare una lezione di completamento
                        // (< 15 min): vengono silenziosamente ignorate dal generatore.
                        // Logghiamo il fatto per permettere alla segreteria di identificare
                        // contratti con discrepanze minime nelle ore.
                        Log::warning('LessonGenerator: ore residue < 15 min ignorate (data loss silenzioso)', [
                            'contract_id'      => $contract->id,
                            'student_id'       => $studentId,
                            'partial_minutes'  => $partialMinutes,
                            'assigned_hours'   => $assignedHours,
                        ]);
                    }

                    if ($partialMinutes >= 15) {
                        // Partenza: giorno successivo all'ultima lezione generata
                        $partialFrom      = $maxEnd
                            ? Carbon::parse($maxEnd)->startOfDay()
                            : $baseStartDay;
                        $partialNotBefore = $maxEnd ?? $notBefore;

                        $partialSlot = null;
                        $partialAt   = null;

                        foreach ($slots as $candidateSlot) {
                            $candidate = $this->nextOccurrenceForSlot(
                                $candidateSlot,
                                $partialFrom,
                                $partialNotBefore
                            );

                            if (! $candidate) {
                                continue;
                            }

                            if (! $partialAt || $candidate->lt($partialAt)) {
                                $partialSlot = $candidateSlot;
                                $partialAt   = $candidate;
                            }
                        }

                        if ($partialSlot && $partialAt) {
                            $partialAt = $this->shiftOutOfClosuresOrNull($partialAt->copy());

                            if ($partialAt) {
                                $partialEnd = $partialAt->copy()->addMinutes($partialMinutes);

                                $dupPartial = Lesson::query()
                                    ->where('contract_id', $contract->id)
                                    ->where('student_id', $studentId)
                                    ->where('starts_at', $partialAt->toDateTimeString())
                                    ->exists();

                                // Controllo sovrapposizione docente anche per la lezione di completamento
                                $teacherPartialOverlap = false;
                                if ($partialSlot->teacher_id) {
                                    $teacherPartialOverlap = Lesson::query()
                                        ->where('teacher_id', (int) $partialSlot->teacher_id)
                                        ->whereNull('cancelled_at')
                                        ->where('starts_at', '<', $partialEnd)
                                        ->where('ends_at', '>', $partialAt)
                                        ->exists();
                                }

                                if (! $dupPartial && ! $teacherPartialOverlap) {
                                    $slotStandard = (int) ($partialSlot->duration_minutes ?? 60);

                                    Lesson::create([
                                        'contract_id'         => $contract->id,
                                        'contract_student_id' => $contractStudentId,
                                        'student_id'          => $studentId,
                                        'teacher_id'          => $partialSlot->teacher_id
                                            ? (int) $partialSlot->teacher_id
                                            : null,
                                        'starts_at'           => $partialAt,
                                        'ends_at'             => $partialEnd,
                                        'meet_url'            => $partialSlot->meet_url,
                                        'duration_minutes'    => $partialMinutes,
                                        'lesson_number'       => $nextLessonNumber,
                                        'counts_as_consumed'  => 0,
                                        'is_recoverable'      => 0,
                                        'language_id'         => $defaultLanguage,
                                        'notes'               => "Lezione di completamento: {$partialMinutes} min "
                                            . "(ore residue del contratto — slot standard {$slotStandard} min).",
                                    ]);

                                    $maxEnd = $maxEnd
                                        ? Carbon::parse($maxEnd)->max($partialEnd)
                                        : $partialEnd;
                                }
                            }
                        }
                    }
                }

                if ($maxEnd) {
                    $contract->update(['ends_at' => $maxEnd]);
                }
            });
        } finally {
            optional($lock)->release();
        }
    }

    private function pickNearest($studentSlots, array $nextBySlot): array
    {
        $bestSlot = null;
        $bestDt = null;

        foreach ($studentSlots as $slot) {
            $dt = $nextBySlot[$slot->id] ?? null;
            if (! $dt) {
                continue;
            }

            if (! $bestDt || $dt->lt($bestDt)) {
                $bestDt = $dt;
                $bestSlot = $slot;
            }
        }

        return [$bestSlot, $bestDt];
    }

    private function nextOccurrenceForSlot(ContractLessonSlot $slot, Carbon $fromDay, Carbon $notBefore): ?Carbon
    {
        $day = (int) $slot->weekly_day;
        if ($day < 1 || $day > 7) {
            return null;
        }

        $time = (string) $slot->weekly_time;
        $hour = (int) substr($time, 0, 2);
        $min  = (int) substr($time, 3, 2);

        $base = $fromDay->copy()->setTime($hour, $min, 0);

        $currentDow = (int) $base->dayOfWeekIso;
        $delta = $day - $currentDow;
        if ($delta < 0) {
            $delta += 7;
        }

        $candidate = $base->copy()->addDays($delta);

        $guard = 0;
        while ($candidate->lt($notBefore) && $guard < 1040) {
            $candidate->addWeek();
            $guard++;
        }

        return $candidate;
    }

    private function isClosureDay(Carbon $dt): bool
    {
        // closure_days usa start_date / end_date (colonna 'date' non esiste)
        return ClosureDay::query()
            ->whereDate('start_date', '<=', $dt->toDateString())
            ->where(function ($q) use ($dt) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $dt->toDateString());
            })
            ->exists();
    }

    private function shiftOutOfClosures(Carbon $dt): Carbon
    {
        $limit = 0;
        while ($this->isClosureDay($dt)) {
            $dt->addWeek();
            $limit++;
            if ($limit > 104) {
                break;
            }
        }

        return $dt;
    }

    private function shiftOutOfClosuresOrNull(Carbon $dt): ?Carbon
    {
        $shifted = $this->shiftOutOfClosures($dt);
        return $this->isClosureDay($shifted) ? null : $shifted;
    }
}
