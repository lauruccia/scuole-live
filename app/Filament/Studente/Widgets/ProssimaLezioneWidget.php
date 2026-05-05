<?php

namespace App\Filament\Studente\Widgets;

use App\Models\Lesson;
use App\Models\Student;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

/**
 * Mostra la prossima lezione programmata dello studente loggato.
 * Appare nella dashboard del pannello studente.
 */
class ProssimaLezioneWidget extends Widget
{
    protected static string $view = 'filament.studente.widgets.prossima-lezione';

    // Mostrata prima del calendario
    protected static ?int $sort = 1;

    // Aggiorna ogni 5 minuti (la data/ora cambia nel corso della giornata)
    protected static ?string $pollingInterval = '300s';

    public function getLesson(): ?Lesson
    {
        $student = $this->resolveStudent();

        if (! $student) {
            return null;
        }

        return Lesson::query()
            ->where('student_id', $student->id)
            ->whereNull('cancelled_at')
            ->whereNull('deleted_at')
            ->where('starts_at', '>', now())
            ->with(['teacher', 'contract'])
            ->orderBy('starts_at')
            ->first();
    }

    private function resolveStudent(): ?Student
    {
        if (! auth()->check()) {
            return null;
        }

        $student = auth()->user()->students()->first();

        if (! $student) {
            $student = Student::where('email', auth()->user()->email)->first();
        }

        return $student;
    }

    public function getViewData(): array
    {
        $lesson = $this->getLesson();

        if (! $lesson) {
            return ['lesson' => null];
        }

        $startsAt    = Carbon::parse($lesson->starts_at);
        $endsAt      = Carbon::parse($lesson->ends_at);
        $isToday     = $startsAt->isToday();
        $isTomorrow  = $startsAt->isTomorrow();
        $isThisWeek  = $startsAt->isCurrentWeek();

        $dayLabel = match (true) {
            $isToday    => 'Oggi',
            $isTomorrow => 'Domani',
            default     => ucfirst($startsAt->translatedFormat('l d F Y')),
        };

        return [
            'lesson'       => $lesson,
            'starts_at'    => $startsAt,
            'ends_at'      => $endsAt,
            'day_label'    => $dayLabel,
            'time_range'   => $startsAt->format('H:i') . ' – ' . $endsAt->format('H:i'),
            'language'     => $lesson->language_id ?? ($lesson->contract?->language_id ?? null),
            'teacher_name' => $lesson->teacher
                ? trim(($lesson->teacher->name ?? '') . ' ' . ($lesson->teacher->surname ?? ''))
                : null,
            'meet_url'     => $lesson->meet_url ?? null,
            'is_today'     => $isToday,
            'is_tomorrow'  => $isTomorrow,
            'diff_human'   => $startsAt->diffForHumans(),
        ];
    }
}
