<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractStudent;
use App\Models\Lesson;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Gestisce i segnaposto FULL nei contratti MIX (Lezioni personalizzate + FULL).
 *
 * Regole di business:
 * - Ogni lezione FULL ha durata fissa di 60 minuti.
 * - N segnaposto = ore FULL assegnate al beneficiario (es. 5 ore → 5 segnaposto).
 * - I segnaposto vengono creati senza data/ora/docente ("da definire").
 * - Nessun controllo di sovrapposizione per lezioni FULL.
 * - La segreteria compila data/ora/docente manualmente per ogni segnaposto.
 * - In caso di modifica delle ore FULL post-conferma, si rigenerano solo i
 *   segnaposto con starts_at = NULL (non le lezioni completate o annullate).
 */
class FullLessonService
{
    public const FULL_LESSON_DURATION_MINUTES = 60;

    /**
     * Contratto MIX? (tipologia "Lezioni personalizzate + FULL")
     */
    public static function isMixContract(Contract $contract): bool
    {
        return ($contract->lesson_type ?? '') === 'Lezioni personalizzate + FULL';
    }

    /**
     * Distribuisce le ore FULL tra i beneficiari del contratto in modo equo.
     *
     * Algoritmo:
     *   base      = floor(hours_full / n_beneficiari)
     *   avanzo    = hours_full - base * n_beneficiari   (in ore intere)
     *   beneficiario[0] → base + avanzo
     *   beneficiario[i≠0] → base
     *
     * Es: 5 ore, 2 studenti → studente[0]=3, studente[1]=2
     *
     * Non salva se il beneficiario ha già assigned_hours_full configurato manualmente
     * (permette alla segreteria di gestirle con libertà dopo la prima distribuzione).
     * Imposta invece solo i beneficiari che non hanno ancora un valore.
     */
    public function distributeFullHours(Contract $contract): void
    {
        $beneficiaries = $contract->beneficiaries()->orderBy('id')->get();
        $n = $beneficiaries->count();

        if ($n === 0) {
            return;
        }

        $totalFull = (int) round((float) ($contract->hours_full ?? 0));

        if ($totalFull <= 0) {
            return;
        }

        $base      = (int) floor($totalFull / $n);
        $remainder = $totalFull - ($base * $n); // ore avanzate al primo beneficiario

        foreach ($beneficiaries as $i => $cs) {
            // Non sovrascrivere se già impostato manualmente con valore > 0
            if (! is_null($cs->assigned_hours_full) && (float) $cs->assigned_hours_full > 0) {
                continue;
            }

            $hours = $base + ($i === 0 ? $remainder : 0);
            $cs->assigned_hours_full = $hours > 0 ? (float) $hours : null;
            $cs->saveQuietly();
        }
    }

