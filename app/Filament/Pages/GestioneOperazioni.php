<?php

namespace App\Filament\Pages;

use App\Models\Contract;
use App\Models\Lesson;
use App\Models\Student;
use App\Services\LessonGeneratorService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class GestioneOperazioni extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationGroup = 'Impostazioni';
    protected static ?string $navigationLabel = 'Gestione Operazioni';
    protected static ?string $title           = 'Gestione Operazioni';
    protected static string  $view            = 'filament.pages.gestione-operazioni';
    protected static ?int    $navigationSort  = 5;

    protected static function allowedRoles(): array
    {
        // Segreteria esclusa. Amministrazione e admin possono accedere.
        // Comandi (SuperadminCommands) è separato e rimane solo superadmin.
        return ['superadmin', 'super_admin', 'admin', 'Amministrazione'];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $u = Filament::auth()->user();
        return $u && $u->hasAnyRole(static::allowedRoles());
    }

    public static function canAccess(): bool
    {
        $u = Filament::auth()->user();
        return $u && $u->hasAnyRole(static::allowedRoles());
    }

    /* ------------------------------------------------------------------
     *  Helpers
     * ------------------------------------------------------------------ */

    private function contractSelect(): Select
    {
        return Select::make('contract_id')
            ->label('Contratto')
            ->searchable()
            ->required()
            ->live()
            ->preload(false)
            ->getSearchResultsUsing(function (string $search): array {
                return Contract::query()
                    ->where(function ($q) use ($search) {
                        $q->where('billing_first_name', 'like', "%{$search}%")
                            ->orWhere('billing_last_name', 'like', "%{$search}%")
                            ->orWhere('billing_email', 'like', "%{$search}%")
                            ->orWhere('id', 'like', "%{$search}%");
                    })
                    ->orderByDesc('id')
                    ->limit(30)
                    ->get()
                    ->mapWithKeys(function (Contract $c) {
                        $name = trim(($c->billing_last_name ?? '') . ' ' . ($c->billing_first_name ?? ''))
                            ?: ($c->company_name ?? "Contratto #{$c->id}");
                        $course = $c->course?->name ?? 'N/D';
                        return [$c->id => "#{$c->id} — {$name} — {$course}"];
                    })
                    ->toArray();
            })
            ->getOptionLabelUsing(function ($value): ?string {
                $c = Contract::find($value);
                if (! $c) return null;
                $name = trim(($c->billing_last_name ?? '') . ' ' . ($c->billing_first_name ?? ''))
                    ?: ($c->company_name ?? "Contratto #{$c->id}");
                return "#{$c->id} — {$name}";
            });
    }

    private function studentSelect(): Select
    {
        return Select::make('student_id')
            ->label('Studente')
            ->searchable()
            ->required()
            ->live()
            ->preload(false)
            ->getSearchResultsUsing(function (string $search): array {
                return Student::query()
                    ->where(function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orderBy('last_name')
                    ->limit(30)
                    ->get()
                    ->mapWithKeys(fn (Student $s) => [
                        $s->id => trim(($s->last_name ?? '') . ' ' . ($s->first_name ?? ''))
                            . ($s->email ? " — {$s->email}" : ''),
                    ])
                    ->toArray();
            })
            ->getOptionLabelUsing(fn ($value): ?string => Student::find($value)?->full_name);
    }

    private function lessonListPreview(\Illuminate\Support\Collection $lessons): HtmlString
    {
        if ($lessons->isEmpty()) {
            return new HtmlString('<span class="text-gray-500 italic text-xs">Nessuna lezione trovata.</span>');
        }

        $total = $lessons->count();
        $html  = "<p class=\"text-xs font-semibold text-gray-700 mb-2\">Totale: <strong>{$total}</strong> lezione/i</p>";
        $html .= '<ul class="space-y-1">';

        foreach ($lessons as $lesson) {
            $date     = $lesson->starts_at
                ? Carbon::parse($lesson->starts_at)->format('d/m/Y H:i')
                : 'N/D';
            $duration = ($lesson->duration_minutes ?? 60) . ' min';
            $teacher  = $lesson->teacher
                ? trim(($lesson->teacher->first_name ?? '') . ' ' . ($lesson->teacher->last_name ?? ''))
                : 'N/D';
            $course = $lesson->contract?->course?->name ?? 'N/D';

            $html .= '<li class="text-xs py-0.5 border-b border-gray-100 flex flex-wrap gap-x-2">'
                . "<span class=\"font-medium text-gray-800\">{$date}</span>"
                . "<span class=\"text-gray-500\">{$duration}</span>"
                . "<span class=\"text-gray-600\">{$course}</span>"
                . "<span class=\"text-gray-400\">Docente: {$teacher}</span>"
                . '</li>';
        }

        $html .= '</ul>';
        return new HtmlString($html);
    }

    /* ------------------------------------------------------------------
     *  Catalogo sezioni (usato dalla view Blade)
     * ------------------------------------------------------------------ */

    public function getOperationsCatalog(): array
    {
        return [
            [
                'section'     => 'Operazioni su Contratto',
                'icon'        => 'icon',
                'description' => 'Operazioni che agiscono sulle lezioni e sui dati di un singolo contratto.',
                'tone'        => 'primary',
                'operations'  => [
                    [
                        'key'         => 'correggi_ore_contratto',
                        'title'       => 'Correzione emergenza ore (residue o in eccesso)',
                        'description' => 'Diagnosi immediata: mostra ore acquistate, fruite, lezioni future programmate e anomalie. Permette di rigenera ore mancanti, eliminare/annullare lezioni in eccesso o correggere le ore acquistate. Solo Amministrazione.',
                        'tone'        => 'warning',
                    ],
                    [
                        'key'         => 'rigenera_lezioni_contratto',
                        'title'       => 'Rigenera lezioni contratto',
                        'description' => 'Cancella le lezioni future non svolte e le rigenera dagli slot configurati.',
                        'tone'        => 'warning',
                    ],
                    [
                        'key'         => 'elimina_lezioni_future_contratto',
                        'title'       => 'Elimina lezioni future (non svolte)',
                        'description' => 'Rimuove dal DB tutte le lezioni future non consumate di un contratto. Irreversibile.',
                        'tone'        => 'danger',
                    ],
                    [
                        'key'         => 'annulla_definitivo_contratto',
                        'title'       => 'Annulla definitivamente lezioni future',
                        'description' => 'Annulla tutte le lezioni future non svolte senza recupero e senza scalare ore.',
                        'tone'        => 'danger',
                    ],
                    [
                        'key'         => 'riattiva_lezioni_contratto',
                        'title'       => 'Riattiva lezioni annullate',
                        'description' => 'Rimuove l\'annullamento dalle lezioni future di un contratto.',
                        'tone'        => 'success',
                    ],
                    [
                        'key'         => 'ricalcola_ore_contratto',
                        'title'       => 'Ricalcola ore consumate',
                        'description' => 'Ricalcola il campo "Ore fruite" contando le lezioni con counts_as_consumed=1. Sicuro.',
                        'tone'        => 'success',
                    ],
                ],
            ],
            [
                'section'     => 'Operazioni su Studente',
                'icon'        => 'icon',
                'description' => 'Operazioni che agiscono sulle lezioni di un singolo studente su tutti i contratti.',
                'tone'        => 'info',
                'operations'  => [
                    [
                        'key'         => 'elimina_lezioni_future_studente',
                        'title'       => 'Elimina lezioni future (non svolte)',
                        'description' => 'Rimuove dal DB tutte le lezioni future non consumate dello studente su tutti i contratti.',
                        'tone'        => 'danger',
                    ],
                    [
                        'key'         => 'annulla_definitivo_studente',
                        'title'       => 'Annulla definitivamente lezioni future',
                        'description' => 'Annulla tutte le lezioni future non svolte dello studente senza recupero.',
                        'tone'        => 'danger',
                    ],
                    [
                        'key'         => 'riattiva_lezioni_studente',
                        'title'       => 'Riattiva lezioni annullate',
                        'description' => 'Rimuove l\'annullamento dalle lezioni future dello studente su tutti i contratti.',
                        'tone'        => 'success',
                    ],
                ],
            ],
            [
                'section'     => 'Operazioni di Sistema',
                'icon'        => 'icon',
                'description' => 'Operazioni globali di manutenzione e bonifica dati.',
                'tone'        => 'gray',
                'operations'  => [
                    [
                        'key'         => 'bonifica_consumi',
                        'title'       => 'Bonifica ore fruite',
                        'description' => 'Riallinea i flag delle lezioni e ricalcola hours_consumed su tutti i contratti.',
                        'tone'        => 'success',
                    ],
                    [
                        'key'         => 'fix_lezioni_future',
                        'title'       => 'Fix lezioni future errate',
                        'description' => 'Corregge lezioni future non annullate erroneamente marcate come consumate.',
                        'tone'        => 'warning',
                    ],
                ],
            ],
        ];
    }

    public function toneClasses(string $tone): array
    {
        return match ($tone) {
            'success' => [
                'card'  => 'border-success-200 bg-success-50/30',
                'badge' => 'bg-success-100 text-success-800',
                'btn'   => 'success',
            ],
            'warning' => [
                'card'  => 'border-warning-200 bg-warning-50/30',
                'badge' => 'bg-warning-100 text-warning-800',
                'btn'   => 'warning',
            ],
            'danger' => [
                'card'  => 'border-danger-200 bg-danger-50/30',
                'badge' => 'bg-danger-100 text-danger-800',
                'btn'   => 'danger',
            ],
            'info' => [
                'card'  => 'border-info-200 bg-info-50/30',
                'badge' => 'bg-info-100 text-info-800',
                'btn'   => 'info',
            ],
            default => [
                'card'  => 'border-gray-200 bg-gray-50/30',
                'badge' => 'bg-gray-100 text-gray-800',
                'btn'   => 'gray',
            ],
        };
    }

    /* ------------------------------------------------------------------
     *  Header Actions
     * ------------------------------------------------------------------ */

    protected function getHeaderActions(): array
    {
        return [

            /* ============================================================
             *  CONTRATTO — Correzione emergenza ore
             * ============================================================ */
            Action::make('correggi_ore_contratto')
                ->label('Correggi ore contratto (emergenza)')
                ->icon('heroicon-o-adjustments-horizontal')
                ->color('warning')
                ->modalHeading('Correzione ore contratto — Solo Amministrazione')
                ->modalDescription('Usa questa procedura per correggere anomalie nelle ore: residue non generate o lezioni in eccesso.')
                ->form([
                    $this->contractSelect(),

                    Section::make('Situazione attuale')
                        ->schema([
                            Placeholder::make('riepilogo_ore')
                                ->label('')
                                ->live()
                                ->content(function (Get $get): HtmlString {
                                    $id = $get('contract_id');
                                    if (! $id) {
                                        return new HtmlString('<span style="color:#6b7280;">Seleziona un contratto per vedere la situazione.</span>');
                                    }

                                    $contract = Contract::with(['course', 'beneficiaries'])->find((int) $id);
                                    if (! $contract) {
                                        return new HtmlString('<span style="color:#dc2626;">Contratto non trovato.</span>');
                                    }

                                    $purchased = (float) ($contract->hours_purchased ?? 0);
                                    $consumed  = (float) ($contract->hours_consumed ?? 0);
                                    $residue   = round($purchased - $consumed, 2);

                                    $futureLessons = Lesson::query()
                                        ->where('contract_id', $contract->id)
                                        ->whereNull('cancelled_at')
                                        ->where('starts_at', '>=', now())
                                        ->get();

                                    $futureHours = round($futureLessons->sum(fn ($l) => ($l->duration_minutes ?? 60) / 60), 2);
                                    $futureCount = $futureLessons->count();

                                    $pastHours = round(Lesson::query()
                                        ->where('contract_id', $contract->id)
                                        ->where('counts_as_consumed', 1)
                                        ->sum(DB::raw('duration_minutes / 60')), 2);

                                    $totalScheduled = round($pastHours + $futureHours, 2);
                                    $diff = round($totalScheduled - $purchased, 2);

                                    if ($diff > 0.1) {
                                        $anomalia = "<div style='margin-top:10px;padding:8px 12px;background:#fef2f2;border-left:4px solid #dc2626;border-radius:4px;'>"
                                            . "<strong>Lezioni in ECCESSO di {$diff} h</strong> rispetto al contratto.<br>"
                                            . "<span style='font-size:12px;'>Usa: annulla o elimina le lezioni future in eccesso.</span></div>";
                                    } elseif ($diff < -0.1) {
                                        $mancanti = abs($diff);
                                        $anomalia = "<div style='margin-top:10px;padding:8px 12px;background:#fefce8;border-left:4px solid #ca8a04;border-radius:4px;'>"
                                            . "<strong>Mancano {$mancanti} h di lezioni</strong> rispetto al contratto.<br>"
                                            . "<span style='font-size:12px;'>Usa: rigenera lezioni mancanti oppure correggi le ore acquistate.</span></div>";
                                    } else {
                                        $anomalia = "<div style='margin-top:10px;padding:8px 12px;background:#f0fdf4;border-left:4px solid #16a34a;border-radius:4px;'>"
                                            . "<strong>Ore bilanciate correttamente.</strong></div>";
                                    }

                                    $residueColor = $residue < 0 ? '#dc2626' : '#16a34a';
                                    $html = "<table style='width:100%;font-size:13px;border-collapse:collapse;'>"
                                        . "<tr><td style='padding:4px 8px;color:#374151;'>Ore acquistate (contratto)</td><td style='padding:4px 8px;font-weight:700;'>{$purchased} h</td></tr>"
                                        . "<tr style='background:#f9fafb;'><td style='padding:4px 8px;color:#374151;'>Ore fruite (passate)</td><td style='padding:4px 8px;font-weight:700;color:#1d4ed8;'>{$pastHours} h</td></tr>"
                                        . "<tr><td style='padding:4px 8px;color:#374151;'>Lezioni future programmate</td><td style='padding:4px 8px;font-weight:700;color:#7c3aed;'>{$futureHours} h ({$futureCount} lezioni)</td></tr>"
                                        . "<tr style='background:#f9fafb;'><td style='padding:4px 8px;color:#374151;'>Totale programmato</td><td style='padding:4px 8px;font-weight:700;'>{$totalScheduled} h</td></tr>"
                                        . "<tr><td style='padding:4px 8px;color:#374151;'>Ore residue (acquistate - fruite)</td><td style='padding:4px 8px;font-weight:700;color:{$residueColor};'>{$residue} h</td></tr>"
                                        . "</table>{$anomalia}";

                                    return new HtmlString($html);
                                }),
                        ]),

                    Section::make('Azione correttiva')
                        ->schema([
                            Select::make('azione')
                                ->label('Cosa vuoi fare?')
                                ->required()
                                ->live()
                                ->options([
                                    'rigenera'        => 'Rigenera lezioni mancanti (ore residue non generate)',
                                    'elimina_eccesso' => 'Elimina lezioni future in eccesso (IRREVERSIBILE)',
                                    'annulla_eccesso' => 'Annulla lezioni future in eccesso (tracciabile)',
                                    'ricalcola'       => 'Ricalcola ore fruite (solo aggiorna contatore)',
                                    'correggi_ore'    => 'Correggi manualmente le ore acquistate',
                                ]),

                            Placeholder::make('spiegazione_azione')
                                ->label('')
                                ->live()
                                ->content(function (Get $get): HtmlString {
                                    return match ($get('azione')) {
                                        'rigenera'        => new HtmlString("<div style='padding:8px 12px;background:#f0fdf4;border-radius:4px;font-size:12px;'>Genera le lezioni mancanti dagli slot attivi senza toccare quelle esistenti.</div>"),
                                        'elimina_eccesso' => new HtmlString("<div style='padding:8px 12px;background:#fef2f2;border-radius:4px;font-size:12px;'>IRREVERSIBILE. Elimina definitivamente le lezioni future generate in eccesso, mantenendo quelle che rientrano nelle ore residue.</div>"),
                                        'annulla_eccesso' => new HtmlString("<div style='padding:8px 12px;background:#fefce8;border-radius:4px;font-size:12px;'>Annulla le lezioni in eccesso senza eliminarle. Rimangono nel DB come annullate (tracciabile). Consigliato rispetto all\'eliminazione.</div>"),
                                        'ricalcola'       => new HtmlString("<div style='padding:8px 12px;background:#eff6ff;border-radius:4px;font-size:12px;'>Operazione sicura: aggiorna solo il contatore Ore Fruite. Nessuna modifica alle lezioni.</div>"),
                                        'correggi_ore'    => new HtmlString("<div style='padding:8px 12px;background:#fefce8;border-radius:4px;font-size:12px;'>Modifica il numero di ore acquistate nel contratto. Usa se le ore erano sbagliate fin dall\'inizio.</div>"),
                                        default           => new HtmlString("<span style='color:#6b7280;'>Seleziona un\'azione per vedere la spiegazione.</span>"),
                                    };
                                }),

                            TextInput::make('nuove_ore')
                                ->label('Nuove ore acquistate')
                                ->numeric()
                                ->step(0.5)
                                ->minValue(0.5)
                                ->required(fn (Get $get) => $get('azione') === 'correggi_ore')
                                ->visible(fn (Get $get) => $get('azione') === 'correggi_ore')
                                ->helperText('Inserisci il valore corretto per le ore acquistate.'),

                            Textarea::make('motivo')
                                ->label('Motivo intervento (obbligatorio)')
                                ->required()
                                ->rows(2)
                                ->placeholder('Es. Lezioni duplicate per errore di sistema — corrette manualmente il 27/04/2026'),
                        ]),
                ])
                ->action(function (array $data): void {
                    $contract = Contract::find((int) $data['contract_id']);
                    if (! $contract) {
                        Notification::make()->title('Contratto non trovato')->danger()->send();
                        return;
                    }

                    $azione = $data['azione'] ?? '';
                    $motivo = $data['motivo'] ?? 'Intervento manuale amministrazione';

                    try {
                        switch ($azione) {

                            case 'rigenera':
                                app(LessonGeneratorService::class)->generateForContract($contract, false);
                                Contract::recalcConsumedHours($contract->id);
                                Notification::make()
                                    ->title('Lezioni generate')
                                    ->body("Contratto #{$contract->id}: lezioni mancanti generate correttamente.")
                                    ->success()->send();
                                break;

                            case 'elimina_eccesso':
                                $purchased = (float) ($contract->hours_purchased ?? 0);
                                $consumed  = (float) ($contract->hours_consumed ?? 0);
                                $residue   = max(0.0, $purchased - $consumed);

                                $future = Lesson::query()
                                    ->where('contract_id', $contract->id)
                                    ->whereNull('cancelled_at')
                                    ->where('starts_at', '>=', now())
                                    ->orderBy('starts_at')
                                    ->get();

                                $oreAccumulate = 0.0;
                                $daEliminare   = [];

                                foreach ($future as $lesson) {
                                    $ore = ($lesson->duration_minutes ?? 60) / 60;
                                    if (round($oreAccumulate + $ore, 4) <= round($residue, 4)) {
                                        $oreAccumulate += $ore;
                                    } else {
                                        $daEliminare[] = $lesson->id;
                                    }
                                }

                                $deleted = 0;
                                if (! empty($daEliminare)) {
                                    $deleted = Lesson::whereIn('id', $daEliminare)->delete();
                                }

                                Contract::recalcConsumedHours($contract->id);

                                Notification::make()
                                    ->title('Lezioni in eccesso eliminate')
                                    ->body("Contratto #{$contract->id}: {$deleted} lezioni eliminate. Mantenute " . round($oreAccumulate, 2) . " h su {$residue} h residue.")
                                    ->success()->send();
                                break;

                            case 'annulla_eccesso':
                                $purchased2 = (float) ($contract->hours_purchased ?? 0);
                                $consumed2  = (float) ($contract->hours_consumed ?? 0);
                                $residue2   = max(0.0, $purchased2 - $consumed2);

                                $future2 = Lesson::query()
                                    ->where('contract_id', $contract->id)
                                    ->whereNull('cancelled_at')
                                    ->where('starts_at', '>=', now())
                                    ->orderBy('starts_at')
                                    ->get();

                                $oreAccumulate2 = 0.0;
                                $daAnnullare    = collect();

                                foreach ($future2 as $lesson) {
                                    $ore = ($lesson->duration_minutes ?? 60) / 60;
                                    if (round($oreAccumulate2 + $ore, 4) <= round($residue2, 4)) {
                                        $oreAccumulate2 += $ore;
                                    } else {
                                        $daAnnullare->push($lesson);
                                    }
                                }

                                foreach ($daAnnullare as $lesson) {
                                    $lesson->cancelled_at        = now();
                                    $lesson->cancelled_by        = auth()->id();
                                    $lesson->cancellation_reason = $motivo;
                                    $lesson->is_recoverable      = false;
                                    $lesson->counts_as_consumed  = false;
                                    $lesson->save();
                                }

                                Notification::make()
                                    ->title('Lezioni in eccesso annullate')
                                    ->body("Contratto #{$contract->id}: {$daAnnullare->count()} lezioni annullate.")
                                    ->success()->send();
                                break;

                            case 'ricalcola':
                                Contract::recalcConsumedHours($contract->id);
                                $contract->refresh();
                                Notification::make()
                                    ->title('Ore ricalcolate')
                                    ->body("Contratto #{$contract->id}: ore fruite aggiornate a {$contract->hours_consumed} h.")
                                    ->success()->send();
                                break;

                            case 'correggi_ore':
                                $nuoveOre = (float) ($data['nuove_ore'] ?? 0);
                                if ($nuoveOre <= 0) {
                                    Notification::make()->title('Errore: ore non valide')->body('Inserisci un valore maggiore di zero.')->danger()->send();
                                    return;
                                }
                                $vecchieOre = $contract->hours_purchased;
                                $contract->hours_purchased = $nuoveOre;
                                $contract->save();
                                Contract::recalcConsumedHours($contract->id);
                                Notification::make()
                                    ->title('Ore contratto corrette')
                                    ->body("Contratto #{$contract->id}: ore cambiate da {$vecchieOre} a {$nuoveOre} h. Motivo: {$motivo}")
                                    ->success()->send();
                                break;

                            default:
                                Notification::make()->title('Errore: azione non valida')->danger()->send();
                        }
                    } catch (\Throwable $e) {
                        report($e);
                        Notification::make()
                            ->title('Errore durante la correzione')
                            ->body($e->getMessage())
                            ->danger()->persistent()->send();
                    }
                }),

            /* ============================================================
             *  CONTRATTO — Rigenera lezioni
             * ============================================================ */
            Action::make('rigenera_lezioni_contratto')
                ->label('Rigenera lezioni contratto')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Rigenera lezioni contratto')
                ->modalDescription('Cancella le lezioni future non svolte e le rigenera dagli slot. Confermi?')
                ->form([
                    $this->contractSelect(),
                    Placeholder::make('lessons_to_delete_preview')
                        ->label('Lezioni che verranno cancellate')
                        ->live()
                        ->content(function (Get $get) {
                            $contractId = $get('contract_id');
                            if (! $contractId) {
                                return new HtmlString("<span style='color:#6b7280;'>Seleziona prima un contratto.</span>");
                            }
                            $count = Lesson::query()
                                ->where('contract_id', (int) $contractId)
                                ->whereNull('cancelled_at')
                                ->where(function ($q) {
                                    $q->whereNull('counts_as_consumed')->orWhere('counts_as_consumed', 0);
                                })
                                ->whereDate('starts_at', '>=', now()->toDateString())
                                ->count();
                            if ($count === 0) {
                                return new HtmlString("<span style='color:#16a34a;font-weight:600;'>Nessuna lezione futura da cancellare.</span>");
                            }
                            return new HtmlString("<span style='color:#ca8a04;font-weight:600;'>Verranno cancellate <strong>{$count} lezioni future</strong> non ancora svolte e rigenerate dagli slot attivi.</span>");
                        }),
                    Toggle::make('force')
                        ->label('Force (rigenera anche dal passato)')
                        ->helperText('Usa solo se il contratto ha starts_at nel passato e non ci sono lezioni.')
                        ->default(false),
                ])
                ->action(function (array $data): void {
                    $contract = Contract::find((int) $data['contract_id']);
                    if (! $contract) {
                        Notification::make()->title('Contratto non trovato')->danger()->send();
                        return;
                    }
                    app(LessonGeneratorService::class)->generateForContract($contract, (bool) ($data['force'] ?? false));
                    Contract::recalcConsumedHours($contract->id);
                    Notification::make()
                        ->title('Lezioni rigenerate')
                        ->body("Contratto #{$contract->id} — lezioni rigenerate correttamente.")
                        ->success()->send();
                }),

            /* ============================================================
             *  CONTRATTO — Elimina lezioni future non svolte
             * ============================================================ */
            Action::make('elimina_lezioni_future_contratto')
                ->label('Elimina lezioni future contratto')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Elimina lezioni future (non svolte) — Contratto')
                ->modalDescription('IRREVERSIBILE. Verranno eliminate solo le lezioni future non svolte e non annullate.')
                ->form([
                    $this->contractSelect(),
                    Section::make('Anteprima')
                        ->schema([
                            Placeholder::make('preview')
                                ->label('')
                                ->content(function (Get $get): HtmlString|string {
                                    $id = $get('contract_id');
                                    if (! $id) return 'Seleziona prima un contratto.';
                                    $lessons = Lesson::query()
                                        ->with(['teacher', 'contract.course'])
                                        ->where('contract_id', $id)
                                        ->whereNull('cancelled_at')
                                        ->where('counts_as_consumed', 0)
                                        ->where('starts_at', '>=', now())
                                        ->orderBy('starts_at')
                                        ->get();
                                    return $this->lessonListPreview($lessons);
                                })
                                ->live(),
                        ]),
                    TextInput::make('conferma')
                        ->label('Scrivi ELIMINA per confermare')
                        ->required()
                        ->rules(['in:ELIMINA']),
                ])
                ->action(function (array $data): void {
                    if (($data['conferma'] ?? '') !== 'ELIMINA') {
                        Notification::make()->title('Errore: conferma non valida. Scrivi ELIMINA.')->danger()->send();
                        return;
                    }
                    $contract = Contract::find((int) $data['contract_id']);
                    if (! $contract) {
                        Notification::make()->title('Contratto non trovato')->danger()->send();
                        return;
                    }
                    $deleted = Lesson::query()
                        ->where('contract_id', $contract->id)
                        ->whereNull('cancelled_at')
                        ->where('counts_as_consumed', 0)
                        ->where('starts_at', '>=', now())
                        ->delete();
                    Contract::recalcConsumedHours($contract->id);
                    Notification::make()
                        ->title('Lezioni eliminate')
                        ->body("Contratto #{$contract->id} — {$deleted} lezione/i eliminate.")
                        ->success()->send();
                }),

            /* ============================================================
             *  CONTRATTO — Annulla definitivamente lezioni future
             * ============================================================ */
            Action::make('annulla_definitivo_contratto')
                ->label('Annulla definitivamente lezioni future (contratto)')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Annulla definitivamente lezioni future — Contratto')
                ->modalDescription('Le lezioni future non svolte verranno annullate senza recupero e senza scalare ore.')
                ->form([
                    $this->contractSelect(),
                    Textarea::make('cancellation_reason')
                        ->label('Motivo annullamento')
                        ->required()
                        ->rows(2)
                        ->placeholder('Es. Lezioni in eccesso — contratto terminato anticipatamente'),
                    Section::make('Anteprima')
                        ->schema([
                            Placeholder::make('preview')
                                ->label('')
                                ->content(function (Get $get): HtmlString|string {
                                    $id = $get('contract_id');
                                    if (! $id) return 'Seleziona prima un contratto.';
                                    $lessons = Lesson::query()
                                        ->with(['teacher', 'contract.course'])
                                        ->where('contract_id', $id)
                                        ->whereNull('cancelled_at')
                                        ->where('counts_as_consumed', 0)
                                        ->where('starts_at', '>=', now())
                                        ->orderBy('starts_at')
                                        ->get();
                                    return $this->lessonListPreview($lessons);
                                })
                                ->live(),
                        ]),
                ])
                ->action(function (array $data): void {
                    $contract = Contract::find((int) $data['contract_id']);
                    if (! $contract) {
                        Notification::make()->title('Contratto non trovato')->danger()->send();
                        return;
                    }
                    $reason  = $data['cancellation_reason'] ?? 'Annullata definitivamente da gestione operazioni';
                    $updated = Lesson::query()
                        ->where('contract_id', $contract->id)
                        ->whereNull('cancelled_at')
                        ->where('counts_as_consumed', 0)
                        ->where('starts_at', '>=', now())
                        ->get();
                    foreach ($updated as $lesson) {
                        $lesson->cancelled_at        = now();
                        $lesson->cancelled_by        = auth()->id();
                        $lesson->cancellation_reason = $reason;
                        $lesson->is_recoverable      = false;
                        $lesson->counts_as_consumed  = false;
                        $lesson->save();
                    }
                    Notification::make()
                        ->title('Lezioni annullate definitivamente')
                        ->body("Contratto #{$contract->id} — {$updated->count()} lezione/i annullate.")
                        ->success()->send();
                }),

            /* ============================================================
             *  CONTRATTO — Ricalcola ore consumate
             * ============================================================ */
            Action::make('ricalcola_ore_contratto')
                ->label('Ricalcola ore consumate')
                ->icon('heroicon-o-calculator')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Ricalcola ore consumate — Contratto')
                ->modalDescription('Ricalcola il campo "Ore fruite" basandosi sulle lezioni completate. Operazione sicura.')
                ->form([$this->contractSelect()])
                ->action(function (array $data): void {
                    $id = (int) $data['contract_id'];
                    Contract::recalcConsumedHours($id);
                    $contract  = Contract::find($id);
                    $consumed  = $contract?->hours_consumed ?? '?';
                    $purchased = $contract?->hours_purchased ?? '?';
                    Notification::make()
                        ->title('Ore ricalcolate')
                        ->body("Contratto #{$id} — fruite: {$consumed} / acquistate: {$purchased}")
                        ->success()->send();
                }),

            /* ============================================================
             *  STUDENTE — Elimina lezioni future non svolte
             * ============================================================ */
            Action::make('elimina_lezioni_future_studente')
                ->label('Elimina lezioni future studente')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Elimina lezioni future (non svolte) — Studente')
                ->modalDescription('Verranno eliminate tutte le lezioni future non svolte dello studente su TUTTI i contratti. IRREVERSIBILE.')
                ->form([
                    $this->studentSelect(),
                    Section::make('Anteprima')
                        ->schema([
                            Placeholder::make('preview')
                                ->label('')
                                ->content(function (Get $get): HtmlString|string {
                                    $id = $get('student_id');
                                    if (! $id) return 'Seleziona prima uno studente.';
                                    $lessons = Lesson::query()
                                        ->with(['teacher', 'contract.course'])
                                        ->where('student_id', $id)
                                        ->whereNull('cancelled_at')
                                        ->where('counts_as_consumed', 0)
                                        ->where('starts_at', '>=', now())
                                        ->orderBy('starts_at')
                                        ->get();
                                    return $this->lessonListPreview($lessons);
                                })
                                ->live(),
                        ]),
                    TextInput::make('conferma')
                        ->label('Scrivi ELIMINA per confermare')
                        ->required()
                        ->rules(['in:ELIMINA']),
                ])
                ->action(function (array $data): void {
                    if (($data['conferma'] ?? '') !== 'ELIMINA') {
                        Notification::make()->title('Errore: conferma non valida. Scrivi ELIMINA.')->danger()->send();
                        return;
                    }
                    $student = Student::find((int) $data['student_id']);
                    if (! $student) {
                        Notification::make()->title('Studente non trovato')->danger()->send();
                        return;
                    }
                    $contractIds = Lesson::query()
                        ->where('student_id', $student->id)
                        ->whereNull('cancelled_at')
                        ->where('counts_as_consumed', 0)
                        ->where('starts_at', '>=', now())
                        ->whereNotNull('contract_id')
                        ->pluck('contract_id')
                        ->unique()
                        ->values();
                    $deleted = Lesson::query()
                        ->where('student_id', $student->id)
                        ->whereNull('cancelled_at')
                        ->where('counts_as_consumed', 0)
                        ->where('starts_at', '>=', now())
                        ->delete();
                    foreach ($contractIds as $cid) {
                        Contract::recalcConsumedHours($cid);
                    }
                    Notification::make()
                        ->title('Lezioni eliminate')
                        ->body("{$student->full_name} — {$deleted} lezione/i eliminate.")
                        ->success()->send();
                }),

            /* ============================================================
             *  STUDENTE — Annulla definitivamente lezioni future
             * ============================================================ */
            Action::make('annulla_definitivo_studente')
                ->label('Annulla definitivamente lezioni future (studente)')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Annulla definitivamente lezioni future — Studente')
                ->form([
                    $this->studentSelect(),
                    Textarea::make('cancellation_reason')
                        ->label('Motivo annullamento')
                        ->required()
                        ->rows(2),
                    Section::make('Anteprima')
                        ->schema([
                            Placeholder::make('preview')
                                ->label('')
                                ->content(function (Get $get): HtmlString|string {
                                    $id = $get('student_id');
                                    if (! $id) return 'Seleziona prima uno studente.';
                                    $lessons = Lesson::query()
                                        ->with(['teacher', 'contract.course'])
                                        ->where('student_id', $id)
                                        ->whereNull('cancelled_at')
                                        ->where('counts_as_consumed', 0)
                                        ->where('starts_at', '>=', now())
                                        ->orderBy('starts_at')
                                        ->get();
                                    return $this->lessonListPreview($lessons);
                                })
                                ->live(),
                        ]),
                ])
                ->action(function (array $data): void {
                    $student = Student::find((int) $data['student_id']);
                    if (! $student) {
                        Notification::make()->title('Studente non trovato')->danger()->send();
                        return;
                    }
                    $reason  = $data['cancellation_reason'] ?? 'Annullata definitivamente';
                    $lessons = Lesson::query()
                        ->where('student_id', $student->id)
                        ->whereNull('cancelled_at')
                        ->where('counts_as_consumed', 0)
                        ->where('starts_at', '>=', now())
                        ->get();
                    foreach ($lessons as $lesson) {
                        $lesson->cancelled_at        = now();
                        $lesson->cancelled_by        = auth()->id();
                        $lesson->cancellation_reason = $reason;
                        $lesson->is_recoverable      = false;
                        $lesson->counts_as_consumed  = false;
                        $lesson->save();
                    }
                    Notification::make()
                        ->title('Lezioni annullate definitivamente')
                        ->body("{$student->full_name} — {$lessons->count()} lezione/i annullate.")
                        ->success()->send();
                }),

            /* ============================================================
             *  CONTRATTO — Riattiva lezioni annullate
             * ============================================================ */
            Action::make('riattiva_lezioni_contratto')
                ->label('Riattiva lezioni annullate (contratto)')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Riattiva lezioni annullate — Contratto')
                ->modalDescription('Rimuove l\'annullamento dalle lezioni future del contratto, riportandole allo stato attivo.')
                ->form([
                    $this->contractSelect(),
                    Section::make('Anteprima — lezioni che verranno riattivate')
                        ->schema([
                            Placeholder::make('preview')
                                ->label('')
                                ->content(function (Get $get): HtmlString|string {
                                    $id = $get('contract_id');
                                    if (! $id) return 'Seleziona prima un contratto.';
                                    $lessons = Lesson::query()
                                        ->with(['teacher', 'contract.course'])
                                        ->where('contract_id', $id)
                                        ->whereNotNull('cancelled_at')
                                        ->where('starts_at', '>=', now())
                                        ->orderBy('starts_at')
                                        ->get();
                                    return $this->lessonListPreview($lessons);
                                })
                                ->live(),
                        ]),
                ])
                ->action(function (array $data): void {
                    $contract = Contract::find((int) $data['contract_id']);
                    if (! $contract) {
                        Notification::make()->title('Contratto non trovato')->danger()->send();
                        return;
                    }
                    $lessons = Lesson::query()
                        ->where('contract_id', $contract->id)
                        ->whereNotNull('cancelled_at')
                        ->where('starts_at', '>=', now())
                        ->get();
                    foreach ($lessons as $lesson) {
                        $lesson->cancelled_at        = null;
                        $lesson->cancelled_by        = null;
                        $lesson->cancellation_reason = null;
                        $lesson->is_recoverable      = false;
                        $lesson->counts_as_consumed  = false;
                        $lesson->save();
                    }
                    Contract::recalcConsumedHours($contract->id);
                    Notification::make()
                        ->title('Lezioni riattivate')
                        ->body("Contratto #{$contract->id} — {$lessons->count()} lezione/i riattivate.")
                        ->success()->send();
                }),

            /* ============================================================
             *  STUDENTE — Riattiva lezioni annullate
             * ============================================================ */
            Action::make('riattiva_lezioni_studente')
                ->label('Riattiva lezioni annullate (studente)')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Riattiva lezioni annullate — Studente')
                ->modalDescription('Rimuove l\'annullamento dalle lezioni future dello studente su tutti i contratti.')
                ->form([
                    $this->studentSelect(),
                    Section::make('Anteprima — lezioni che verranno riattivate')
                        ->schema([
                            Placeholder::make('preview')
                                ->label('')
                                ->content(function (Get $get): HtmlString|string {
                                    $id = $get('student_id');
                                    if (! $id) return 'Seleziona prima uno studente.';
                                    $lessons = Lesson::query()
                                        ->with(['teacher', 'contract.course'])
                                        ->where('student_id', $id)
                                        ->whereNotNull('cancelled_at')
                                        ->where('starts_at', '>=', now())
                                        ->orderBy('starts_at')
                                        ->get();
                                    return $this->lessonListPreview($lessons);
                                })
                                ->live(),
                        ]),
                ])
                ->action(function (array $data): void {
                    $student = Student::find((int) $data['student_id']);
                    if (! $student) {
                        Notification::make()->title('Studente non trovato')->danger()->send();
                        return;
                    }
                    $contractIds = Lesson::query()
                        ->where('student_id', $student->id)
                        ->whereNotNull('cancelled_at')
                        ->where('starts_at', '>=', now())
                        ->whereNotNull('contract_id')
                        ->pluck('contract_id')
                        ->unique()
                        ->values();
                    $lessons = Lesson::query()
                        ->where('student_id', $student->id)
                        ->whereNotNull('cancelled_at')
                        ->where('starts_at', '>=', now())
                        ->get();
                    foreach ($lessons as $lesson) {
                        $lesson->cancelled_at        = null;
                        $lesson->cancelled_by        = null;
                        $lesson->cancellation_reason = null;
                        $lesson->is_recoverable      = false;
                        $lesson->counts_as_consumed  = false;
                        $lesson->save();
                    }
                    foreach ($contractIds as $cid) {
                        Contract::recalcConsumedHours($cid);
                    }
                    Notification::make()
                        ->title('Lezioni riattivate')
                        ->body("{$student->full_name} — {$lessons->count()} lezione/i riattivate.")
                        ->success()->send();
                }),

            /* ============================================================
             *  SISTEMA — Bonifica consumi
             * ============================================================ */
            Action::make('bonifica_consumi')
                ->label('Bonifica ore fruite')
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Bonifica ore fruite — Sistema')
                ->modalDescription('Riallinea i flag delle lezioni e ricalcola hours_consumed su tutti i contratti.')
                ->form([
                    Toggle::make('dry_run')
                        ->label('Dry-run (simula, non salva)')
                        ->default(false),
                ])
                ->action(function (array $data): void {
                    $params = ['--chunk' => 300];
                    if (! empty($data['dry_run'])) {
                        $params['--dry'] = true;
                    }
                    Artisan::call('scuole:bonifica', $params);
                    Notification::make()
                        ->title('Bonifica completata')
                        ->body('Flag lezioni e ore consumate riallineate.')
                        ->success()->send();
                }),

            /* ============================================================
             *  SISTEMA — Fix lezioni future
             * ============================================================ */
            Action::make('fix_lezioni_future')
                ->label('Fix lezioni future errate')
                ->icon('heroicon-o-calendar-days')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Fix lezioni future — Sistema')
                ->modalDescription('Corregge lezioni future non annullate erroneamente marcate come "consumate".')
                ->form([
                    Toggle::make('dry_run')
                        ->label('Dry-run (simula, non salva)')
                        ->default(false),
                ])
                ->action(function (array $data): void {
                    $params = [];
                    if (! empty($data['dry_run'])) {
                        $params['--dry-run'] = true;
                    }
                    Artisan::call('lessons:fix-future-counts', $params);
                    Notification::make()
                        ->title('Fix completato')
                        ->success()->send();
                }),
        ];
    }
}
