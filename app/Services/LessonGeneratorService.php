<?php

namespace App\Services;

use App\Models\ClosureDay;
use App\Models\Contract;
use App\Models\ContractLessonSlot;
use App\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LessonGeneratorService
{
    public function generateForContract(Contract $contract, bool $force = false): void
    {
        $contract->loadMissing(['course', 'beneficiaries']);

        if (! $contract->starts_at) {
            return;
        }

        $lessonsCount = (int) ($contract->course?->lessons_count ?? 0);
        if ($lessonsCount <= 0) {
            return;
        }

        $lock = Cache::lock("contract:{$contract->id}:generate_lessons", 10);

        if (! $lock->get()) {
            return;
        }

        try {
            DB::transaction(function () use ($contract, $lessonsCount, $force) {

                $today = now()->startOfDay();

                // cancella solo NON svolte e NON annullate
                $deleteQuery = Lesson::query()
                    ->where('contract_id', $contract->id)
                    ->whereNull('cancelled_at')
                    ->where(function ($q) {
                        $q->whereNull('counts_as_consumed')
                          ->orWhere('counts_as_consumed', 0);
                    });

                if (! $force) {
                    $deleteQuery->whereDate('starts_at', '>=', $today);
                }

                $deleteQuery->delete();

                // slot attivi
                $slots = ContractLessonSlot::query()
                    ->where('contract_id', $contract->id)
                    ->where('is_active', true)
                    ->whereNotNull('student_id')
                    ->orderBy('student_id')
                    ->orderBy('weekly_day')
                    ->orderBy('weekly_time')
                    ->get();

                if ($slots->isEmpty()) {
                    return;
                }

                $slotsByStudent = $slots->groupBy(fn (ContractLessonSlot $s) => (int) $s->student_id);

                $maxEnd = null;

                foreach ($slotsByStudent as $studentId => $studentSlots) {

                    // ✅ capiamo se questo studente aveva già lezioni (prima della rigenerazione)
                    $hasAnyLessons = Lesson::query()
                        ->where('contract_id', $contract->id)
                        ->where('student_id', (int) $studentId)
                        ->exists();

                    $existingCount = Lesson::query()
                        ->where('contract_id', $contract->id)
                        ->where('student_id', (int) $studentId)
                        ->count();

                    $toGenerate = max(0, $lessonsCount - $existingCount);
                    if ($toGenerate <= 0) {
                        continue;
                    }

                    $contractStudentId = DB::table('contract_students')
                        ->where('contract_id', $contract->id)
                        ->where('student_id', (int) $studentId)
                        ->value('id');

                    if (! $contractStudentId) {
                        continue;
                    }

                    $nextLessonNumber = (int) (Lesson::query()
                        ->where('contract_id', $contract->id)
                        ->where('student_id', (int) $studentId)
                        ->max('lesson_number') ?? 0) + 1;

                    /**
                     * ✅ LOGICA DESIDERATA:
                     * - force=true -> sempre da starts_at contratto
                     * - force=false:
                     *    - se NON esistono ancora lezioni per questo studente -> da starts_at contratto
                     *    - se esistono -> da oggi (solo futuro)
                     */
                    $contractStartDay = Carbon::parse($contract->starts_at)->startOfDay();

                    $baseStartDay = $contractStartDay->copy();
                    if (! $force && $hasAnyLessons && $baseStartDay->lt($today)) {
                        $baseStartDay = $today;
                    }

                    // soglia datetime (evita creare nel passato rispetto a "adesso" quando stiamo aggiornando)
                    $notBefore = ($force || ! $hasAnyLessons)
                        ? Carbon::parse($contract->starts_at)->startOfDay()
                        : now();

                    $nextBySlot = [];
                    foreach ($studentSlots as $slot) {
                        $nextBySlot[$slot->id] = $this->nextOccurrenceForSlot($slot, $baseStartDay, $notBefore);
                    }

                    $generated = 0;
                    $guard = 0;

                    while ($generated < $toGenerate && $guard < 5000) {
                        $guard++;

                        [$slot, $startAt] = $this->pickNearest($studentSlots, $nextBySlot);
                        if (! $slot || ! $startAt) {
                            break;
                        }

                        $startAt = $this->shiftOutOfClosuresOrNull($startAt->copy());
                        if (! $startAt) {
                            $nextBySlot[$slot->id] = null;
                            continue;
                        }

                        // range slot
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

                        $duration = (int) ($slot->duration_minutes ?? 60);
                        if ($duration <= 0) $duration = 60;

                        $endAt = $startAt->copy()->addMinutes($duration);

                        // evita doppioni
                        $duplicate = Lesson::query()
                            ->where('contract_id', $contract->id)
                            ->where('student_id', (int) $studentId)
                            ->where('starts_at', $startAt->toDateTimeString())
                            ->exists();

                        if ($duplicate) {
                            $nextBySlot[$slot->id] = $startAt->copy()->addWeek();
                            continue;
                        }

                        // conflitto studente
                        $studentOverlap = Lesson::query()
                            ->where('student_id', (int) $studentId)
                            ->whereNull('cancelled_at')
                            ->where('starts_at', '<', $endAt)
                            ->where('ends_at', '>', $startAt)
                            ->exists();

                        if ($studentOverlap) {
                            $nextBySlot[$slot->id] = $startAt->copy()->addWeek();
                            continue;
                        }

                        // conflitto docente
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
                            'contract_student_id' => (int) $contractStudentId,
                            'student_id'          => (int) $studentId,
                            'teacher_id'          => $slot->teacher_id ? (int) $slot->teacher_id : null,
                            'starts_at'           => $startAt,
                            'ends_at'             => $endAt,
                            'meet_url'            => $slot->meet_url,
                            'duration_minutes'    => $duration,
                            'lesson_number'       => $nextLessonNumber,
                            'counts_as_consumed'  => 0,
                            'is_recoverable'      => 0,
                        ]);

                        $generated++;
                        $nextLessonNumber++;

                        $maxEnd = $maxEnd ? Carbon::parse($maxEnd)->max($endAt) : $endAt;
                        $nextBySlot[$slot->id] = $startAt->copy()->addWeek();
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
            if (! $dt) continue;

            if (! $bestDt || $dt->lt($bestDt)) {
                $bestDt = $dt;
                $bestSlot = $slot;
            }
        }

        return [$bestSlot, $bestDt];
    }

    private function nextOccurrenceForSlot(ContractLessonSlot $slot, Carbon $fromDay, Carbon $notBefore): ?Carbon
    {
        $day = (int) $slot->weekly_day; // 1..7 ISO
        if ($day < 1 || $day > 7) return null;

        $time = (string) $slot->weekly_time; // "HH:MM:SS"
        $hour = (int) substr($time, 0, 2);
        $min  = (int) substr($time, 3, 2);

        $base = $fromDay->copy()->setTime($hour, $min, 0);

        $currentDow = (int) $base->dayOfWeekIso;
        $delta = $day - $currentDow;
        if ($delta < 0) $delta += 7;

        $candidate = $base->copy()->addDays($delta);

        // sposta avanti finché supera la soglia notBefore
        $guard = 0;
        while ($candidate->lt($notBefore) && $guard < 1040) {
            $candidate->addWeek();
            $guard++;
        }

        return $candidate;
    }

    private function isClosureDay(Carbon $dt): bool
    {
        static $hasDateColumn = null;
        if ($hasDateColumn === null) {
            $hasDateColumn = Schema::hasColumn('closure_days', 'date');
        }

        if ($hasDateColumn) {
            return ClosureDay::query()->whereDate('date', $dt->toDateString())->exists();
        }

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
            if ($limit > 104) break;
        }
        return $dt;
    }

    private function shiftOutOfClosuresOrNull(Carbon $dt): ?Carbon
    {
        $shifted = $this->shiftOutOfClosures($dt);
        return $this->isClosureDay($shifted) ? null : $shifted;
    }
}
