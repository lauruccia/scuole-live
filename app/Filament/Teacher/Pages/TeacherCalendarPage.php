<?php

namespace App\Filament\Teacher\Pages;

use App\Models\Lesson;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class TeacherCalendarPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Didattica';
    protected static ?string $navigationLabel = 'Calendario lezioni';
    protected static ?string $title = 'Calendario lezioni';

    protected static string $view = 'filament.teacher.pages.teacher-calendar';

    public array $events = [];

    public function mount(): void
    {
        $teacherId = (int) auth()->id();

        // carico un range sensato (ad es. -30gg / +120gg)
        $from = Carbon::today()->subDays(30);
        $to   = Carbon::today()->addDays(120);

        $this->events = Lesson::query()
            ->where('teacher_id', $teacherId)
            ->whereBetween('starts_at', [$from->startOfDay(), $to->endOfDay()])
            ->orderBy('starts_at')
            ->get()
            ->map(function (Lesson $l) {
                $status = $l->cancelled_at ? 'Annullata' : ($l->counts_as_consumed ? 'Completata' : 'Programmata');

                return [
                    'title' => ($l->contractStudent?->beneficiary_full_name ?? $l->student?->full_name ?? 'Studente') . " • {$status}",
                    'start' => $l->starts_at?->toIso8601String(),
                    'end'   => $l->ends_at?->toIso8601String(),
                    'url'   => route('filament.docente.resources.lessons.edit', ['record' => $l->id]),
                ];
            })
            ->values()
            ->all();
    }
}
