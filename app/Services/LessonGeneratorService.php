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

        $lock = Cache::lock("contract:{$contract->id}:generate_lessons", 10);

        if (! $lock->get()) {
            return;
        }

        try {
            DB::transaction(function () use ($contract, $force) {
                $today = now()->startOfDay();

                $defaultLanguage = method_exists($contract, 'getDefaultLanguage')
                    ? $contract->getDefaultLanguage()
                    : ($contract->language_id ?: null);

                $deleteQuery = Lesson::query()
                    ->where('contract_id', $contract->id)
                    ->whereNull('cancelled_at')
                    ->where(function ($q) {
                        $q->whereNull('counts_as_consumed')
                            ->orWhere('counts_as_consumed', 0);
                    })
                    ->whereColumn('updated_at', 'created_at');

                if (! $force) {
                    $deleteQuery->whereDate('starts_at', '>=', $today);
                }

                $deleteQuery->delete();

                $beneficiaries = DB::table('contract_students')
                    ->where('contract_id', $contract->id)
                    ->whereNotNull('student_id')
                    ->orderBy('id')
                    ->get();

                if ($beneficiaries->isEmpty()) {
                    return;
                }

                $maxEnd = null;

                foreach ($beneficiaries as $contractStudent) {
                    $studentId = (int) $contractStudent->student_id;
                    $contractStudentId = (int) $contractStudent->id;
                    $assignedHours = (float) ($contractStudent->assigned_hours ?? 0);

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

                    $hasAnyLessons = Lesson::query()
                        ->where('contract_id', $contract->id)
                        ->where('student_id', $studentId)
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

                    $existingFutureHours = (float) Lesson::query()
                        ->where('contract_id', $contract->id)
                        ->where('student_id', $studentId)
                        ->whereNull('cancelled_at')
                        ->sum(DB::raw('COALESCE(duration_minutes, TIMESTAMPDIFF(MINUTE, starts_at, ends_at)) / 60'));

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
