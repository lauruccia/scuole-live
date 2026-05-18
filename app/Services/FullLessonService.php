<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractStudent;
use App\Models\Lesson;
use Illuminate\Support\Facades\DB;

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
     */
    public function generatePlaceholders(Contract $contract): void
    {
        if (! self::isMixContract($contract)) {
            return;
        }

        $contract->loadMissing('beneficiaries');

        DB::transaction(function () use ($contract) {
            foreach ($contract->beneficiaries as $cs) {
                $this->generatePlaceholdersForBeneficiary($contract, $cs);
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

        $assignedHoursFull = (int) round((float) ($cs->assigned_hours_full ?? 0));

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

        $nextNumber = (int) (
            Lesson::query()
                ->where('contract_id', $contract->id)
                ->where('contract_student_id', $cs->id)
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

            $nextNumber = (int) (
                Lesson::query()
                    ->where('contract_id', $contract->id)
                    ->where('contract_student_id', $cs->id)
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
