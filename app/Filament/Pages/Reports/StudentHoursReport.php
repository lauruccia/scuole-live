<?php

namespace App\Filament\Pages\Reports;

use App\Models\Contract;
use App\Models\ContractLessonSlot;
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
use Illuminate\Support\Facades\DB;

class StudentHoursReport extends Page implements Tables\Contracts\HasTable, Forms\Contracts\HasForms
{
    use Tables\Concerns\InteractsWithTable;
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Report';
    protected static ?string $navigationLabel = 'Lezioni studenti';
    protected static ?string $slug = 'student-hours-report';
    protected static string $view = 'filament.pages.reports.student-hours-report';

    public array $data = [];

    public static function canAccess(): bool
    {
        $u = Filament::auth()->user();

        if (! $u) {
            return false;
        }

        if ($u->hasAnyRole(['super_admin', 'superadmin'])) {
            return true;
        }

        return $u->can('page_' . class_basename(static::class));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->form->fill([
            'from'          => now()->startOfMonth()->toDateString(),
            'to'            => now()->endOfMonth()->toDateString(),
            'student_id'    => null,
            'academic_year' => null,
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
                    ->columns(4)
                    ->schema([
                        Forms\Components\DatePicker::make('from')
                            ->label('Da')
                            ->live(),

                        Forms\Components\DatePicker::make('to')
                            ->label('A')
                            ->live(),

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

                                    return [
                                        $s->id => $label !== '' ? $label : ('Studente #' . $s->id),
                                    ];
                                })
                                ->toArray())
                            ->placeholder('Tutti')
                            ->live(),

                        Forms\Components\Select::make('academic_year')
                            ->label('Anno scolastico')
                            ->options(function () {
                                return \App\Models\Contract::query()
                                    ->whereNotNull('academic_year')
                                    ->where('academic_year', '!=', '')
                                    ->distinct()
                                    ->orderByDesc('academic_year')
                                    ->pluck('academic_year', 'academic_year')
                                    ->toArray();
                            })
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
                        $label = trim((string) ($record->student_name_calc ?? ''));

                        return $label !== ''
                            ? $label
                            : ('Studente #' . ($record->student_id ?? $record->id));
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->orderBy('student_last_sort', $direction)
                            ->orderBy('student_first_sort', $direction);
                    }),

                Tables\Columns\TextColumn::make('course_name_calc')
                    ->label('Corso')
                    ->getStateUsing(fn (ContractStudent $record) => trim((string) ($record->course_name_calc ?? ''))
                        ?: ($record->contract?->course?->name ?: ($record->contract_id ? 'Contratto #' . $record->contract_id : '—')))
                    ->sortable(),

