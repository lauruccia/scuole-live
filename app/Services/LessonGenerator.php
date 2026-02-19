<?php

namespace App\Services;

use App\Models\ClosureDay;
use App\Models\Contract;
use App\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LessonGenerator
{
    public function appendMissing(Contract $contract): int
    {
        $courseLessons = (int) ($contract->course?->lessons_count ?? 0);
        if ($courseLessons <= 0) return 0;

        $lock = Cache::lock("contract:{$contract->id}:generate_lessons_safe", 20);

        if (! $lock->get()) {
            // qualcun altro sta generando: esci
            return 0;
        }

        try {
            return DB::transaction(function () use ($contract, $courseLessons) {
                $createdTotal = 0;

                $contract->loadMissing('beneficiaries');

                foreach ($contract->beneficiaries as $cs) {
                    if (! $cs->weekly_day || ! $cs->weekly_time) {
                        continue;
                    }

                    $existingCount = Lesson::query()
                        ->where('contract_id', $contract->id)
                        ->where('contract_student_id', $cs->id)
                        ->count();

                    if ($existingCount >= $courseLessons) {
                        continue;
                    }

                    $startDate = Carbon::parse($contract->starts_at ?? now())->startOfDay();
                    $time = Carbon::parse($cs->weekly_time)->format('H:i:s');

                    $current = $this->firstOccurrence($startDate, (int) $cs->weekly_day)
                        ->setTimeFromTimeString($time);

                    $duration = 60;

                    $createdForThis = 0;

                    // safety
                    $maxIterations = $courseLessons * 30;
                    $iterations = 0;

                    while (($existingCount + $createdForThis) < $courseLessons && $iterations < $maxIterations) {
                        $iterations++;

                        while ($this->isClosureDate($current)) {
                            $current->addWeek();
                        }

                        // ✅ IDPOTENZA: se esiste già lo slot, salta
                        $existsSameSlot = Lesson::query()
                            ->where('contract_id', $contract->id)
                            ->where('contract_student_id', $cs->id)
                            ->where('starts_at', $current->copy())
                            ->exists();

                        if (! $existsSameSlot) {
                            Lesson::create([
                                'contract_id' => $contract->id,
                                'contract_student_id' => $cs->id,
                                'student_id' => $cs->student_id,
                                'teacher_id' => $cs->teacher_id ?: null,
                                'starts_at' => $current->copy(),
                                'ends_at' => $current->copy()->addMinutes($duration),
                                'duration_minutes' => $duration,
                                'counts_as_consumed' => true,
                                'is_recoverable' => false,
                            ]);

                            $createdForThis++;
                            $createdTotal++;
                        }

                        $current->addWeek();
                    }
                }

                return $createdTotal;
            });
        } finally {
            optional($lock)->release();
        }
    }

    public function regenerateFuture(Contract $contract): int
    {
        $courseLessons = (int) ($contract->course?->lessons_count ?? 0);
        if ($courseLessons <= 0) return 0;

        $lock = Cache::lock("contract:{$contract->id}:generate_lessons_danger", 30);

        if (! $lock->get()) {
            return 0;
        }

        try {
            return DB::transaction(function () use ($contract, $courseLessons) {
                $createdTotal = 0;

                $contract->loadMissing('beneficiaries');

                foreach ($contract->beneficiaries as $cs) {
                    if (! $cs->weekly_day || ! $cs->weekly_time) {
                        continue;
                    }

                    Lesson::query()
                        ->where('contract_id', $contract->id)
                        ->where('contract_student_id', $cs->id)
                        ->whereNull('cancelled_at')
                        ->where('starts_at', '>=', now())
                        ->delete();

                    $startDate = Carbon::parse($contract->starts_at ?? now())->startOfDay();
                    $time = Carbon::parse($cs->weekly_time)->format('H:i:s');

                    $current = $this->firstOccurrence($startDate, (int) $cs->weekly_day)
                        ->setTimeFromTimeString($time);

                    $duration = 60;

                    $createdForThis = 0;
                    $maxIterations = $courseLessons * 30;
                    $iterations = 0;

                    while ($createdForThis < $courseLessons && $iterations < $maxIterations) {
                        $iterations++;

                        while ($this->isClosureDate($current)) {
                            $current->addWeek();
                        }

                        Lesson::create([
                            'contract_id' => $contract->id,
                            'contract_student_id' => $cs->id,
                            'student_id' => $cs->student_id,
                            'teacher_id' => $cs->teacher_id ?: null,
                            'starts_at' => $current->copy(),
                            'ends_at' => $current->copy()->addMinutes($duration),
                            'duration_minutes' => $duration,
                            'counts_as_consumed' => true,
                            'is_recoverable' => false,
                        ]);

                        $createdForThis++;
                        $createdTotal++;

                        $current->addWeek();
                    }
                }

                return $createdTotal;
            });
        } finally {
            optional($lock)->release();
        }
    }

    private function isClosureDate(Carbon $dt): bool
    {
        $date = $dt->toDateString();

        return ClosureDay::query()
            ->where(function ($q) use ($date) {
                $q->where('start_date', $date)
                  ->orWhere(function ($q) use ($date) {
                      $q->where('start_date', '<=', $date)
                        ->whereNotNull('end_date')
                        ->where('end_date', '>=', $date);
                  });
            })
            ->exists();
    }

    private function firstOccurrence(Carbon $start, int $isoWeekday): Carbon
    {
        $d = $start->copy();
        while ((int) $d->isoWeekday() !== $isoWeekday) {
            $d->addDay();
        }
        return $d;
    }
}
