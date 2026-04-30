<?php

namespace App\Filament\Pages\Reports;

use App\Models\Contract;
use App\Models\Lesson;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherPayReport extends Page implements Tables\Contracts\HasTable, Forms\Contracts\HasForms
{
    use Tables\Concerns\InteractsWithTable;
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-currency-euro';
    protected static ?string $navigationGroup = 'Report';
    protected static ?string $navigationLabel = 'Paghe docenti';
    protected static ?string $slug            = 'teacher-pay-report';
    protected static string  $view            = 'filament.pages.reports.teacher-pay-report';
    protected static ?int    $navigationSort  = 25;

    public array $data = [];

    /* ------------------------------------------------------------------ */

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

    /* ------------------------------------------------------------------ */

    public function getTableRecordKey($record): string
    {
        return (string) $record->id;
    }

    public function mount(): void
    {
        $this->form->fill([
            'from'          => now()->startOfMonth()->toDateString(),
            'to'            => now()->endOfMonth()->toDateString(),
            'teacher_id'    => null,
            'academic_year' => null,
        ]);
    }

    /* ------------------------------------------------------------------ */

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Section::make('Filtri')
                    ->columns(4)
                    ->schema([
                        Forms\Components\DatePicker::make('from')
                            ->label('Da')
                            ->native(false)
                            ->live(),

                        Forms\Components\DatePicker::make('to')
                            ->label('A')
                            ->native(false)
                            ->live(),

                        Forms\Components\Select::make('academic_year')
                            ->label('Anno didattico')
                            ->placeholder('Tutti gli anni')
                            ->options(fn () => Contract::query()
                                ->whereNotNull('academic_year')
                                ->distinct()
                                ->orderBy('academic_year')
                                ->pluck('academic_year', 'academic_year')
                                ->toArray()
                            )
                            ->nullable()
                            ->live(),

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
                                    ->orderBy('last_name')
                                    ->orderBy('first_name')
                                    ->get()
                                    ->mapWithKeys(function (User $u) {
                                        $label = trim(($u->last_name ?? '') . ' ' . ($u->first_name ?? ''));
                                        if ($label === '') {
                                            $label = trim((string) ($u->name ?? ''));
                                        }
                                        if ($label === '') {
                                            $label = $u->email ?: ('Docente #' . $u->id);
                                        }
                                        return [$u->id => $label];
                                    })
                                    ->toArray();
                            })
                            ->placeholder('Tutti i docenti')
                            ->live(),
                    ]),
            ]);
    }

    /* ------------------------------------------------------------------ */

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

                Tables\Columns\TextColumn::make('billing_mode_label')
                    ->label('Fatturazione')
                    ->getStateUsing(fn (User $r) => match ($r->teacher_billing_mode) {
                        'ritenuta_20' => "Ritenuta 20%",
                        'senza_iva'   => "Senza IVA",
                        'con_iva'     => "Con IVA",
                        'nessuna'     => "Nessuna",
                        default       => '—',
                    })
                    ->badge()
                    ->color(fn (User $r) => match ($r->teacher_billing_mode) {
                        'ritenuta_20' => 'warning',
                        'senza_iva'   => 'info',
                        'con_iva'     => 'success',
                        default       => 'gray',
                    }),

                Tables\Columns\TextColumn::make('worked_hours')
                    ->label('Ore lavorate')
                    ->alignRight()
                    ->getStateUsing(fn (User $r) => number_format((float) ($r->worked_hours ?? 0), 2, ',', ''))
                    ->suffix(' h')
                    ->sortable(),

                Tables\Columns\TextColumn::make('hourly_rate')
                    ->label('Tariffa €/h')
                    ->alignRight()
                    ->getStateUsing(fn (User $r) => $r->teacher_hourly_rate_gross
                        ? '€ ' . number_format((float) $r->teacher_hourly_rate_gross, 2, ',', '.')
                        : '—'
                    )
                    ->color(fn (User $r) => $r->teacher_hourly_rate_gross ? null : 'danger'),

                Tables\Columns\TextColumn::make('gross_pay')
                    ->label('Lordo (€)')
                    ->alignRight()
                    ->getStateUsing(function (User $r) {
                        $hours = (float) ($r->worked_hours ?? 0);
                        $rate  = (float) ($r->teacher_hourly_rate_gross ?? 0);
                        if ($rate <= 0) return '—';
                        return '€ ' . number_format($hours * $rate, 2, ',', '.');
                    })
                    ->color(fn (User $r) => ! $r->teacher_hourly_rate_gross ? 'danger' : null),

                Tables\Columns\TextColumn::make('withholding')
                    ->label('Ritenuta (€)')
                    ->alignRight()
                    ->getStateUsing(function (User $r) {
                        $hours = (float) ($r->worked_hours ?? 0);
                        $rate  = (float) ($r->teacher_hourly_rate_gross ?? 0);
                        if ($rate <= 0) return '—';
                        $gross = $hours * $rate;
                        if ($r->teacher_billing_mode === 'ritenuta_20') {
                            return '€ ' . number_format($gross * 0.20, 2, ',', '.');
                        }
                        return '—';
                    }),

                Tables\Columns\TextColumn::make('net_pay')
                    ->label('Netto (€)')
                    ->alignRight()
                    ->getStateUsing(function (User $r) {
                        $hours = (float) ($r->worked_hours ?? 0);
                        $rate  = (float) ($r->teacher_hourly_rate_gross ?? 0);
                        if ($rate <= 0) return '—';
                        $gross = $hours * $rate;
                        $net   = ($r->teacher_billing_mode === 'ritenuta_20')
                            ? $gross * 0.80
                            : $gross;
                        return '€ ' . number_format($net, 2, ',', '.');
                    })
                    ->weight(\Filament\Support\Enums\FontWeight::Bold),

                Tables\Columns\IconColumn::make('warnings')
                    ->label('⚠')
                    ->alignCenter()
                    ->getStateUsing(fn (User $r) => $this->hasWarnings($r))
                    ->icon(fn (User $r) => $this->hasWarnings($r) ? 'heroicon-o-exclamation-triangle' : null)
                    ->color('warning')
                    ->tooltip(fn (User $r) => implode(' | ', $this->getWarnings($r)) ?: null),
            ])
            ->defaultSort('teacher_label', 'asc')
            ->paginated([25, 50, 100])
            ->headerActions([
                Tables\Actions\Action::make('export_csv')
                    ->label('Esporta CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn () => $this->exportCsv()),

                Tables\Actions\Action::make('show_anomalies')
                    ->label('Mostra anomalie')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->action(function () {
                        $anomalies = $this->collectAnomalyMessages();
                        if ($anomalies->isEmpty()) {
                            Notification::make()
                                ->title('Nessuna anomalia')
                                ->body('Tutti i docenti nel periodo sono configurati correttamente.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('⚠️ Anomalie trovate (' . $anomalies->count() . ')')
                                ->body($anomalies->implode("\n"))
                                ->warning()
                                ->persistent()
                                ->send();
                        }
                    }),
            ]);
    }

    /* ------------------------------------------------------------------ */

    protected function baseQuery(): Builder
    {
        $from         = $this->data['from'] ?? null;
        $to           = $this->data['to'] ?? null;
        $teacherId    = $this->data['teacher_id'] ?? null;
        $academicYear = $this->data['academic_year'] ?? null;

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
        $toDate   = $to   ? Carbon::parse($to)->endOfDay()   : null;

        // Trova i teacher_id che hanno lezioni nel periodo (consumate)
        $teacherIds = Lesson::query()
            ->whereNotNull('teacher_id')
            ->whereNull('cancelled_at')
            ->where('counts_as_consumed', 1)
            ->whereNull('recovery_of_lesson_id')
            ->when($fromDate, fn ($q) => $q->where('starts_at', '>=', $fromDate))
            ->when($toDate,   fn ($q) => $q->where('starts_at', '<=', $toDate))
            ->when($academicYear, fn ($q) => $q->whereHas(
                'contract', fn ($cq) => $cq->where('academic_year', $academicYear)
            ))
            ->distinct()
            ->pluck('teacher_id');

        $q = User::query()
            ->whereIn('id', $teacherIds)
            ->when($teacherId, fn (Builder $qq) => $qq->where('id', (int) $teacherId));

        // Label docente
        $q->selectRaw("
            users.*,
            COALESCE(
                NULLIF(TRIM(CONCAT(COALESCE(users.last_name,''), ' ', COALESCE(users.first_name,''))), ''),
                NULLIF(TRIM(users.name), ''),
                CONCAT('Docente #', users.id)
            ) AS teacher_label
        ");

        // Ore lavorate nel periodo: SUM dei minuti / 60
        $lessonSub = Lesson::query()
            ->selectRaw("
                COALESCE(SUM(
                    CASE
                        WHEN duration_minutes > 0
                            THEN duration_minutes / 60.0
                        WHEN ends_at IS NOT NULL
                            THEN TIMESTAMPDIFF(MINUTE, starts_at, ends_at) / 60.0
                        ELSE 1.0
                    END
                ), 0)
            ")
            ->whereColumn('teacher_id', 'users.id')
            ->whereNull('cancelled_at')
            ->where('counts_as_consumed', 1)
            ->whereNull('recovery_of_lesson_id')
            ->when($fromDate, fn ($lq) => $lq->where('starts_at', '>=', $fromDate))
            ->when($toDate,   fn ($lq) => $lq->where('starts_at', '<=', $toDate))
            ->when($academicYear, fn ($lq) => $lq->whereHas(
                'contract', fn ($cq) => $cq->where('academic_year', $academicYear)
            ));

        $q->selectSub($lessonSub, 'worked_hours');

        return $q;
    }

    /* ------------------------------------------------------------------ */

    protected function hasWarnings(User $teacher): bool
    {
        return count($this->getWarnings($teacher)) > 0;
    }

    protected function getWarnings(User $teacher): array
    {
        $warnings = [];

        if (! $teacher->teacher_hourly_rate_gross || (float) $teacher->teacher_hourly_rate_gross <= 0) {
            $warnings[] = 'Tariffa oraria non impostata — impossibile calcolare la paga';
        }

        if (! $teacher->teacher_billing_mode) {
            $warnings[] = 'Modalità di fatturazione non configurata';
        }

        if (! $teacher->teacher_contract_type) {
            $warnings[] = 'Tipo di contratto non impostato';
        }

        $hours = (float) ($teacher->worked_hours ?? 0);
        if ($hours <= 0) {
            $warnings[] = 'Nessuna ora lavorata nel periodo selezionato';
        }

        // Controlla se ci sono lezioni senza duration_minutes E senza ends_at
        $from         = $this->data['from'] ?? null;
        $to           = $this->data['to'] ?? null;
        $academicYear = $this->data['academic_year'] ?? null;
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
        $toDate   = $to   ? Carbon::parse($to)->endOfDay()   : null;

        $missingDuration = Lesson::query()
            ->where('teacher_id', $teacher->id)
            ->whereNull('cancelled_at')
            ->where('counts_as_consumed', 1)
            ->whereNull('recovery_of_lesson_id')
            ->where(function ($q) {
                $q->whereNull('duration_minutes')
                  ->orWhere('duration_minutes', '<=', 0);
            })
            ->whereNull('ends_at')
            ->when($fromDate, fn ($q) => $q->where('starts_at', '>=', $fromDate))
            ->when($toDate,   fn ($q) => $q->where('starts_at', '<=', $toDate))
            ->when($academicYear, fn ($q) => $q->whereHas(
                'contract', fn ($cq) => $cq->where('academic_year', $academicYear)
            ))
            ->count();

        if ($missingDuration > 0) {
            $warnings[] = "{$missingDuration} lezioni senza durata esplicita (usato fallback 60 min)";
        }

        return $warnings;
    }

    /* ------------------------------------------------------------------ */

    protected function collectAnomalyMessages(): Collection
    {
        $from         = $this->data['from'] ?? null;
        $to           = $this->data['to'] ?? null;
        $teacherId    = $this->data['teacher_id'] ?? null;
        $academicYear = $this->data['academic_year'] ?? null;

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
        $toDate   = $to   ? Carbon::parse($to)->endOfDay()   : null;

        // Docenti con lezioni nel periodo ma tariffa mancante
        $teacherIds = Lesson::query()
            ->whereNotNull('teacher_id')
            ->whereNull('cancelled_at')
            ->where('counts_as_consumed', 1)
            ->whereNull('recovery_of_lesson_id')
            ->when($fromDate, fn ($q) => $q->where('starts_at', '>=', $fromDate))
            ->when($toDate,   fn ($q) => $q->where('starts_at', '<=', $toDate))
            ->when($academicYear, fn ($q) => $q->whereHas(
                'contract', fn ($cq) => $cq->where('academic_year', $academicYear)
            ))
            ->distinct()
            ->pluck('teacher_id');

        $teachers = User::query()
            ->whereIn('id', $teacherIds)
            ->when($teacherId, fn ($q) => $q->where('id', (int) $teacherId))
            ->get();

        $messages = collect();

        foreach ($teachers as $teacher) {
            $name = trim(($teacher->last_name ?? '') . ' ' . ($teacher->first_name ?? ''))
                ?: ($teacher->name ?? 'Docente #' . $teacher->id);

            foreach ($this->getWarnings($teacher) as $warning) {
                $messages->push("• {$name}: {$warning}");
            }
        }

        // Lezioni senza docente nel periodo
        $lessonsWithoutTeacher = Lesson::query()
            ->whereNull('teacher_id')
            ->whereNull('cancelled_at')
            ->where('counts_as_consumed', 1)
            ->when($fromDate, fn ($q) => $q->where('starts_at', '>=', $fromDate))
            ->when($toDate,   fn ($q) => $q->where('starts_at', '<=', $toDate))
            ->count();

        if ($lessonsWithoutTeacher > 0) {
            $messages->push("• {$lessonsWithoutTeacher} lezioni completate nel periodo NON hanno un docente assegnato");
        }

        return $messages;
    }

    /* ------------------------------------------------------------------ */

    public function updatedData(): void
    {
        $this->resetTable();
        $this->checkAndNotifyAnomalies();
    }

    protected function checkAndNotifyAnomalies(): void
    {
        // Avvisa se ci sono docenti senza tariffa oraria che hanno ore nel periodo
        $from      = $this->data['from'] ?? null;
        $to        = $this->data['to'] ?? null;
        $fromDate  = $from ? Carbon::parse($from)->startOfDay() : null;
        $toDate    = $to   ? Carbon::parse($to)->endOfDay()   : null;

        $missingRate = $this->baseQuery()
            ->where(function ($q) {
                $q->whereNull('teacher_hourly_rate_gross')
                  ->orWhere('teacher_hourly_rate_gross', '<=', 0);
            })
            ->count();

        if ($missingRate > 0) {
            Notification::make()
                ->title('⚠️ ' . $missingRate . ' ' . ($missingRate === 1 ? 'docente senza' : 'docenti senza') . ' tariffa oraria')
                ->body('Configura la tariffa in Risorse Umane → Docenti per calcolare le paghe correttamente.')
                ->warning()
                ->send();
        }

        $missingBilling = $this->baseQuery()
            ->whereNull('teacher_billing_mode')
            ->count();

        if ($missingBilling > 0) {
            Notification::make()
                ->title('⚠️ ' . $missingBilling . ' ' . ($missingBilling === 1 ? 'docente senza' : 'docenti senza') . ' modalità fatturazione')
                ->body('La modalità di fatturazione è necessaria per calcolare ritenuta e netto.')
                ->warning()
                ->send();
        }
    }

    /* ------------------------------------------------------------------ */

    public function exportCsv(): StreamedResponse
    {
        $from         = $this->data['from'] ?? null;
        $to           = $this->data['to'] ?? null;
        $academicYear = $this->data['academic_year'] ?? null;
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
        $toDate   = $to   ? Carbon::parse($to)->endOfDay()   : null;

        $records = $this->baseQuery()->orderByRaw('teacher_label ASC')->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="paghe_docenti_' . now()->format('Ymd_His') . '.csv"',
        ];

        $columns = [
            'Docente', 'Tipo contratto', 'Fatturazione',
            'Ore lavorate', 'Tariffa €/h', 'Lordo (€)', 'Ritenuta (€)', 'Netto (€)',
            'Periodo da', 'Periodo a', 'Anno didattico', 'Note anomalie',
        ];

        $callback = function () use ($records, $columns, $from, $to, $academicYear) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $columns, ';');

            foreach ($records as $r) {
                $hours = (float) ($r->worked_hours ?? 0);
                $rate  = (float) ($r->teacher_hourly_rate_gross ?? 0);
                $gross = $rate > 0 ? $hours * $rate : null;
                $withholding = ($gross !== null && $r->teacher_billing_mode === 'ritenuta_20')
                    ? $gross * 0.20 : null;
                $net = $gross !== null
                    ? ($withholding !== null ? $gross - $withholding : $gross)
                    : null;

                $contractTypeLabel = match ($r->teacher_contract_type) {
                    'dipendente'     => 'Dipendente',
                    'collaborazione' => 'Collaborazione',
                    'piva'           => 'Partita IVA',
                    'occasionale'    => 'Prestazione occasionale',
                    'altro'          => 'Altro',
                    default          => '',
                };

                $billingLabel = match ($r->teacher_billing_mode) {
                    'ritenuta_20' => "Ritenuta d'acconto 20%",
                    'senza_iva'   => "Fatturazione senza IVA",
                    'con_iva'     => "Fatturazione con IVA",
                    'nessuna'     => "Nessuna",
                    default       => '',
                };

                $warnings = $this->getWarnings($r);

                fputcsv($handle, [
                    $r->teacher_label ?? '',
                    $contractTypeLabel,
                    $billingLabel,
                    number_format($hours, 2, ',', ''),
                    $rate > 0 ? number_format($rate, 2, ',', '.') : '',
                    $gross !== null ? number_format($gross, 2, ',', '.') : '',
                    $withholding !== null ? number_format($withholding, 2, ',', '.') : '',
                    $net !== null ? number_format($net, 2, ',', '.') : '',
                    $from ?? '',
                    $to ?? '',
                    $academicYear ?? '',
                    implode(' | ', $warnings),
                ], ';');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
