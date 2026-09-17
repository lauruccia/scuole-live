<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractLessonSlot;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\HtmlString;

/**
 * Rileva i conflitti di orario di uno slot settimanale PRIMA di generare le lezioni.
 *
 * Il LessonGeneratorService salta in silenzio le settimane in cui il docente o lo
 * studente sono già impegnati (es. contratto 144: lezioni spostate a marzo 2027
 * perché il docente aveva già il mercoledì 18:00 con il contratto 137).
 * Questo servizio produce l'elenco dei conflitti da mostrare all'operatore, che può
 * scegliere se creare comunque le lezioni (comportamento attuale) o modificare lo slot.
 *
 * Considera:
 *  - lezioni NON annullate e NON eliminate di ALTRI contratti con lo stesso docente
 *    o lo stesso studente, nello stesso giorno della settimana e con orario sovrapposto,
 *    nel periodo [max(inizio contratto, oggi) … fine contratto];
 *  - altri slot attivi dello STESSO contratto con lo stesso docente/studente e orario
 *    sovrapposto (le loro lezioni vengono rigenerate insieme, quindi non esistono ancora).
 */
class SlotConflictService
{
    public const DAY_NAMES = [
        1 => 'lunedì',
        2 => 'martedì',
        3 => 'mercoledì',
        4 => 'giovedì',
        5 => 'venerdì',
        6 => 'sabato',
        7 => 'domenica',
    ];

    /**
     * Conflitti per un singolo slot (creazione/modifica dalla tabella Lesson Slots).
     *
     * @param  array{student_id?:mixed, teacher_id?:mixed, weekly_day?:mixed, weekly_time?:mixed, duration_minutes?:mixed}  $slot
     * @return array<int, array<string, mixed>>
     */
    public function forSlotData(Contract $contract, array $slot, ?int $excludeSlotId = null): array
    {
        $normalized = $this->normalizeSlot($slot);

        if (! $normalized) {
            return [];
        }

        $normalized['id'] = $excludeSlotId;

        $otherSlots = ContractLessonSlot::query()
            ->where('contract_id', $contract->id)
            ->where('is_active', true)
            ->when($excludeSlotId, fn ($q) => $q->where('id', '!=', $excludeSlotId))
            ->get()
            ->map(fn (ContractLessonSlot $s) => $this->normalizeSlot($s->toArray() + ['id' => $s->id]))
            ->filter()
            ->values()
            ->all();

        return $this->detect(
            $contract->id,
            $contract->starts_at,
            $contract->ends_at,
            [$normalized],
            $otherSlots
        );
    }