    /**
     * Genera i segnaposto FULL per tutti i beneficiari del contratto.
     * Salta i beneficiari che hanno già segnaposto "da definire" esistenti,
     * evitando duplicati.
     *
     * Difesa multi-livello:
     *  1) Pulizia orfani: i segnaposto FULL "da definire" il cui
     *     `contract_student_id` non appartiene più ad alcun beneficiario
     *     attuale del contratto (es. il vecchio ContractStudent è stato
     *     ricreato con id diverso dopo una modifica del flag
     *     "intestatario = studente") vengono eliminati. Senza questa
     *     pulizia il count per-beneficiario li ignora e il generatore
     *     crea altri 5 segnaposto, raddoppiando il monte ore FULL.
     *  2) Cap a livello contratto: il totale dei segnaposto FULL attivi
     *     (non completati / non annullati) non può mai superare
     *     `contracts.hours_full`. Se per qualunque motivo c'è eccedenza,
     *     i "da definire" più recenti vengono rimossi.
     */
    public function generatePlaceholders(Contract $contract): void
    {
        if (! self::isMixContract($contract)) {
            return;
        }

        $contract->loadMissing('beneficiaries');

        DB::transaction(function () use ($contract) {
            // (1) Pulizia segnaposto FULL orfani
            $beneficiaryIds = $contract->beneficiaries->pluck('id')->filter()->values()->all();

            $orphanQuery = Lesson::query()
                ->where('contract_id', $contract->id)
                ->where('is_full_lesson', true)
                ->whereNull('starts_at')
                ->whereNull('completed_at')
                ->whereNull('cancelled_at');

            if (empty($beneficiaryIds)) {
                // Nessun beneficiario corrente: tutti i "da definire" sono orfani.
                $orphanQuery->forceDelete();
            } else {
                $orphanQuery
                    ->where(function ($q) use ($beneficiaryIds) {
                        $q->whereNull('contract_student_id')
                          ->orWhereNotIn('contract_student_id', $beneficiaryIds);
                    })
                    ->forceDelete();
            }

            // Generazione standard per beneficiario
            foreach ($contract->beneficiaries as $cs) {
                $this->generatePlaceholdersForBeneficiary($contract, $cs);
            }

            // (2) Cap a livello contratto su hours_full
            $contractHoursFull = (int) round((float) ($contract->hours_full ?? 0));

            if ($contractHoursFull <= 0) {
                return;
            }

            $activePlaceholders = Lesson::query()
                ->where('contract_id', $contract->id)
                ->where('is_full_lesson', true)
                ->whereNull('completed_at')
                ->whereNull('cancelled_at')
                ->count();

            if ($activePlaceholders <= $contractHoursFull) {
                return;
            }

            $excess = $activePlaceholders - $contractHoursFull;

            // Rimuoviamo solo i "da definire" più recenti (mai pianificati,
            // mai completati, mai annullati). Le lezioni già con data restano
            // intoccate: la segreteria le ha già pianificate consapevolmente.
            // Prima preleviamo gli id e poi facciamo il DELETE: alcuni driver
            // non supportano LIMIT direttamente sulla DELETE via Eloquent.
            $idsToRemove = Lesson::query()
                ->where('contract_id', $contract->id)
                ->where('is_full_lesson', true)
                ->whereNull('starts_at')
                ->whereNull('completed_at')
                ->whereNull('cancelled_at')
                ->orderByDesc('id')
                ->limit($excess)
                ->pluck('id')
                ->all();

            if (! empty($idsToRemove)) {
                Lesson::query()->whereIn('id', $idsToRemove)->forceDelete();
            }

            // Bug 9: se l'eccesso non è stato azzerato completamente (perché parte
            // dei segnaposto in eccesso ha già una data pianificata e non viene toccata),
            // logga un warning esplicito affinché la segreteria possa intervenire manualmente.
            $removedCount = count($idsToRemove);
            if ($removedCount < $excess) {
                $stillExcess = $excess - $removedCount;
                Log::warning('FullLessonService: cap FULL parziale — lezioni pianificate in eccesso non rimosse', [
                    'contract_id'     => $contract->id,
                    'hours_full_cap'  => $contractHoursFull,
                    'active_total'    => $activePlaceholders,
                    'removed'         => $removedCount,
                    'still_excess'    => $stillExcess,
                    'note'            => 'Le lezioni pianificate (starts_at non null) non vengono mai rimosse automaticamente. Verificare e annullare manualmente quelle in eccesso.',
                ]);
            }
        });
    }

