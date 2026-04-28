<?php

namespace App\Filament\Studente\Widgets;

use App\Filament\Widgets\LessonCalendarWidget as BaseLessonCalendarWidget;

class StudentLessonCalendarWidget extends BaseLessonCalendarWidget
{
    protected static ?string $extraBodyAttributes = 'student-calendar-no-click';

    public ?int $student_id = null;
    public ?int $teacher_id = null;
    public ?int $course_id = null;

    public function mount(): void
    {
        $this->student_id = auth()->check()
            ? auth()->user()->students()->pluck('id')->map(fn ($id) => (int) $id)->first()
            : null;

        $this->teacher_id = null;
        $this->course_id = null;
    }

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }
}