                Tables\Columns\TextColumn::make('teacher_name_calc')
                    ->label('Docente')
                    ->getStateUsing(fn (ContractStudent $record) => trim((string) ($record->teacher_name_calc ?? '')) ?: '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('purchased_lessons_calc')
                    ->label('Acquistate')
                    ->alignRight()
                    ->getStateUsing(fn (ContractStudent $record) => (int) ($record->purchased_lessons_calc ?? 0))
                    ->sortable(),

                Tables\Columns\TextColumn::make('attended_period')
                    ->label('Fruite')
                    ->alignRight()
                    ->getStateUsing(fn (ContractStudent $record) => (int) ($record->attended_period ?? 0))
                    ->sortable(),

                Tables\Columns\TextColumn::make('scheduled_period')
                    ->label('Programmate')
                    ->alignRight()
                    ->getStateUsing(fn (ContractStudent $record) => (int) ($record->scheduled_period ?? 0))
                    ->sortable(),

                Tables\Columns\TextColumn::make('future_from_today')
                    ->label('Future (da oggi)')
                    ->alignRight()
                    ->getStateUsing(fn (ContractStudent $record) => (int) ($record->future_from_today ?? 0))
                    ->sortable(),

                Tables\Columns\TextColumn::make('to_recover')
                    ->label('Da recuperare')
                    ->alignRight()
                    ->getStateUsing(fn (ContractStudent $record) => (int) ($record->to_recover ?? 0))
                    ->sortable(),

                Tables\Columns\TextColumn::make('remaining_total')
                    ->label('Residue')
                    ->alignRight()
                    ->getStateUsing(function (ContractStudent $record): int {
                        $purchased = (int) ($record->purchased_lessons_calc ?? 0);
                        $consumed = (int) ($record->consumed_total ?? 0);

                        return max(0, $purchased - $consumed);
                    })
                    ->sortable(),
            ])
            ->defaultSort('student_last_sort', 'asc')
            ->paginated([25, 50, 100, 'all']);
    }

    protected function baseQuery(): Builder
    {
        $from         = $this->data['from'] ?? null;
        $to           = $this->data['to'] ?? null;
        $studentId    = $this->data['student_id'] ?? null;
        $academicYear = $this->data['academic_year'] ?? null;

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
        $toDate = $to ? Carbon::parse($to)->endOfDay() : null;

        $q = ContractStudent::query()
            ->leftJoin('students as s', 's.id', '=', 'contract_students.student_id')
            ->leftJoin('contracts as ctr', 'ctr.id', '=', 'contract_students.contract_id')
            ->leftJoin('companies as co', 'co.id', '=', 'ctr.company_id')

            // Esclude record completamente vuoti/orfani
            ->where(function ($qq) {
                $qq->whereNotNull('contract_students.student_id')
                    ->orWhereRaw("NULLIF(contract_students.beneficiary_first_name, '') IS NOT NULL")
                    ->orWhereRaw("NULLIF(contract_students.beneficiary_last_name, '') IS NOT NULL")
                    ->orWhereRaw("NULLIF(ctr.company_name, '') IS NOT NULL")
                    ->orWhereRaw("NULLIF(co.name, '') IS NOT NULL");
            })

            // Mostra solo chi ha lezioni O slot
            ->where(function ($qq) {
                $qq->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('lessons')
                        ->whereColumn('lessons.contract_student_id', 'contract_students.id');
                })
                ->orWhereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('contract_lesson_slots')
                        ->whereColumn('contract_lesson_slots.contract_id', 'contract_students.contract_id')
                        ->whereColumn('contract_lesson_slots.student_id', 'contract_students.student_id');
                });
            })

            ->select('contract_students.*')
            ->selectRaw("
                COALESCE(
                    NULLIF(ctr.company_name, ''),
                    NULLIF(co.name, ''),
                    NULLIF(
                        TRIM(CONCAT(
                            COALESCE(NULLIF(contract_students.beneficiary_first_name,''), NULLIF(s.first_name,''), ''),
                            ' ',
                            COALESCE(NULLIF(contract_students.beneficiary_last_name,''), NULLIF(s.last_name,''), '')
                        )),
                        ''
                    )
                ) AS student_name_calc,

                UPPER(
                    COALESCE(
                        NULLIF(ctr.company_name, ''),
                        NULLIF(co.name, ''),
                        NULLIF(contract_students.beneficiary_last_name,''),
                        NULLIF(s.last_name,''),
                        ''
                    )
                ) AS student_last_sort,

                UPPER(
                    CASE
                        WHEN COALESCE(NULLIF(ctr.company_name, ''), NULLIF(co.name, ''), '') <> '' THEN ''
                        ELSE COALESCE(
                            NULLIF(contract_students.beneficiary_first_name,''),
                            NULLIF(s.first_name,''),
                            ''
                        )
                    END
                ) AS student_first_sort
            ")
            ->with(['student', 'contract.course', 'contract.company'])
            ->when($studentId,    fn (Builder $qq) => $qq->where('contract_students.student_id', (int) $studentId))
            ->when($academicYear, fn (Builder $qq) => $qq->where('ctr.academic_year', $academicYear));

        // Docente da lezioni
        $q->selectSub(
            Lesson::query()
                ->join('users', 'users.id', '=', 'lessons.teacher_id')
                ->whereColumn('lessons.contract_student_id', 'contract_students.id')
                ->whereNotNull('lessons.teacher_id')
                ->selectRaw("
                    COALESCE(
                        NULLIF(users.name,''),
                        NULLIF(TRIM(CONCAT(COALESCE(users.first_name,''), ' ', COALESCE(users.last_name,''))), ''),
                        users.email,
                        CONCAT('Docente #', users.id)
                    )
                ")
                ->limit(1),
            'teacher_name_calc'
        );

        $q->selectSub(
            Contract::query()
                ->join('courses', 'courses.id', '=', 'contracts.course_id')
                ->whereColumn('contracts.id', 'contract_students.contract_id')
                ->selectRaw('courses.name')
                ->limit(1),
            'course_name_calc'
        );

        $q->selectSub(
            Contract::query()
                ->join('courses', 'courses.id', '=', 'contracts.course_id')
                ->whereColumn('contracts.id', 'contract_students.contract_id')
                ->selectRaw('COALESCE(contracts.hours_purchased, courses.hours_purchased, 0)')
                ->limit(1),
            'purchased_lessons_calc'
        );

        $q->selectSub(
            Lesson::query()
                ->selectRaw('COUNT(*)')
                ->whereColumn('lessons.contract_student_id', 'contract_students.id')
                ->where('lessons.counts_as_consumed', true),
            'consumed_total'
        );

        $q->selectSub(
            Lesson::query()
                ->selectRaw('COUNT(*)')
                ->whereColumn('lessons.contract_student_id', 'contract_students.id')
                ->where('lessons.counts_as_consumed', true)
                ->when($fromDate, fn ($lq) => $lq->where('lessons.starts_at', '>=', $fromDate))
                ->when($toDate, fn ($lq) => $lq->where('lessons.starts_at', '<=', $toDate)),
            'attended_period'
        );

        $q->selectSub(
            Lesson::query()
                ->selectRaw('COUNT(*)')
                ->whereColumn('lessons.contract_student_id', 'contract_students.id')
                ->whereNull('lessons.cancelled_at')
                ->when($fromDate, fn ($lq) => $lq->where('lessons.starts_at', '>=', $fromDate))
                ->when($toDate, fn ($lq) => $lq->where('lessons.starts_at', '<=', $toDate)),
            'scheduled_period'
        );

        $q->selectSub(
            Lesson::query()
                ->selectRaw('COUNT(*)')
                ->whereColumn('lessons.contract_student_id', 'contract_students.id')
                ->whereNull('lessons.cancelled_at')
                ->where('lessons.starts_at', '>=', now()->startOfDay()),
            'future_from_today'
        );

        $q->selectSub(
            Lesson::query()
                ->selectRaw('COUNT(*)')
                ->whereColumn('lessons.contract_student_id', 'contract_students.id')
                ->whereNotNull('lessons.cancelled_at')
                ->where('lessons.is_recoverable', true),
            'to_recover'
        );

        return $q;
    }

    public function updatedData(): void
    {
        $this->resetTable();
    }
}