<?php

namespace App\Filament\Studente\Widgets;

use App\Filament\Widgets\LessonCalendarWidget as BaseLessonCalendarWidget;
use App\Models\Contract;
use App\Models\Lesson;
use Illuminate\Support\Carbon;

class StudentLessonCalendarWidget extends BaseLessonCalendarWidget
{
    public ?int $student_id = null;
    public ?int $teacher_id = null;
    public ?int $course_id  = null;

    public function mount(): void
    {
        if (! auth()->check()) {
            $this->student_id = null;
            return;
        }

        // Prova prima la relazione diretta user → student (via user_id)
        $student = auth()->user()->students()->first();

        // Fallback: cerca per email
        if (! $student) {
            $student = \App\Models\Student::where('email', auth()->user()->email)->first();
        }

        $this->student_id = $student?->id;
        $this->teacher_id = null;
        $this->course_id  = null;
    }

    /**
     * Recupera gli ID dei contratti dello studente loggato.
     */
    private function getStudentContractIds(): array
    {
        if (! $this->student_id) {
            return [];
        }

        return Contract::query()
            ->whereHas('students', fn ($q) => $q->where('students.id', $this->student_id))
            ->pluck('id')
            ->toArray();
    }

    /**
     * Override del fetchEvents: filtra per student_id diretto
     * OPPURE per contract_id dei contratti dello studente.
     */
    public function fetchEvents(array $info): array
    {
        if (! $this->student_id) {
            return [];
        }

        $rangeStart  = Carbon::parse($info['start']);
        $rangeEnd    = Carbon::parse($info['end']);
        $contractIds = $this->getStudentContractIds();

        $lessons = Lesson::query()
            ->with(['teacher', 'contract.course'])
            ->whereBetween('starts_at', [$rangeStart, $rangeEnd])
            ->where(function ($q) use ($contractIds) {
                // Lezioni con student_id diretto
                $q->where('student_id', $this->student_id);

                // Oppure lezioni collegate a un contratto dello studente
                if (! empty($contractIds)) {
                    $q->orWhereIn('contract_id', $contractIds);
                }
            })
            ->get();

        return $lessons->map(function (Lesson $l) {
            $teacher = $l->teacher
                ? trim($l->teacher->name ?: (($l->teacher->first_name ?? '') . ' ' . ($l->teacher->last_name ?? '')))
                : '—';

            $course = $l->contract?->course?->name ?? null;

            $isCancelled = (bool) $l->cancelled_at;

            $end = $l->ends_at
                ?: ($l->starts_at
                    ? Carbon::parse($l->starts_at)->copy()->addMinutes((int) ($l->duration_minutes ?? 60))
                    : null
                );

            if ($isCancelled) {
                $statusLabel = $l->is_recoverable ? 'Annullata (da recuperare)' : 'Annullata';
                $statusKey   = $l->is_recoverable ? 'annullata-recover' : 'annullata';
            } else {
                if ($end && Carbon::parse($end)->isPast()) {
                    $statusLabel = 'Completata';
                    $statusKey   = 'completata';
                } else {
                    $statusLabel = 'Programmata';
                    $statusKey   = 'programmata';
                }
            }

            $start = $l->starts_at ? Carbon::parse($l->starts_at) : null;
            $end   = $end ? Carbon::parse($end) : null;

            // Titolo: docente · corso (lo studente è sempre l'utente loggato)
            $title = $teacher;
            if ($course) {
                $title .= ' · ' . $course;
            }

            return [
                'id'      => (string) $l->id,
                'title'   => $title,
                'start'   => $start?->toIso8601String(),
                'end'     => $end?->toIso8601String(),
                'allDay'  => false,
                'classNames' => ['lesson-event', 'lesson-' . $statusKey],
                'extendedProps' => [
                    'course' => $course,
                    'meet'   => $l->meet_url,
                    'status' => $statusLabel,
                ],
            ];
        })->values()->all();
    }

    /**
     * Gli studenti non possono cliccare sugli eventi per aprire la lezione admin.
     * Mostriamo solo il tooltip (già gestito dal JS del widget base).
     */
    public function onEventClick(array $event): void
    {
        // Nessuna azione: lo studente non ha accesso alla pagina di modifica lezione
    }

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }
}
