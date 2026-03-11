<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\LessonResource;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Support\RawJs;
use Illuminate\Support\Carbon;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class LessonCalendarWidget extends FullCalendarWidget
{
    public ?int $student_id = null;
    public ?int $teacher_id = null;
    public ?int $course_id = null;

    protected static function isTeacherPanel(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'Docente';
    }

    public function config(): array
    {
        return [
            'locale' => 'it',
            'firstDay' => 1,

            // ✅ ORA VISIBILE negli eventi
            'displayEventTime' => true,
            'eventTimeFormat' => [
                'hour' => '2-digit',
                'minute' => '2-digit',
                'hour12' => false,
            ],

            // (consigliato) più spazio e meno tagli nei box evento
            'dayMaxEvents' => true,
            'expandRows' => true,

            // ✅ click evento -> Livewire call
            'eventClick' => RawJs::make(<<<'JS'
                function(info) {
                    info.jsEvent.preventDefault();
                    info.jsEvent.stopPropagation();

                    const wireEl = info.el.closest('[wire\\:id]');
                    const wireId = wireEl ? wireEl.getAttribute('wire:id') : null;

                    if (wireId && window.Livewire) {
                        window.Livewire.find(wireId).call('onEventClick', {
                            event: { id: info.event.id }
                        });
                    }
                }
            JS),

            // ✅ tooltip
            'eventDidMount' => RawJs::make(<<<'JS'
function(info) {
    const p = info.event.extendedProps || {};

    const course = p.course ? `Corso: ${p.course}` : '';
    const meet   = p.meet ? `Meet: ${p.meet}` : '';

    const fmt = (d) => d
      ? d.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' })
      : '';

    const start = fmt(info.event.start);
    const end   = fmt(info.event.end);
    const time  = (start || end) ? `Orario: ${start}${end ? ' - ' + end : ''}` : '';

    const text = [course, time, meet].filter(Boolean).join('\\n');
    if (!text) return;

    let tip = null;

    const show = (e) => {
        if (tip) return;

        tip = document.createElement('div');
        tip.className = 'lesson-tooltip';
        tip.textContent = text;

        document.body.appendChild(tip);

        const pad = 10;
        const x = (e?.clientX ?? 0) + pad;
        const y = (e?.clientY ?? 0) + pad;

        tip.style.left = x + 'px';
        tip.style.top  = y + 'px';
    };

    const move = (e) => {
        if (!tip) return;
        const pad = 10;
        tip.style.left = (e.clientX + pad) + 'px';
        tip.style.top  = (e.clientY + pad) + 'px';
    };

    const hide = () => {
        if (!tip) return;
        tip.remove();
        tip = null;
    };

    info.el.addEventListener('mouseenter', show);
    info.el.addEventListener('mousemove', move);
    info.el.addEventListener('mouseleave', hide);
}
JS),
        ];
    }

    public function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Filtri')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('student_id')
                        ->label('Studente')
                        ->searchable()
                        ->preload()
                        ->options(fn () => Student::query()
                            ->orderBy('last_name')->orderBy('first_name')
                            ->get()
                            ->mapWithKeys(fn (Student $s) => [
                                $s->id => trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')) ?: ('Studente #' . $s->id),
                            ])->toArray()
                        )
                        ->live(),

                    // ✅ nel panel docente NON serve scegliere il docente
                    Forms\Components\Select::make('teacher_id')
                        ->label('Docente')
                        ->visible(fn () => ! static::isTeacherPanel())
                        ->searchable()
                        ->preload()
                        ->options(fn () => User::query()
                            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['docente', 'Docente']))
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (User $u) => [
                                $u->id => trim($u->name ?: (($u->first_name ?? '') . ' ' . ($u->last_name ?? '')))
                                    ?: ($u->email ?: ('Docente #' . $u->id)),
                            ])->toArray()
                        )
                        ->live(),

                    Forms\Components\Select::make('course_id')
                        ->label('Corso')
                        ->searchable()
                        ->preload()
                        ->options(fn () => Course::query()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray()
                        )
                        ->live(),
                ]),
        ];
    }

    public function updatedStudentId(): void { $this->refreshEvents(); }
    public function updatedTeacherId(): void { $this->refreshEvents(); }
    public function updatedCourseId(): void { $this->refreshEvents(); }

    public function fetchEvents(array $info): array
    {
        $rangeStart = Carbon::parse($info['start']);
        $rangeEnd   = Carbon::parse($info['end']);

        $query = Lesson::query()
            ->with(['student', 'teacher', 'contract.course'])
            ->whereBetween('starts_at', [$rangeStart, $rangeEnd]);

        // ✅ Panel docente: vede SOLO le sue lezioni
        if (static::isTeacherPanel()) {
            $query->where('teacher_id', auth()->id());
        } else {
            // ✅ Panel admin: filtri liberi
            $query->when($this->teacher_id, fn ($q) => $q->where('teacher_id', $this->teacher_id));
        }

        $lessons = $query
            ->when($this->student_id, fn ($q) => $q->where('student_id', $this->student_id))
            ->when($this->course_id, fn ($q) => $q->whereHas('contract', fn ($c) => $c->where('course_id', $this->course_id)))
            ->get();

        return $lessons->map(function (Lesson $l) {
            $student = $l->student
                ? trim(($l->student->first_name ?? '') . ' ' . ($l->student->last_name ?? ''))
                : 'Studente';

            $teacher = $l->teacher
                ? (trim($l->teacher->name ?: (($l->teacher->first_name ?? '') . ' ' . ($l->teacher->last_name ?? ''))) ?: 'Docente')
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

            // ✅ titolo: nel panel docente puoi anche togliere il nome docente (è sempre lui)
            $title = static::isTeacherPanel()
                ? $student
                : "{$student} • {$teacher}";

            return [
                'id'    => (string) $l->id,
                'title' => $title,
                'start' => $start?->toIso8601String(),
                'end'   => $end?->toIso8601String(),
                'allDay' => false,
                'classNames' => ['lesson-event', 'lesson-' . $statusKey],
                'extendedProps' => [
                    'course' => $course,
                    'meet'   => $l->meet_url,
                    'status' => $statusLabel,
                ],
            ];
        })->values()->all();
    }

    public function onEventClick(array $event): void
    {
        $id = $event['event']['id'] ?? $event['id'] ?? null;
        if (! $id) return;

        // ✅ nel panel docente: usa VIEW (evita 404 su /edit se non esiste la rotta)
        $page = static::isTeacherPanel() ? 'view' : 'edit';

        $this->redirect(
            LessonResource::getUrl($page, ['record' => $id]),
            navigate: true
        );
    }
}