    /**
     * Conflitti di tutti gli slot attivi di un contratto (Salva contratto, Rigenera, Completa).
     *
     * @return array<int, array<string, mixed>>
     */
    public function forContract(Contract $contract, mixed $startsAt = null, mixed $endsAt = null): array
    {
        $slots = ContractLessonSlot::query()
            ->where('contract_id', $contract->id)
            ->where('is_active', true)
            ->orderBy('weekly_day')
            ->orderBy('weekly_time')
            ->get()
            ->map(fn (ContractLessonSlot $s) => $this->normalizeSlot($s->toArray() + ['id' => $s->id]))
            ->filter()
            ->values()
            ->all();

        if (empty($slots)) {
            return [];
        }

        return $this->detect(
            $contract->id,
            $startsAt ?? $contract->starts_at,
            $endsAt ?? $contract->ends_at,
            $slots,
            $slots
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $slots       slot da verificare
     * @param  array<int, array<string, mixed>>  $sameContractSlots  slot attivi dello stesso contratto
     * @return array<int, array<string, mixed>>
     */
    protected function detect(int $contractId, mixed $startsAt, mixed $endsAt, array $slots, array $sameContractSlots): array
    {
        if (! $startsAt) {
            return [];
        }

        $from = Carbon::parse($startsAt)->startOfDay();
        if ($from->lt(now()->startOfDay())) {
            $from = now()->startOfDay();
        }

        $to = $endsAt ? Carbon::parse($endsAt)->endOfDay() : null;

        if ($to && $to->lt($from)) {
            return [];
        }

        $conflicts = [];
        $seenPairs = [];

        foreach ($slots as $slot) {
            $teacherId = $slot['teacher_id'];
            $studentId = $slot['student_id'];

            if (! $teacherId && ! $studentId) {
                continue;
            }

            // ── 1) Lezioni di altri contratti ────────────────────────────────
            $lessons = Lesson::query()
                ->where('contract_id', '!=', $contractId)
                ->whereNull('cancelled_at')
                ->whereNotNull('starts_at')
                ->where('starts_at', '>=', $from)
                ->when($to, fn ($q) => $q->where('starts_at', '<=', $to))
                ->where(function ($q) use ($teacherId, $studentId) {
                    if ($teacherId) {
                        $q->orWhere('teacher_id', $teacherId);
                    }
                    if ($studentId) {
                        $q->orWhere('student_id', $studentId);
                    }
                })
                ->orderBy('starts_at')
                ->get(['id', 'contract_id', 'student_id', 'teacher_id', 'starts_at', 'ends_at', 'duration_minutes']);

            $groups = [];

            foreach ($lessons as $lesson) {
                $start = Carbon::parse($lesson->starts_at);

                if ((int) $start->dayOfWeekIso !== $slot['weekly_day']) {
                    continue;
                }

                $lessonStartMin = $start->hour * 60 + $start->minute;
                $lessonEndMin   = $lesson->ends_at
                    ? $lessonStartMin + max(1, (int) round($start->diffInMinutes(Carbon::parse($lesson->ends_at), true)))
                    : $lessonStartMin + max(1, (int) ($lesson->duration_minutes ?: 60));

                if (! ($lessonStartMin < $slot['end_min'] && $lessonEndMin > $slot['start_min'])) {
                    continue;
                }

                $types = [];
                if ($teacherId && (int) $lesson->teacher_id === $teacherId) {
                    $types[] = 'teacher';
                }
                if ($studentId && (int) $lesson->student_id === $studentId) {
                    $types[] = 'student';
                }

                foreach ($types as $type) {
                    $key = $type . '|' . $lesson->contract_id . '|' . (int) $lesson->student_id . '|' . (int) $lesson->teacher_id;

                    $groups[$key] ??= [
                        'type'         => $type,
                        'slot'         => $slot,
                        'contract_id'  => (int) $lesson->contract_id,
                        'student_id'   => $lesson->student_id ? (int) $lesson->student_id : null,
                        'teacher_id'   => $lesson->teacher_id ? (int) $lesson->teacher_id : null,
                        'count'        => 0,
                        'first'        => $start->copy(),
                        'last'         => $start->copy(),
                        'same_contract'=> false,
                    ];

                    $groups[$key]['count']++;
                    $groups[$key]['last'] = $start->copy();
                }
            }

            foreach ($groups as $group) {
                $conflicts[] = $group;
            }

            // ── 2) Altri slot attivi dello stesso contratto ──────────────────
            foreach ($sameContractSlots as $other) {
                if ($slot['id'] && $other['id'] === $slot['id']) {
                    continue;
                }

                if ($other['weekly_day'] !== $slot['weekly_day']) {
                    continue;
                }

                if (! ($other['start_min'] < $slot['end_min'] && $other['end_min'] > $slot['start_min'])) {
                    continue;
                }

                $sameTeacher = $teacherId && $other['teacher_id'] === $teacherId;
                $sameStudent = $studentId && $other['student_id'] === $studentId;

                if (! $sameTeacher && ! $sameStudent) {
                    continue;
                }

                // Evita di segnalare due volte la stessa coppia (A↔B e B↔A)
                $pair = collect([$slot['id'] ?? 'new', $other['id'] ?? 'new'])->sort()->implode('-');
                if (isset($seenPairs[$pair])) {
                    continue;
                }
                $seenPairs[$pair] = true;

                $conflicts[] = [
                    'type'          => $sameTeacher ? 'teacher' : 'student',
                    'slot'          => $slot,
                    'contract_id'   => $contractId,
                    'student_id'    => $sameTeacher ? $other['student_id'] : $studentId,
                    'teacher_id'    => $sameTeacher ? $teacherId : $other['teacher_id'],
                    'count'         => 0,
                    'first'         => null,
                    'last'          => null,
                    'same_contract' => true,
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Messaggi leggibili, uno per conflitto.
     *
     * @param  array<int, array<string, mixed>>  $conflicts
     * @return array<int, string>
     */
    public function messages(array $conflicts): array
    {
        $studentIds = collect($conflicts)->flatMap(fn ($c) => [$c['student_id'], $c['slot']['student_id']])->filter()->unique()->all();
        $teacherIds = collect($conflicts)->flatMap(fn ($c) => [$c['teacher_id'], $c['slot']['teacher_id']])->filter()->unique()->all();

        $students = Student::query()->whereIn('id', $studentIds)->get()->keyBy('id');
        $teachers = User::query()->whereIn('id', $teacherIds)->get()->keyBy('id');

        $studentName = function (?int $id) use ($students): string {
            $s = $id ? $students->get($id) : null;
            $name = $s ? trim((string) $s->full_name) : '';

            return $name !== '' ? $name : ($id ? "#{$id}" : "non indicato");
        };

        $teacherName = function (?int $id) use ($teachers): string {
            $u = $id ? $teachers->get($id) : null;
            $name = $u ? trim((string) ($u->name ?? '')) : '';

            return $name !== '' ? $name : ($id ? "#{$id}" : "non indicato");
        };

        $messages = [];

        foreach ($conflicts as $c) {
            $slot = $c['slot'];
            $when = 'il ' . self::DAY_NAMES[$slot['weekly_day']] . ' alle ' . $slot['time_label'];

            $period = $c['same_contract']
                ? 'in un altro slot di questo contratto'
                : sprintf(
                    '(contratto #%d: %d %s dal %s al %s)',
                    $c['contract_id'],
                    $c['count'],
                    $c['count'] === 1 ? 'lezione' : 'lezioni',
                    $c['first']->format('d/m/Y'),
                    $c['last']->format('d/m/Y')
                );

            if ($c['type'] === 'teacher') {
                $messages[] = sprintf(
                    'Il docente %s è già impegnato %s con lo studente %s %s.',
                    $teacherName($slot['teacher_id']),
                    $when,
                    $studentName($c['student_id']),
                    $period
                );
            } else {
                $messages[] = sprintf(
                    'Lo studente %s ha già lezione %s con il docente %s %s.',
                    $studentName($slot['student_id']),
                    $when,
                    $teacherName($c['teacher_id']),
                    $period
                );
            }
        }

        return array_values(array_unique($messages));
    }

    /**
     * Testo del modale di conferma.
     *
     * @param  array<int, string>  $messages
     */
    public static function toHtml(array $messages, ?string $intro = null): HtmlString
    {
        $items = collect($messages)
            ->map(fn (string $m) => '<li style="margin-bottom:.35rem">' . e($m) . '</li>')
            ->implode('');

        $html = '';

        if ($intro) {
            $html .= '<p style="margin-bottom:.75rem">' . e($intro) . '</p>';
        }

        $html .= '<ul style="text-align:left;list-style:disc;padding-left:1.25rem;margin-bottom:.75rem">' . $items . '</ul>'
            . '<p style="text-align:left"><strong>Crea lezioni</strong>: le lezioni vengono create lo stesso, '
            . 'saltando le settimane in cui docente o studente sono occupati (possono slittare anche di molti mesi).<br>'
            . '<strong>Modifica</strong>: non salva nulla e torna indietro per cambiare docente, giorno o orario.</p>';

        return new HtmlString($html);
    }

    /**
     * @return array{id:?int, student_id:?int, teacher_id:?int, weekly_day:int, start_min:int, end_min:int, time_label:string}|null
     */
    protected function normalizeSlot(array $slot): ?array
    {
        $day  = (int) ($slot['weekly_day'] ?? 0);
        $time = (string) ($slot['weekly_time'] ?? '');

        if ($day < 1 || $day > 7 || ! preg_match('/^(\d{1,2}):(\d{2})/', $time, $m)) {
            return null;
        }

        $start    = (int) $m[1] * 60 + (int) $m[2];
        $duration = (int) ($slot['duration_minutes'] ?? 60);
        if ($duration <= 0) {
            $duration = 60;
        }

        return [
            'id'         => isset($slot['id']) && $slot['id'] ? (int) $slot['id'] : null,
            'student_id' => ! empty($slot['student_id']) ? (int) $slot['student_id'] : null,
            'teacher_id' => ! empty($slot['teacher_id']) ? (int) $slot['teacher_id'] : null,
            'weekly_day' => $day,
            'start_min'  => $start,
            'end_min'    => $start + $duration,
            'time_label' => sprintf('%02d:%s', (int) $m[1], $m[2]),
        ];
    }
}
