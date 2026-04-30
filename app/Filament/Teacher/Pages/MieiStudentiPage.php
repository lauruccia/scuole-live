<?php

namespace App\Filament\Teacher\Pages;

use App\Models\Homework;
use App\Models\Lesson;
use App\Models\Student;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class MieiStudentiPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Miei studenti';
    protected static ?string $navigationGroup = 'Didattica';
    protected static ?int    $navigationSort  = 1;
    protected static string  $view            = 'filament.teacher.pages.miei-studenti-page';
    protected static ?string $title           = 'Miei studenti';

    /** Tutti gli studenti caricati al mount */
    public array $students = [];

    /** Filtri Livewire (reattivi) */
    public string $search        = '';
    public string $filterStatus  = '';   // '' | active | completed | suspended
    public string $filterLang    = '';   // '' | Inglese | Francese | …
    public string $filterPending = '';   // '' | 1 (solo con esercitazioni da valutare)

    public function mount(): void
    {
        $teacherId = Auth::id();

        $students = Student::query()
            ->whereHas('lessons', fn ($q) => $q->where('lessons.teacher_id', $teacherId))
            ->with(['contracts' => fn ($q) => $q->latest('id')->limit(1)])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $this->students = $students->map(function (Student $s) use ($teacherId) {
            $contract = $s->contracts->first();

            $lessonsDone = Lesson::where('student_id', $s->id)
                ->where('teacher_id', $teacherId)
                ->whereNotNull('completed_at')
                ->whereNull('cancelled_at')
                ->count();

            $lessonsScheduled = Lesson::where('student_id', $s->id)
                ->where('teacher_id', $teacherId)
                ->whereNull('cancelled_at')
                ->whereNull('completed_at')
                ->where('starts_at', '>', now())
                ->count();

            $homeworkPending = Homework::where('contract_id', $contract?->id)
                ->where('teacher_id', $teacherId)
                ->whereHas('submissions', fn ($q) => $q->where('status', 'submitted'))
                ->count();

            $langs = is_array($contract?->languages) ? $contract->languages : [];

            return [
                'id'               => $s->id,
                'full_name'        => trim($s->last_name . ' ' . $s->first_name),
                'email'            => $s->email ?? '',
                'contract_id'      => $contract?->id,
                'languages'        => $langs,
                'contract_label'   => implode(', ', $langs) ?: '—',
                'status'           => $contract?->status ?? 'unknown',
                'lessons_done'     => $lessonsDone,
                'lessons_scheduled'=> $lessonsScheduled,
                'homework_pending' => $homeworkPending,
            ];
        })->toArray();
    }

    /** Studenti filtrati — calcolato al volo da Livewire */
    public function getFilteredStudentsProperty(): array
    {
        return array_values(array_filter($this->students, function (array $s) {
            // Ricerca testo
            if ($this->search !== '') {
                $q = mb_strtolower($this->search);
                if (
                    ! str_contains(mb_strtolower($s['full_name']), $q) &&
                    ! str_contains(mb_strtolower($s['email']), $q)
                ) {
                    return false;
                }
            }

            // Filtro stato contratto
            if ($this->filterStatus !== '' && $s['status'] !== $this->filterStatus) {
                return false;
            }

            // Filtro lingua
            if ($this->filterLang !== '' && ! in_array($this->filterLang, $s['languages'])) {
                return false;
            }

            // Solo con esercitazioni in attesa
            if ($this->filterPending === '1' && $s['homework_pending'] === 0) {
                return false;
            }

            return true;
        }));
    }

    /** Lingue disponibili per il filtro (da tutti gli studenti caricati) */
    public function getAvailableLanguagesProperty(): array
    {
        $langs = [];
        foreach ($this->students as $s) {
            foreach ($s['languages'] as $l) {
                $langs[$l] = $l;
            }
        }
        ksort($langs);
        return $langs;
    }
}