    /**
     * Genera i segnaposto FULL per un singolo beneficiario.
     * Tiene conto dei segnaposto già esistenti per non crearne in eccesso.
     */
    public function generatePlaceholdersForBeneficiary(Contract $contract, ContractStudent $cs): void
    {
        if (! self::isMixContract($contract)) {
            return;
        }

        $rawHoursFull      = (float) ($cs->assigned_hours_full ?? 0);
        $assignedHoursFull = (int) round($rawHoursFull);

        // Bug 16: assigned_hours_full decimale (es. 1.5) viene arrotondato a intero per il
        // conteggio dei segnaposto (1 segnaposto = 1 ora FULL). Logga se c'è perdita di decimali
        // significativi (> 0.05h ≈ 3 min) per far emergere eventuali configurazioni errate.
        if ($rawHoursFull > 0 && abs($rawHoursFull - $assignedHoursFull) > 0.05) {
            Log::warning('FullLessonService: assigned_hours_full decimale arrotondato a intero', [
                'contract_student_id' => $cs->id,
                'contract_id'         => $cs->contract_id,
                'raw_value'           => $rawHoursFull,
                'rounded_to'          => $assignedHoursFull,
                'note'                => 'Le ore FULL devono essere intere (1 ora = 1 segnaposto). Correggere il valore nel contratto.',
            ]);
        }

        if ($assignedHoursFull <= 0) {
            return;
        }

        // Quanti segnaposto "da definire" esistono già?
        $existingPlaceholders = Lesson::query()
            ->where('contract_id', $contract->id)
            ->where('contract_student_id', $cs->id)
            ->where('is_full_lesson', true)
            ->whereNull('starts_at')
            ->whereNull('cancelled_at')
            ->whereNull('completed_at')
            ->count();

        // Quante lezioni FULL sono già completate o annullate?
        $doneOrCancelled = Lesson::query()
            ->where('contract_id', $contract->id)
            ->where('contract_student_id', $cs->id)
            ->where('is_full_lesson', true)
            ->where(function ($q) {
                $q->whereNotNull('completed_at')
                  ->orWhereNotNull('cancelled_at');
            })
            ->count();

        // Quanti segnaposto pianificati (con data ma non completati/annullati)?
        $scheduled = Lesson::query()
            ->where('contract_id', $contract->id)
            ->where('contract_student_id', $cs->id)
            ->where('is_full_lesson', true)
            ->whereNotNull('starts_at')
            ->whereNull('cancelled_at')
            ->whereNull('completed_at')
            ->count();

        $totalExisting   = $existingPlaceholders + $doneOrCancelled + $scheduled;
        $toCreate        = max(0, $assignedHoursFull - $totalExisting);

        if ($toCreate <= 0) {
            return;
        }

        $defaultLanguage = method_exists($contract, 'getDefaultLanguage')
            ? $contract->getDefaultLanguage()
            : ($contract->language_id ?: null);

        // Bug 10: numerazione SEPARATA lezioni FULL — max() filtrato su is_full_lesson = true
        // per non mischiare la sequenza con le lezioni personalizzate dello stesso beneficiario.
        $nextNumber = (int) (
            Lesson::query()
                ->where('contract_id', $contract->id)
                ->where('contract_student_id', $cs->id)
                ->where('is_full_lesson', true)
                ->max('lesson_number') ?? 0
        ) + 1;

        for ($i = 0; $i < $toCreate; $i++) {
            Lesson::create([
                'contract_id'        => $contract->id,
                'contract_student_id' => $cs->id,
                'student_id'         => $cs->student_id,
                'teacher_id'         => null,
                'starts_at'          => null,
                'ends_at'            => null,
                'duration_minutes'   => self::FULL_LESSON_DURATION_MINUTES,
                'is_full_lesson'     => true,
                'counts_as_consumed' => false,
                'is_recoverable'     => false,
                'language_id'        => $defaultLanguage,
                'lesson_number'      => $nextNumber + $i,
                'notes'              => 'Lezione FULL — in attesa di pianificazione.',
            ]);
        }
    }

