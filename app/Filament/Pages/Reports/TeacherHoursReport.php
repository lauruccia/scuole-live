<?php

namespace App\Filament\Pages\Reports;

use App\Models\Lesson;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TeacherHoursReport extends Page implements Tables\Contracts\HasTable, Forms\Contracts\HasForms
{
    use Tables\Concerns\InteractsWithTable;
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Report';
    protected static ?string $navigationLabel = 'Report lezioni docenti';

    protected static ?string $slug = 'teacher-hours-report';
    protected static string $view = 'filament.pages.reports.teacher-hours-report';

    public static function canAccess(): bool
    {
        $u = Filament::auth()->user();
        if (! $u) return false;

        if ($u->hasAnyRole(['super_admin', 'superadmin'])) return true;

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
            'teacher_id' => null,
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

                        Forms\Components\Select::make('teacher_id')
                            ->label('Docente')
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                $ids = Lesson::query()
                                    ->whereNotNull('teacher_id')
                                    ->distinct()
                                    ->pluck('teacher_id');

                                return User::query()
                                    ->whereIn('id', $ids)
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function (User $u) {
                                        $label = trim((string) ($u->name ?? ''));
                                        if ($label === '') $label = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
                                        if ($label === '') $label = $u->email ?: ('Docente #' . $u->id);
                                        return [$u->id => $label];
                                    })
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
                Tables\Columns\TextColumn::make('teacher_label')
                    ->label('Docente')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhereRaw("concat(first_name,' ',last_name) like ?", ["%{$search}%"]);
                        });
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('worked_hours_period')
    ->label('Lavorate')
    ->alignRight()
    ->getStateUsing(fn (User $r) => (int) ($r->worked_hours_period ?? 0))
    ->suffix(' h')
    ->sortable(),

                Tables\Columns\TextColumn::make('scheduled_count_period')->label('Programmate')->alignRight()
                    ->getStateUsing(fn (User $r) => (int) ($r->scheduled_count_period ?? 0))->sortable(),

                Tables\Columns\TextColumn::make('total_count_period')->label('Totale')->alignRight()
                    ->getStateUsing(fn (User $r) => (int) ($r->total_count_period ?? 0))->sortable(),

                Tables\Columns\TextColumn::make('future_from_today')->label('Future (da oggi)')->alignRight()
                    ->getStateUsing(fn (User $r) => (int) ($r->future_from_today ?? 0))->sortable(),

                Tables\Columns\TextColumn::make('to_recover')->label('Da recuperare')->alignRight()
                    ->getStateUsing(fn (User $r) => (int) ($r->to_recover ?? 0))->sortable(),
            ])
            ->defaultSort('teacher_label', 'asc')
            ->paginated([25, 50, 100]);
    }

    protected function baseQuery(): Builder
    {
        $from = $this->data['from'] ?? null;
        $to   = $this->data['to'] ?? null;
        $teacherId = $this->data['teacher_id'] ?? null;

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
        $toDate   = $to ? Carbon::parse($to)->endOfDay() : null;

        $teacherIds = Lesson::query()
            ->whereNotNull('teacher_id')
            ->distinct()
            ->pluck('teacher_id');

        $q = User::query()
            ->whereIn('id', $teacherIds)
            ->when($teacherId, fn (Builder $qq) => $qq->where('id', (int) $teacherId));

        $q->selectRaw("
            users.*,
            COALESCE(
                NULLIF(TRIM(users.name), ''),
                NULLIF(TRIM(CONCAT(COALESCE(users.first_name,''), ' ', COALESCE(users.last_name,''))), ''),
                CONCAT('Docente #', users.id)
            ) AS teacher_label
        ");

$q->selectSub(
    Lesson::query()
        ->selectRaw("
            COALESCE(SUM(
                CASE
                    WHEN (
                        CASE
                            WHEN ends_at IS NOT NULL
                                THEN TIMESTAMPDIFF(MINUTE, starts_at, ends_at)
                            ELSE COALESCE(NULLIF(duration_minutes,0), 60)
                        END
                    ) >= 120 THEN 2
                    ELSE 1
                END
            ), 0)
        ")
        ->whereColumn('teacher_id', 'users.id')
        ->whereNull('cancelled_at')
        ->where('counts_as_consumed', 1)
        ->whereNull('recovery_of_lesson_id')
        ->when($fromDate, fn ($lq) => $lq->where('starts_at', '>=', $fromDate))
        ->when($toDate, fn ($lq) => $lq->where('starts_at', '<=', $toDate)),
    'worked_hours_period'
);

$q->selectSub(
    Lesson::query()
        ->selectRaw('COUNT(*)')
        ->whereColumn('teacher_id', 'users.id')
        ->whereNull('cancelled_at')
        // "non completate": counts_as_consumed = 0 OR NULL
        ->where(function ($lq) {
    $lq->where(function ($x) {
        $x->whereNull('recovery_of_lesson_id')
          ->where(function ($y) {
              $y->whereNull('counts_as_consumed')
                ->orWhere('counts_as_consumed', 0);
          });
    })->orWhereNotNull('recovery_of_lesson_id'); // include sempre recovery
})
        ->when($fromDate, fn ($lq) => $lq->where('starts_at', '>=', $fromDate))
        ->when($toDate, fn ($lq) => $lq->where('starts_at', '<=', $toDate)),
    'scheduled_count_period'
);

$q->selectSub(
    Lesson::query()
        ->selectRaw('COUNT(*)')
        ->whereColumn('teacher_id', 'users.id')
        ->where(function ($lq) {
            $lq->whereNull('cancelled_at')
               ->orWhere(function ($x) {
                   $x->whereNotNull('cancelled_at')
                     ->where('is_recoverable', 0); // annullate non recuperabili
               });
        })
        ->when($fromDate, fn ($lq) => $lq->where('starts_at', '>=', $fromDate))
        ->when($toDate, fn ($lq) => $lq->where('starts_at', '<=', $toDate)),
    'total_count_period'
);

$q->selectSub(
    Lesson::query()
        ->selectRaw('COUNT(*)')
        ->whereColumn('teacher_id', 'users.id')
        ->whereNull('cancelled_at')
        ->where('starts_at', '>=', now()->startOfDay())
        ->where(function ($lq) {
            $lq->where(function ($x) {
                $x->whereNull('recovery_of_lesson_id')
                  ->where(function ($y) {
                      $y->whereNull('counts_as_consumed')
                        ->orWhere('counts_as_consumed', 0);
                  });
            })->orWhereNotNull('recovery_of_lesson_id');
        })
        ->when($fromDate, fn ($lq) => $lq->where('starts_at', '>=', $fromDate))
        ->when($toDate, fn ($lq) => $lq->where('starts_at', '<=', $toDate)),
    'future_from_today'
);

$q->selectSub(
    Lesson::query()
        ->selectRaw('COUNT(*)')
        ->whereColumn('teacher_id', 'users.id')
        ->whereNull('cancelled_at')
        ->whereNotNull('recovery_of_lesson_id')
        ->when($fromDate, fn ($lq) => $lq->where('starts_at', '>=', $fromDate))
        ->when($toDate, fn ($lq) => $lq->where('starts_at', '<=', $toDate)),
    'to_recover'
);

        return $q;
    }

    public function updatedData(): void
    {
        $this->resetTable();
    }
}
