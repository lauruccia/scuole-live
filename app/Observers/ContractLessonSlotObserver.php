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

            // evita chiamate multiple ravvicinate
            $lock = Cache::lock("contract:{$contractId}:auto_regen_lessons", 8);
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

                // ✅ se non esistono lezioni per il contratto, è “prima generazione”:
                // vogliamo partire da starts_at (anche se nel passato)
                $hasAnyLesson = Lesson::query()
                    ->where('contract_id', $contractId)
                    ->exists();

                $force = ! $hasAnyLesson;

                app(LessonGeneratorService::class)->generateForContract($contract, $force);
            } finally {
                optional($lock)->release();
            }
        });
    }
}
