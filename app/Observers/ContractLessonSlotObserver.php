<?php

namespace App\Observers;

use App\Models\Contract;
use App\Models\ContractLessonSlot;
use App\Models\Lesson;
use App\Services\LessonGeneratorService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ContractLessonSlotObserver
{
    public function saved(ContractLessonSlot $slot): void
    {
        $this->regenerate($slot);
    }

    public function deleted(ContractLessonSlot $slot): void
    {
        $this->regenerate($slot);
    }

    private function regenerate(ContractLessonSlot $slot): void
    {
        $contractId = (int) $slot->contract_id;

        DB::afterCommit(function () use ($contractId) {
            $contract = Contract::query()->find($contractId);
            if (! $contract || ! $contract->starts_at) {
                return;
            }

            // Lock per evitare rigenerazioni multiple ravvicinate dall'Observer.
            // Usiamo get() invece di blockFor() per compatibilità con driver cache 'file'.
            $lock = Cache::lock("contract:{$contractId}:auto_regen_lessons", 30);
            if (! $lock->get()) {
                return;
            }

            try {
                $hasAnySlot = ContractLessonSlot::query()
                    ->where('contract_id', $contractId)
                    ->where('is_active', true)
                    ->whereNotNull('student_id')
                    ->exists();

                if (! $hasAnySlot) {
                    return;
                }

                $hasAnyLesson = Lesson::query()
                    ->where('contract_id', $contractId)
                    ->exists();

                if (! $hasAnyLesson) {
                    // Prima generazione: parte da starts_at (anche nel passato)
                    app(LessonGeneratorService::class)->generateForContract($contract, true, false);
                } else {
                    // Slot aggiunto/modificato/rimosso su contratto con lezioni esistenti:
                    // cancella e rigenera tutte le lezioni future non completate,
                    // ridistribuendo le ore rimanenti tra tutti gli slot aggiornati.
                    // Esempio: 10h + 1 slot 60min = 10 lezioni;
                    //          aggiunto 2° slot 60min = cancella future, rigenera 5+5 (alternando lunedì/mercoledì).
                    app(LessonGeneratorService::class)->generateForContract($contract, false, true);
                }
            } finally {
                optional($lock)->release();
            }
        });
    }
}