    /**
     * Rigenera i segnaposto FULL per un beneficiario dopo modifica delle ore assegnate.
     *
     * Logica:
     * 1. Elimina i segnaposto "da definire" (starts_at = NULL, non completati/annullati).
     * 2. Crea i nuovi segnaposto: n = nuove_ore - già_completate - già_annullate - già_pianificate.
     *
     * Non tocca mai le lezioni completate o annullate.
     *
     * @throws \RuntimeException se le nuove ore scendono sotto le ore già erogate
     */
    public function regeneratePlaceholdersForBeneficiary(
        Contract $contract,
        ContractStudent $cs,
        float $newHoursFullAssigned
    ): void {
        $newHours = (int) round($newHoursFullAssigned);

        // Bug 16: log se il valore passato ha decimali significativi (> 0.05)
        if (abs($newHoursFullAssigned - $newHours) > 0.05) {
            Log::warning('FullLessonService: newHoursFullAssigned decimale arrotondato a intero', [
                'contract_student_id' => $cs->id,
                'contract_id'         => $cs->contract_id,
                'raw_value'           => $newHoursFullAssigned,
                'rounded_to'          => $newHours,
                'note'                => 'Le ore FULL devono essere intere (1 ora = 1 segnaposto). Correggere il valore nel contratto.',
            ]);
        }

        // Conta lezioni completate e annullate (non modificabili)
        $completed = Lesson::query()
            ->where('contract_id', $contract->id)
            ->where('contract_student_id', $cs->id)
            ->where('is_full_lesson', true)
            ->whereNotNull('completed_at')
            ->count();

        $cancelled = Lesson::query()
            ->where('contract_id', $contract->id)
            ->where('contract_student_id', $cs->id)
            ->where('is_full_lesson', true)
            ->whereNotNull('cancelled_at')
            ->count();

        $immutable = $completed + $cancelled;

        // Blocca se il nuovo totale scende sotto le ore già erogate
        if ($newHours < $immutable) {
            throw new \RuntimeException(
                "Impossibile ridurre le ore FULL al di sotto di quelle già erogate ({$immutable}h) per questo beneficiario."
            );
        }

        DB::transaction(function () use ($contract, $cs, $newHours, $immutable) {
            // Elimina solo i segnaposto "da definire" (starts_at = NULL, non completati/annullati)
            Lesson::query()
                ->where('contract_id', $contract->id)
                ->where('contract_student_id', $cs->id)
                ->where('is_full_lesson', true)
                ->whereNull('starts_at')
                ->whereNull('completed_at')
                ->whereNull('cancelled_at')
                ->forceDelete();

            // Quante lezioni FULL pianificate (con data, non completate/annullate) restano?
            $scheduled = Lesson::query()
                ->where('contract_id', $contract->id)
                ->where('contract_student_id', $cs->id)
                ->where('is_full_lesson', true)
                ->whereNotNull('starts_at')
                ->whereNull('completed_at')
                ->whereNull('cancelled_at')
                ->count();

            // Nuovi segnaposto da creare
            $toCreate = max(0, $newHours - $immutable - $scheduled);

            // Aggiorna le ore assegnate al beneficiario
            $cs->assigned_hours_full = (float) $newHours;
            $cs->saveQuietly();

            if ($toCreate <= 0) {
                return;
            }

            $defaultLanguage = method_exists($contract, 'getDefaultLanguage')
                ? $contract->getDefaultLanguage()
                : ($contract->language_id ?: null);

            // Bug 10: numerazione SEPARATA lezioni FULL
            $nextNumber = (int) (
                Lesson::query()
                    ->where('contract_id', $contract->id)
                    ->where('contract_student_id', $cs->id)
                    ->where('is_full_lesson', true)
                    ->max('lesson_number') ?? 0
            ) + 1;

            for ($i = 0; $i < $toCreate; $i++) {
                Lesson::create([
                    'contract_id'         => $contract->id,
                    'contract_student_id' => $cs->id,
                    'student_id'          => $cs->student_id,
                    'teacher_id'          => null,
                    'starts_at'           => null,
                    'ends_at'             => null,
                    'duration_minutes'    => self::FULL_LESSON_DURATION_MINUTES,
                    'is_full_lesson'      => true,
                    'counts_as_consumed'  => false,
                    'is_recoverable'      => false,
                    'language_id'         => $defaultLanguage,
                    'lesson_number'       => $nextNumber + $i,
                    'notes'               => 'Lezione FULL — in attesa di pianificazione.',
                ]);
            }
        });
    }

    /**
     * Valida la modifica delle ore FULL di un beneficiario.
     * Restituisce null se valido, altrimenti il messaggio di errore.
     *
     * Vincolo 1: nuove_ore >= ore_erogate (completate + annullate)
     * Vincolo 2: somma ore_full di tutti i beneficiari == contract.hours_full
     */
    public function validateFullHoursUpdate(
        Contract $contract,
        ContractStudent $cs,
        float $newHours,
        float $newContractHoursFull
    ): ?string {
        $newHours = (int) round($newHours);

        // Vincolo 1: non scendere sotto le ore già erogate
        $immutable = Lesson::query()
            ->where('contract_id', $contract->id)
            ->where('contract_student_id', $cs->id)
            ->where('is_full_lesson', true)
            ->where(function ($q) {
                $q->whereNotNull('completed_at')
                  ->orWhereNotNull('cancelled_at');
            })
            ->count();

        if ($newHours < $immutable) {
            return "Impossibile ridurre le ore FULL al di sotto di quelle già erogate ({$immutable}h tra completate e annullate) per questo beneficiario.";
        }

        // Vincolo 2: la somma delle ore FULL di tutti i beneficiari deve == contract.hours_full
        $sumOthers = (float) $contract->beneficiaries()
            ->where('id', '!=', $cs->id)
            ->sum('assigned_hours_full');

        $total = $sumOthers + $newHours;
        $contractHoursFull = (int) round($newContractHoursFull);

        if ((int) round($total) !== $contractHoursFull) {
            $diff = $contractHoursFull - (int) round($sumOthers);
            return "Il totale delle ore FULL assegnate deve corrispondere alle ore FULL del contratto ({$contractHoursFull}h). Agli altri beneficiari sono già assegnate {$sumOthers}h: il valore corretto per questo beneficiario è {$diff}h.";
        }

        return null;
    }
}
