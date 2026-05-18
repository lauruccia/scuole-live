<?php

namespace App\Services;

use App\Models\ClosureDay;
use App\Models\Contract;
use App\Models\ContractLessonSlot;
use App\Models\Lesson;
use App\Services\ContractService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LessonGeneratorService
{
    /**
     * Cache dei giorni di chiusura per l'intera generazione corrente.
     * Caricata una volta all'inizio di generateForContract() per evitare
     * N+1 query DB nel loop di creazione lezioni (Bug 7).
     *
     * @var array<\App\Models\ClosureDay>
     */
    private array $closureDaysCache = [];

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
            // Bug 6: rende visibile nei log il lock contention silenzioso.
            // Questo avviene quando due pipeline di rigenerazione vengono schedulate
            // quasi in contemporanea (es. Contract::saved + ContractLessonSlotObserver).
            Log::info('LessonGeneratorService: lock non acquisito, generazione saltata.', [
                'contract_id' => $contract->id,
            ]);
            return;
        }

        // Bug 7: carica i giorni di chiusura UNA SOLA VOLTA per l'intera generazione,
        // evitando N+1 query DB (una per ogni candidato lezione nel loop).
        $this->closureDaysCache = ClosureDay::all()->all();

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

                // ── Bug 5: distribuzione ore unificata con CreateContract ──────────────
                //
                // Usa le ore PERSONALIZZATE (hours_purchased − hours_full), non il totale.
                // Per contratti MIX, hours_full sono gestite da FullLessonService e non
                // devono rientrare nel monte ore delle lezioni personalizzate auto-generate.
                // Contratti pre-migrazione hanno hours_full = 0/null → comportamento invariato.
                //
                // Algoritmo: ContractService::distributePersonalHoursToNull()
                //   → round(x, 2) compatibile con qualsiasi durata slot (30/60/90/custom min)
                //   → l'ultimo beneficiario null riceve il resto esatto (nessuna perdita da arrotondamento)
                //
                // Esempio: 9h, 2 studenti, slot 1.5h
                //   base = round(9/2, 2) = 4.5h → 3 lezioni × 1.5h = 4.5h ✓
                //
                // Esempio: 9h, studente A=1h (esplicito, slot 1h), studente B=null (slot 2h)
                //   hoursLeft = 9-1 = 8h → B riceve 8h → 4 lezioni × 2h ✓
                $personalHours = max(0.0,
                    (float) ($contract->hours_purchased ?? 0) - (float) ($contract->hours_full ?? 0)
                );
                $countBeneficiaries = $beneficiaries->count();
                $nullCount          = $beneficiaries->whereNull('assigned_hours')->count();
                $sumAssigned        = (float) $beneficiaries->sum(fn ($b) => (float) ($b->assigned_hours ?? 0));

                ['base' => $defaultHoursPerNull, 'lastExtra' => $lastNullExtra] =
                    app(ContractService::class)->distributePersonalHoursToNull(
                        $personalHours,
                        $nullCount,
                        $sumAssigned
                    );

                // Contatore dei beneficiari null già processati.
                // Serve a identificare l'ultimo (che riceve il resto esatto).
                $nullBenefCounter = 0;

                foreach ($beneficiaries as $contractStudent) {
                    $studentId = (int) $contractStudent->student_id;
                    $contractStudentId = (int) $contractStudent->id;

                    // Usa assigned_hours del beneficiario se impostato (anche se 0 esplicito).
                    // Fallback unificato: distribuisce le ore rimanenti tra i beneficiari null.
                    // L'ultimo null riceve il resto esatto per non perdere frazioni.
                    $rawHours = $contractStudent->assigned_hours;
                    if ($rawHours === null) {
                        $nullBenefCounter++;
                        $assignedHours = ($nullBenefCounter === $nullCount)
                            ? round($defaultHoursPerNull + $lastNullExtra, 2)
                            : $defaultHoursPerNull;
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

                    // Bug 10: numerazione SEPARATA per lezioni personalizzate e FULL.
                    // max() filtrato su is_full_lesson = false/null per non mischiare
                    // le sequenze (es. FULL 1-5, personalizzate 1-10).
                    $nextLessonNumber = (int) (
                        Lesson::query()
                            ->where('contract_id', $contract->id)
                            ->where('student_id', $studentId)
                            ->where(function ($q) { $q->where('is_full_lesson', false)->orWhereNull('is_full_lesson'); })
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

                        $duration = (int) ($slot->duration_minutes ?? 0);
                        if ($duration <= 0) {
                            // Bug 15: duration_minutes mancante o <= 0 normalizzato silenziosamente a 60 min.
                            // Log per segnalare lo slot anomalo senza interrompere la generazione.
                            Log::warning('LessonGeneratorService: duration_minutes non valido, normalizzato a 60 min', [
                                'contract_id'      => $contract->id,
                                'slot_id'          => $slot->id,
                                'duration_minutes' => $slot->duration_minutes,
                            ]);
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
                                // Bug 14: le ore residue sono inferiori alla durata
                                // minima di tutti gli slot disponibili — verranno perse.
                                // Log per rendere visibile la perdita silente.
                                if ($remainingHours > 0) {
                                    $remainingMinutes = (int) round($remainingHours * 60);
                                    Log::info('LessonGeneratorService: ore residue < durata slot, non generate', [
                                        'contract_id'       => $contract->id,
                                        'student_id'        => $studentId,
                                        'remaining_hours'   => $remainingHours,
                                        'remaining_minutes' => $remainingMinutes,
                                        'note'              => "Nessuno slot con durata <= {$remainingMinutes} min disponibile. Le ore residue non vengono generate.",
                                    ]);
                                }
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

                // Bug 11: aggiorna ends_at solo se non è già impostato manualmente.
                // Il generator calcola la fine dell'ultima lezione generata, ma
                // non deve sovrascrivere una scadenza contrattuale inserita dalla segreteria.
                // Se ends_at è null (mai compilato), il valore calcolato è l'unica
                // informazione disponibile e va memorizzato.
                if ($maxEnd && ! $contract->ends_at) {
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
        // Bug 7: usa $closureDaysCache caricato una volta all'inizio di generateForContract()
        // per evitare N+1 query DB (una per ogni candidato lezione nel loop).
        // ClosureDay casta start_date / end_date come Carbon ('date' cast nel model).
        $dateStr = $dt->toDateString();

        foreach ($this->closureDaysCache as $cd) {
            $startStr = $cd->start_date->toDateString();
            $endStr   = $cd->end_date?->toDateString();

            if ($dateStr >= $startStr && ($endStr === null || $dateStr <= $endStr)) {
                return true;
            }
        }

        return false;
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
