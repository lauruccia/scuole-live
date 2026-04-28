<?php

namespace App\Filament\Pages\Reports;

use App\Models\Contract;
use App\Models\Installment;
use App\Models\Student;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentsReport extends Page implements Tables\Contracts\HasTable, Forms\Contracts\HasForms
{
    use Tables\Concerns\InteractsWithTable;
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Report';
    protected static ?string $navigationLabel = 'Storico pagamenti';
    protected static ?string $slug            = 'payments-report';
    protected static string  $view            = 'filament.pages.reports.payments-report';
    protected static ?int    $navigationSort  = 20;

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

    public function mount(): void
    {
        $this->form->fill([
            'student_id'    => null,
            'academic_year' => null,
            'status'        => null,
            'from'          => null,
            'to'            => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Grid::make(5)->schema([
                    Forms\Components\Select::make('student_id')
                        ->label('Studente')
                        ->placeholder('Tutti gli studenti')
                        ->searchable()
                        ->preload()
                        ->options(fn () => Student::query()
                            ->orderBy('last_name')
                            ->orderBy('first_name')
                            ->get()
                            ->mapWithKeys(fn ($s) => [$s->id => trim("{$s->last_name} {$s->first_name}")])
                        )
                        ->nullable()
                        ->live(),

                    Forms\Components\Select::make('academic_year')
                        ->label('Anno didattico')
                        ->placeholder('Tutti gli anni')
                        ->options(fn () => $this->academicYearOptions())
                        ->nullable()
                        ->live(),

                    Forms\Components\Select::make('status')
                        ->label('Stato')
                        ->placeholder('Tutti')
                        ->options([
                            'paid'    => 'Pagate',
                            'unpaid'  => 'Da pagare',
                            'overdue' => 'Scadute (non pagate)',
                        ])
                        ->nullable()
                        ->live(),

                    Forms\Components\DatePicker::make('from')
                        ->label('Scadenza dal')
                        ->native(false)
                        ->nullable()
                        ->live(),

                    Forms\Components\DatePicker::make('to')
                        ->label('Scadenza al')
                        ->native(false)
                        ->nullable()
                        ->live(),
                ]),
            ]);
    }

    /* ------------------------------------------------------------------ */

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->buildQuery())
            ->columns([
                Tables\Columns\TextColumn::make('student_name')
                    ->label('Studente')
                    ->getStateUsing(fn (Installment $r) => $r->contract?->students
                        ->map(fn ($s) => trim("{$s->last_name} {$s->first_name}"))
                        ->implode(', ') ?: '—'
                    )
                    ->searchable(query: fn (Builder $q, string $search) => $q->whereHas(
                        'contract.students',
                        fn (Builder $sq) => $sq->where(function (Builder $qq) use ($search) {
                            $qq->where('first_name', 'like', "%{$search}%")
                               ->orWhere('last_name', 'like', "%{$search}%");
                        })
                    ))
                    ->wrap(),

                Tables\Columns\TextColumn::make('contract.academic_year')
                    ->label('Anno didattico')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('contract.course.name')
                    ->label('Corso')
                    ->wrap(),

                Tables\Columns\TextColumn::make('number')
                    ->label('N° rata')
                    ->formatStateUsing(fn ($state, Installment $r) => match (true) {
                        $r->number === -1   => 'Tassa iscr.',
                        $r->is_deposit      => 'Acconto',
                        default             => "Rata {$state}",
                    })
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Scadenza')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Importo')
                    ->money('EUR', locale: 'it_IT')
                    ->alignEnd()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Stato')
                    ->getStateUsing(fn (Installment $r) => match (true) {
                        $r->status === 'paid' || ! is_null($r->paid_at) => 'paid',
                        $r->due_date && Carbon::parse($r->due_date)->isPast()  => 'overdue',
                        default                                                => 'unpaid',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'paid'    => '✅ Pagata',
                        'overdue' => '⚠️ Scaduta',
                        default   => '🕐 Da pagare',
                    })
                    ->colors([
                        'success' => 'paid',
                        'danger'  => 'overdue',
                        'warning' => 'unpaid',
                    ]),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Pagata il')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ])
            ->defaultSort('due_date', 'asc')
            ->paginationPageOptions([25, 50, 100])
            ->headerActions([
                Tables\Actions\Action::make('export_csv')
                    ->label('Esporta CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn () => $this->exportCsv()),
            ]);
    }

    /* ------------------------------------------------------------------ */

    protected function buildQuery(): Builder
    {
        $data = $this->data;

        $query = Installment::withoutGlobalScopes()
            ->with(['contract.students', 'contract.course']);

        if (! empty($data['student_id'])) {
            $query->whereHas('contract.students', fn (Builder $q) => $q->where('students.id', $data['student_id']));
        }

        if (! empty($data['academic_year'])) {
            $query->whereHas('contract', fn (Builder $q) => $q->where('academic_year', $data['academic_year']));
        }

        if (! empty($data['from'])) {
            $query->whereDate('due_date', '>=', $data['from']);
        }

        if (! empty($data['to'])) {
            $query->whereDate('due_date', '<=', $data['to']);
        }

        if (! empty($data['status'])) {
            match ($data['status']) {
                'paid' => $query->where(function (Builder $q) {
                    $q->where('status', 'paid')->orWhereNotNull('paid_at');
                }),
                'unpaid' => $query->where(function (Builder $q) {
                    $q->where('status', '!=', 'paid')->whereNull('paid_at');
                }),
                'overdue' => $query->where(function (Builder $q) {
                    $q->where('status', '!=', 'paid')->whereNull('paid_at');
                })->whereDate('due_date', '<', now()->toDateString()),
                default => null,
            };
        }

        return $query;
    }

    /* ------------------------------------------------------------------ */

    public function exportCsv(): StreamedResponse
    {
        $records = $this->buildQuery()->orderBy('due_date')->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="pagamenti_' . now()->format('Ymd_His') . '.csv"',
        ];

        $columns = [
            'Studente', 'Anno didattico', 'Corso', 'N° rata',
            'Scadenza', 'Importo (€)', 'Stato', 'Pagata il',
        ];

        $callback = function () use ($records, $columns) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $columns, ';');

            foreach ($records as $r) {
                $studentName = $r->contract?->students
                    ->map(fn ($s) => trim("{$s->last_name} {$s->first_name}"))
                    ->implode(', ') ?: '—';

                $rataLabel = match (true) {
                    $r->number === -1 => 'Tassa iscrizione',
                    $r->is_deposit    => 'Acconto',
                    default           => 'Rata ' . $r->number,
                };

                $statusLabel = match (true) {
                    $r->status === 'paid' || ! is_null($r->paid_at)   => 'Pagata',
                    $r->due_date && Carbon::parse($r->due_date)->isPast() => 'Scaduta',
                    default                                                => 'Da pagare',
                };

                fputcsv($handle, [
                    $studentName,
                    $r->contract?->academic_year ?? '',
                    $r->contract?->course?->name ?? '',
                    $rataLabel,
                    $r->due_date?->format('d/m/Y') ?? '',
                    number_format((float) ($r->amount ?? 0), 2, ',', '.'),
                    $statusLabel,
                    $r->paid_at?->format('d/m/Y H:i') ?? '',
                ], ';');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /* ------------------------------------------------------------------ */

    public function updatedData(): void
    {
        $this->resetTable();
    }

    /* ------------------------------------------------------------------ */

    protected function academicYearOptions(): array
    {
        // Anni già presenti nei contratti + anni correnti generati
        $fromDb = Contract::query()
            ->whereNotNull('academic_year')
            ->distinct()
            ->orderBy('academic_year')
            ->pluck('academic_year')
            ->toArray();

        $thisYear = (int) now()->format('Y');
        $generated = [];
        for ($y = $thisYear - 1; $y <= $thisYear + 1; $y++) {
            $label = "{$y}/" . ($y + 1);
            $generated[$label] = $label;
        }

        foreach ($fromDb as $year) {
            $generated[$year] = $year;
        }

        ksort($generated);
        return $generated;
    }
}
