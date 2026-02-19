<?php

namespace App\Observers;

use App\Models\Contract;
use App\Models\Lesson;

class LessonObserver
{

    public bool $afterCommit = true;

    public function saved(Lesson $lesson): void
    {
        $this->recalcContract($lesson);
    }

    public function deleted(Lesson $lesson): void
    {
        $this->recalcContract($lesson);
    }

    private function recalcContract(Lesson $lesson): void
    {
        if (! $lesson->contract_id) return;

        $contractId = (int) $lesson->contract_id;

        // SOLO manuale: consumata = counts_as_consumed = 1 e non annullata
        $consumedCount = Lesson::query()
    ->where('contract_id', $contractId)
    ->whereNull('cancelled_at')
    ->where('counts_as_consumed', true)
    ->count();

Contract::query()
    ->whereKey($contractId)
    ->update(['hours_consumed' => (float) $consumedCount]);
    }
}
