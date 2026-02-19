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
                    ->getStateUsing(function (User $r): string {
                        $minutes = (int) ($r->worked_minutes_period ?? 0);
                        return number_format($minutes / 60, 2, ',', '.');
                    })
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
                ->selectRaw('COALESCE(SUM(duration_minutes),0)')
                ->whereColumn('teacher_id', 'users.id')
                ->whereNull('cancelled_at')
                ->where(function ($lq) {
                    $lq->where(function ($x) {
                        $x->whereNotNull('ends_at')->where('ends_at', '<', now());
                    })->orWhere(function ($x) {
                        $x->whereNull('ends_at')
                            ->whereRaw("DATE_ADD(starts_at, INTERVAL COALESCE(duration_minutes,0) MINUTE) < ?", [now()]);
                    });
                })
                ->when($fromDate, fn ($lq) => $lq->where('starts_at', '>=', $fromDate))
                ->when($toDate, fn ($lq) => $lq->where('starts_at', '<=', $toDate)),
            'worked_minutes_period'
        );

        $q->selectSub(
            Lesson::query()
                ->selectRaw('count(*)')
                ->whereColumn('teacher_id', 'users.id')
                ->whereNull('cancelled_at')
                ->when($fromDate, fn ($lq) => $lq->where('starts_at', '>=', $fromDate))
                ->when($toDate, fn ($lq) => $lq->where('starts_at', '<=', $toDate)),
            'scheduled_count_period'
        );

        $q->selectSub(
            Lesson::query()
                ->selectRaw('count(*)')
                ->whereColumn('teacher_id', 'users.id')
                ->when($fromDate, fn ($lq) => $lq->where('starts_at', '>=', $fromDate))
                ->when($toDate, fn ($lq) => $lq->where('starts_at', '<=', $toDate)),
            'total_count_period'
        );

        $q->selectSub(
            Lesson::query()
                ->selectRaw('count(*)')
                ->whereColumn('teacher_id', 'users.id')
                ->whereNull('cancelled_at')
                ->where('starts_at', '>=', now()->startOfDay()),
            'future_from_today'
        );

        $q->selectSub(
            Lesson::query()
                ->selectRaw('count(*)')
                ->whereColumn('teacher_id', 'users.id')
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
