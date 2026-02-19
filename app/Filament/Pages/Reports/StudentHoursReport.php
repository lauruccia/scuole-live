<?php

namespace App\Filament\Pages\Reports;

use App\Models\ContractStudent;
use App\Models\Lesson;
use App\Models\Student;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class StudentHoursReport extends Page implements Tables\Contracts\HasTable, Forms\Contracts\HasForms
{
    use Tables\Concerns\InteractsWithTable;
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Report';
    protected static ?string $navigationLabel = 'Lezioni studenti';

    protected static ?string $slug = 'student-hours-report';
    protected static string $view = 'filament.pages.reports.student-hours-report';

    public static function canAccess(): bool
    {
        $u = Filament::auth()->user();
        if (! $u) {
            return false;
        }

        // ✅ supporta entrambi i nomi ruolo
        if ($u->hasAnyRole(['super_admin', 'superadmin'])) {
            return true;
        }

        // ✅ permesso Shield per la Page
        return $u->can('page_' . class_basename(static::class));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'from' => now()->startOfMonth()->toDateString(),
            'to'   => now()->endOfMonth()->toDateString(),
            'student_id' => null,
        ]);
    }

    public function getTableRecordKey($record): string
    {
        return (string) $record->id;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Filtri')
                    ->columns(3)
                    ->schema([
                        Forms\Components\DatePicker::make('from')->label('Da')->live(),
                        Forms\Components\DatePicker::make('to')->label('A')->live(),

                        Forms\Components\Select::make('student_id')
                            ->label('Studente (anagrafica)')
                            ->searchable()
                            ->preload()
                            ->options(fn () => Student::query()
                                ->orderBy('last_name')
                                ->orderBy('first_name')
                                ->get()
                                ->mapWithKeys(function (Student $s) {
                                    $label = $s->full_name ?? trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? ''));
                                    $label = trim((string) $label);
                                    return [$s->id => $label !== '' ? $label : ('Studente #' . $s->id)];
                                })
                                ->toArray()
                            )
                            ->placeholder('Tutti')
                            ->live(),
                    ]),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->baseQuery())
            ->columns([
                Tables\Columns\TextColumn::make('student_name_calc')
                    ->label('Studente')
                    ->getStateUsing(function (ContractStudent $record): string {
                        $benef = trim(($record->beneficiary_first_name ?? '') . ' ' . ($record->beneficiary_last_name ?? ''));
                        if ($benef !== '') return $benef;

                        $calc = trim((string) ($record->student_name_calc ?? ''));
                        if ($calc !== '') return $calc;

                        if ($record->student) {
                            $label = $record->student->full_name
                                ?? trim(($record->student->first_name ?? '') . ' ' . ($record->student->last_name ?? ''));
                            $label = trim((string) $label);
                            if ($label !== '') return $label;
                        }

                        return 'Studente #' . ($record->student_id ?? $record->id);
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('course_name_calc')
                    ->label('Corso')
                    ->getStateUsing(fn (ContractStudent $record) => trim((string) ($record->course_name_calc ?? ''))
                        ?: ($record->contract?->course?->name ?: ($record->contract_id ? 'Contratto #' . $record->contract_id : '—')))
                    ->sortable(),

                Tables\Columns\TextColumn::make('teacher_name_calc')
                    ->label('Docente')
                    ->getStateUsing(fn (ContractStudent $record) => trim((string) ($record->teacher_name_calc ?? '')) ?: '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('purchased_lessons_calc')->label('Acquistate')->alignRight()
                    ->getStateUsing(fn (ContractStudent $record) => (int) ($record->purchased_lessons_calc ?? 0))->sortable(),

                Tables\Columns\TextColumn::make('attended_period')->label('Fruite')->alignRight()
                    ->getStateUsing(fn (ContractStudent $record) => (int) ($record->attended_period ?? 0))->sortable(),

                Tables\Columns\TextColumn::make('scheduled_period')->label('Programmate')->alignRight()
                    ->getStateUsing(fn (ContractStudent $record) => (int) ($record->scheduled_period ?? 0))->sortable(),

                Tables\Columns\TextColumn::make('future_from_today')->label('Future (da oggi)')->alignRight()
                    ->getStateUsing(fn (ContractStudent $record) => (int) ($record->future_from_today ?? 0))->sortable(),

                Tables\Columns\TextColumn::make('to_recover')->label('Da recuperare')->alignRight()
                    ->getStateUsing(fn (ContractStudent $record) => (int) ($record->to_recover ?? 0))->sortable(),

                Tables\Columns\TextColumn::make('remaining_total')->label('Residue')->alignRight()
                    ->getStateUsing(function (ContractStudent $record): int {
                        $purchased = (int) ($record->purchased_lessons_calc ?? 0);
                        $consumed  = (int) ($record->consumed_total ?? 0);
                        return max(0, $purchased - $consumed);
                    })
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->paginated([25, 50, 100]);
    }

    protected function baseQuery(): Builder
    {
        $from = $this->data['from'] ?? null;
        $to   = $this->data['to'] ?? null;
        $studentId = $this->data['student_id'] ?? null;

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
        $toDate   = $to ? Carbon::parse($to)->endOfDay() : null;

        $q = ContractStudent::query()
            ->select('contract_students.*')
            ->with(['student', 'contract.course'])
            ->when($studentId, fn (Builder $qq) => $qq->where('student_id', (int) $studentId));

        $q->selectSub(
            Lesson::query()
                ->join('students', 'students.id', '=', 'lessons.student_id')
                ->whereColumn('lessons.contract_student_id', 'contract_students.id')
                ->whereNotNull('lessons.student_id')
                ->selectRaw("TRIM(CONCAT(COALESCE(students.first_name,''),' ',COALESCE(students.last_name,'')))")
                ->limit(1),
            'student_name_calc'
        );

        $q->selectSub(
            Lesson::query()
                ->join('users', 'users.id', '=', 'lessons.teacher_id')
                ->whereColumn('lessons.contract_student_id', 'contract_students.id')
                ->whereNotNull('lessons.teacher_id')
                ->selectRaw("COALESCE(NULLIF(users.name,''), NULLIF(TRIM(CONCAT(COALESCE(users.first_name,''),' ',COALESCE(users.last_name,''))),''), users.email, CONCAT('Docente #', users.id))")
                ->limit(1),
            'teacher_name_calc'
        );

        $q->selectSub(
            \App\Models\Contract::query()
                ->join('courses', 'courses.id', '=', 'contracts.course_id')
                ->whereColumn('contracts.id', 'contract_students.contract_id')
                ->selectRaw('courses.name')
                ->limit(1),
            'course_name_calc'
        );

        $q->selectSub(
            \App\Models\Contract::query()
                ->join('courses', 'courses.id', '=', 'contracts.course_id')
                ->whereColumn('contracts.id', 'contract_students.contract_id')
                ->selectRaw('COALESCE(courses.lessons_count,0)')
                ->limit(1),
            'purchased_lessons_calc'
        );

        $q->selectSub(
            Lesson::query()
                ->selectRaw('count(*)')
                ->whereColumn('contract_student_id', 'contract_students.id')
                ->where('counts_as_consumed', true),
            'consumed_total'
        );

        $q->selectSub(
            Lesson::query()
                ->selectRaw('count(*)')
                ->whereColumn('contract_student_id', 'contract_students.id')
                ->where('counts_as_consumed', true)
                ->when($fromDate, fn ($lq) => $lq->where('starts_at', '>=', $fromDate))
                ->when($toDate, fn ($lq) => $lq->where('starts_at', '<=', $toDate)),
            'attended_period'
        );

        $q->selectSub(
            Lesson::query()
                ->selectRaw('count(*)')
                ->whereColumn('contract_student_id', 'contract_students.id')
                ->whereNull('cancelled_at')
                ->when($fromDate, fn ($lq) => $lq->where('starts_at', '>=', $fromDate))
                ->when($toDate, fn ($lq) => $lq->where('starts_at', '<=', $toDate)),
            'scheduled_period'
        );

        $q->selectSub(
            Lesson::query()
                ->selectRaw('count(*)')
                ->whereColumn('contract_student_id', 'contract_students.id')
                ->whereNull('cancelled_at')
                ->where('starts_at', '>=', now()->startOfDay()),
            'future_from_today'
        );

        $q->selectSub(
            Lesson::query()
                ->selectRaw('count(*)')
                ->whereColumn('contract_student_id', 'contract_students.id')
                ->whereNotNull('cancelled_at')
                ->where('is_recoverable', true),
            'to_recover'
        );

        return $q;
    }

    public function updatedData(): void
    {
        $this->resetTable();
    }
}
