<?php

namespace App\Filament\Pages\Reports;

use App\Models\Contract;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnomalyReport extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-shield-exclamation';
    protected static ?string $navigationGroup = 'Report';
    protected static ?string $navigationLabel = 'Controllo anomalie';
    protected static ?string $slug            = 'anomaly-report';
    protected static string  $view            = 'filament.pages.reports.anomaly-report';
    protected static ?int    $navigationSort  = 30;

    public array $data    = [];
    public array $results = [];
    public bool  $ran     = false;

    /* ------------------------------------------------------------------ */

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

    /* ------------------------------------------------------------------ */

    public function mount(): void
    {
        $this->form->fill([
            'academic_year' => null,
            'from'          => now()->startOfMonth()->toDateString(),
            'to'            => now()->endOfMonth()->toDateString(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Section::make('Filtri opzionali')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('academic_year')
                            ->label('Anno didattico')
                            ->placeholder('Tutti gli anni')
                            ->options(fn () => Contract::query()
                                ->whereNotNull('academic_year')
                                ->distinct()->orderBy('academic_year')
                                ->pluck('academic_year', 'academic_year')
                                ->toArray()
                            )
                            ->nullable(),

                        Forms\Components\DatePicker::make('from')
                            ->label('Periodo da')
                            ->native(false),

                        Forms\Components\DatePicker::make('to')
                            ->label('Periodo a')
                            ->native(false),
                    ]),
            ]);
    }

    /* ------------------------------------------------------------------ */

    public function runChecks(): void
    {
        $this->form->getState();

        $year  = $this->data['academic_year'] ?? null;
        $from  = filled($this->data['from'] ?? null)  ? Carbon::parse($this->data['from'])->startOfDay()  : null;
        $to    = filled($this->data['to']   ?? null)  ? Carbon::parse($this->data['to'])->endOfDay()      : null;

        $this->results = [
            'lezioni'   => $this->checkLezioni($from, $to, $year),
            'contratti' => $this->checkContratti($year),
            'docenti'   => $this->checkDocenti($from, $to, $year),
            'studenti'  => $this->checkStudenti(),
            'rate'      => $this->checkRate($year),
        ];

        $this->ran = true;
    }

    /* ==================================================================
     *  LEZIONI  — tutto in un'unica query aggregata
     * ================================================================== */

    protected function checkLezioni(?Carbon $from, ?Carbon $to, ?string $year): array
    {
        /*
         * Una sola query che calcola tutti i conteggi con CASE WHEN.
         * Nessun N+1, nessuna query in loop.
         */
        $row = DB::selectOne("
            SELECT
                /* 1. senza durata */
                SUM(CASE WHEN cancelled_at IS NULL
                         AND (duration_minutes IS NULL OR duration_minutes <= 0)
                         AND ends_at IS NULL
                    THEN 1 ELSE 0 END) AS no_duration,

                /* 2. durata oltre 2 ore */
                SUM(CASE WHEN cancelled_at IS NULL
                         AND (
                             duration_minutes > 120
                             OR (
                                 ends_at IS NOT NULL
                                 AND starts_at IS NOT NULL
                                 AND TIMESTAMPDIFF(MINUTE, starts_at, ends_at) > 120
                             )
                         )
                    THEN 1 ELSE 0 END) AS long_duration,

                /* 3. annullate recuperabili senza lezione di recupero */
                SUM(CASE WHEN is_recoverable = 1
                         AND cancelled_at IS NOT NULL
                         AND recovery_of_lesson_id IS NULL
                         AND NOT EXISTS (
                             SELECT 1 FROM lessons r
                             WHERE r.recovery_of_lesson_id = lessons.id
                         )
                    THEN 1 ELSE 0 END) AS no_recovery_planned,

                /* 4. future già completate */
                SUM(CASE WHEN completed_at IS NOT NULL
                         AND starts_at > NOW()
                    THEN 1 ELSE 0 END) AS future_completed,

                /* 5. passate non gestite (> 2h fa) */
                SUM(CASE WHEN completed_at IS NULL
                         AND cancelled_at IS NULL
                         AND starts_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)
                         AND recovery_of_lesson_id IS NULL
                    THEN 1 ELSE 0 END) AS past_unmanaged

            FROM lessons
            WHERE 1=1
            " . ($from  ? " AND starts_at >= ?"  : "") . "
            " . ($to    ? " AND starts_at <= ?"  : "") . "
            " . ($year  ? " AND contract_id IN (SELECT id FROM contracts WHERE academic_year = ?)" : ""),
            array_filter([$from?->toDateTimeString(), $to?->toDateTimeString(), $year], fn ($v) => $v !== null)
        );

        $lessonBase = '/admin/lessons';
        $lessonFilters = [
            // Disattiva il filtro "Da oggi" di default, poi aggiunge il filtro anomalia specifico.
            'upcoming' => ['isActive' => false],
        ];

        if ($year) {
            $lessonFilters['academic_year'] = ['value' => $year];
        }

        if ($from || $to) {
            $lessonFilters['date_range'] = array_filter([
                'from'  => $from?->toDateString(),
                'until' => $to?->toDateString(),
            ], fn ($v) => $v !== null);
        }

        $urlAnomaly = fn (string $anomaly) => $this->tableUrl($lessonBase, $lessonFilters + [
            'anomaly' => ['value' => $anomaly],
        ]);

        return [
            $this->item(
                $row->no_duration > 0 ? 'warning' : 'success',
                'Lezioni senza durata esplicita',
                (int) $row->no_duration,
                'La durata viene calcolata come fallback a 60 minuti. Questo causa imprecisioni nel conteggio delle ore e nella paga docenti.',
                'Apri ogni lezione e imposta la durata in minuti (campo "Durata"), oppure verifica che l\'orario di fine sia compilato.',
                $row->no_duration > 0 ? $urlAnomaly('no_duration') : null
            ),

            $this->item(
                $row->long_duration > 0 ? 'warning' : 'success',
                'Lezioni con durata superiore a 2 ore',
                (int) $row->long_duration,
                'Queste lezioni hanno una durata superiore a 120 minuti. Potrebbe essere corretto, ma spesso indica un errore di orario o durata.',
                'Apri ogni lezione interessata e verifica durata, ora di inizio e ora di fine.',
                $row->long_duration > 0 ? $urlAnomaly('long_duration') : null
            ),

            $this->item(
                $row->no_recovery_planned > 0 ? 'warning' : 'success',
                'Lezioni da recuperare senza recupero pianificato',
                (int) $row->no_recovery_planned,
                'Queste lezioni sono state annullate e risultano recuperabili, ma non è ancora stata pianificata una lezione di recupero.',
                'Vai in Lezioni → nella lista filtrata individua ogni lezione → menu azioni → usa "Crea recupero".',
                $row->no_recovery_planned > 0 ? $urlAnomaly('no_recovery_planned') : null
            ),

            $this->item(
                $row->future_completed > 0 ? 'danger' : 'success',
                'Lezioni future segnate come completate',
                (int) $row->future_completed,
                'Anomalia dati: una lezione con data futura è marcata come completata. Questo può causare errori nel conteggio ore.',
                'Apri la lezione interessata e verifica la data di completamento o lo stato.',
                $row->future_completed > 0 ? $urlAnomaly('future_completed') : null
            ),

            $this->item(
                $row->past_unmanaged > 0 ? 'warning' : 'success',
                'Lezioni passate non gestite',
                (int) $row->past_unmanaged,
                'Lezioni già passate (più di 2 ore fa) che non risultano né completate né annullate. Le ore non vengono conteggiate.',
                'Filtra le lezioni per data e segna ciascuna come "Svolta" o "Annullata" tramite le azioni rapide.',
                $row->past_unmanaged > 0 ? $urlAnomaly('past_unmanaged') : null
            ),

        ];
    }

    /* ==================================================================
     *  CONTRATTI  — query aggregate
     * ================================================================== */

    protected function checkContratti(?string $year): array
    {
        $yearClause     = $year ? " AND academic_year = " . DB::connection()->getPdo()->quote($year) : '';
        $yearClauseBase = $year ? " AND c.academic_year = "  . DB::connection()->getPdo()->quote($year) : '';

        $row = DB::selectOne("
            SELECT
                /* 1. attivi senza studenti */
                (SELECT COUNT(*) FROM contracts c
                 WHERE c.status = 'active' {$yearClauseBase}
                   AND NOT EXISTS (SELECT 1 FROM contract_students cs WHERE cs.contract_id = c.id)
                ) AS no_students,

                /* 2. ore consumate > acquistate */
                (SELECT COUNT(*) FROM contracts c
                 WHERE c.hours_purchased > 0 {$yearClauseBase}
                   AND c.hours_consumed > c.hours_purchased
                ) AS hours_exceeded,

                /* 3. scaduti ma attivi */
                (SELECT COUNT(*) FROM contracts c
                 WHERE c.status = 'active' {$yearClauseBase}
                   AND c.ends_at IS NOT NULL AND c.ends_at < CURDATE()
                ) AS expired_active,

                /* 4. a rate senza rate create */
                (SELECT COUNT(*) FROM contracts c
                 WHERE c.status = 'active' AND c.payment_mode = 'installments' {$yearClauseBase}
                   AND NOT EXISTS (SELECT 1 FROM installments i WHERE i.contract_id = c.id)
                ) AS installments_missing,

                /* 5. prezzo zero */
                (SELECT COUNT(*) FROM contracts c
                 WHERE c.status = 'active' {$yearClauseBase}
                   AND (c.course_price IS NULL OR c.course_price <= 0)
                ) AS zero_price,

                /* 6. senza corso */
                (SELECT COUNT(*) FROM contracts c
                 WHERE c.status = 'active' {$yearClauseBase}
                   AND c.course_id IS NULL
                ) AS no_course
        "
        );

        $contractBase = '/admin/contracts';
        $contractUrl = function (string $anomaly, array $extraFilters = []) use ($contractBase, $year): string {
            $filters = [
                'anomaly' => ['value' => $anomaly],
            ];

            if ($year) {
                $filters['academic_year'] = ['value' => $year];
            }

            return $this->tableUrl($contractBase, $filters + $extraFilters);
        };

        return [
            $this->item(
                $row->no_students > 0 ? 'danger' : 'success',
                'Contratti attivi senza studenti',
                (int) $row->no_students,
                'Un contratto attivo senza studenti collegati non genererà lezioni e non apparirà nell\'area studente.',
                'Apri il contratto → sezione Beneficiari e aggiungi almeno uno studente.',
                $row->no_students > 0 ? $contractUrl('no_students') : null
            ),

            $this->item(
                $row->hours_exceeded > 0 ? 'danger' : 'success',
                'Contratti con ore consumate superiori alle acquistate',
                (int) $row->hours_exceeded,
                'Le ore fruite superano quelle previste dal contratto. Può indicare un errore di calcolo o lezioni segnate per errore.',
                'Verifica le lezioni completate del contratto e controlla se ci sono duplicati o lezioni segnate erroneamente come consumate.',
                $row->hours_exceeded > 0 ? $contractUrl('hours_exceeded') : null
            ),

            $this->item(
                $row->expired_active > 0 ? 'warning' : 'success',
                'Contratti scaduti ancora attivi',
                (int) $row->expired_active,
                'La data di fine contratto è passata ma lo stato risulta ancora "Attivo". Potrebbe impedire la corretta gestione.',
                'Apri ogni contratto scaduto e aggiorna lo stato in "Completato" o "Sospeso" secondo la situazione reale.',
                $row->expired_active > 0 ? $contractUrl('expired_active') : null
            ),

            $this->item(
                $row->installments_missing > 0 ? 'danger' : 'success',
                'Contratti a rate senza rate create',
                (int) $row->installments_missing,
                'Il contratto prevede il pagamento a rate ma non è stata creata nessuna rata. Il cliente non riceverà solleciti e i pagamenti non sono tracciabili.',
                'Apri il contratto → sezione Rate e crea le rate con date di scadenza e importi, oppure usa la generazione automatica rate.',
                $row->installments_missing > 0 ? $contractUrl('installments_missing') : null
            ),

            $this->item(
                $row->zero_price > 0 ? 'warning' : 'success',
                'Contratti attivi con prezzo corso = 0',
                (int) $row->zero_price,
                'Un contratto attivo con prezzo zero potrebbe essere un errore di inserimento. I report finanziari risulteranno incompleti.',
                'Apri il contratto e verifica il prezzo corso e la tassa di iscrizione.',
                $row->zero_price > 0 ? $contractUrl('zero_price') : null
            ),

            $this->item(
                $row->no_course > 0 ? 'danger' : 'success',
                'Contratti senza corso associato',
                (int) $row->no_course,
                'Senza un corso collegato il contratto non può generare lezioni correttamente.',
                'Apri il contratto e seleziona il corso nel passaggio 2 del wizard.',
                $row->no_course > 0 ? $contractUrl('no_course') : null
            ),
        ];
    }

    /* ==================================================================
     *  DOCENTI  — query aggregate
     * ================================================================== */

    protected function checkDocenti(?Carbon $from, ?Carbon $to, ?string $year): array
    {
        // Tutti i docenti in una query
        $teacherStats = DB::selectOne("
            SELECT
                SUM(CASE WHEN teacher_hourly_rate_gross IS NULL OR teacher_hourly_rate_gross <= 0 THEN 1 ELSE 0 END) AS no_rate,
                SUM(CASE WHEN teacher_billing_mode IS NULL OR teacher_billing_mode = '' THEN 1 ELSE 0 END) AS no_billing,
                SUM(CASE WHEN teacher_contract_type IS NULL OR teacher_contract_type = '' THEN 1 ELSE 0 END) AS no_contract_type,
                SUM(CASE WHEN tax_code IS NULL OR tax_code = '' THEN 1 ELSE 0 END) AS no_tax_code,
                SUM(CASE WHEN iban IS NULL OR iban = '' THEN 1 ELSE 0 END) AS no_iban
            FROM users
            WHERE id IN (
                SELECT model_id FROM model_has_roles r
                JOIN roles ro ON ro.id = r.role_id
                WHERE ro.name IN ('Docente','docente')
                  AND r.model_type = 'App\\\\Models\\\\User'
            )
        ");

        $teacherBase = '/admin/teachers';
        $teacherUrl = fn (string $anomaly) => $this->tableUrl($teacherBase, [
            'anomaly' => ['value' => $anomaly],
        ]);

        $items = [
            $this->item(
                $teacherStats->no_rate > 0 ? 'danger' : 'success',
                'Docenti senza tariffa oraria',
                (int) $teacherStats->no_rate,
                'Senza tariffa €/h non è possibile calcolare le paghe nel report "Paghe docenti".',
                'Vai in Risorse Umane → Docenti → Modifica docente → sezione Contratto → imposta la tariffa oraria lorda.',
                $teacherStats->no_rate > 0 ? $teacherUrl('no_rate') : null
            ),
            $this->item(
                $teacherStats->no_billing > 0 ? 'warning' : 'success',
                'Docenti senza modalità fatturazione',
                (int) $teacherStats->no_billing,
                'La modalità di fatturazione è necessaria per calcolare la ritenuta d\'acconto e il netto da pagare.',
                'Vai in Risorse Umane → Docenti → Modifica docente → sezione Contratto → seleziona la modalità (es. Ritenuta 20%, Partita IVA).',
                $teacherStats->no_billing > 0 ? $teacherUrl('no_billing') : null
            ),
            $this->item(
                $teacherStats->no_contract_type > 0 ? 'warning' : 'success',
                'Docenti senza tipo contratto',
                (int) $teacherStats->no_contract_type,
                'Il tipo di contratto è utile per la gestione amministrativa e per i report.',
                'Vai in Risorse Umane → Docenti → Modifica docente → sezione Contratto → imposta il tipo (Dipendente, Collaborazione, P.IVA, ecc.).',
                $teacherStats->no_contract_type > 0 ? $teacherUrl('no_contract_type') : null
            ),
            $this->item(
                $teacherStats->no_tax_code > 0 ? 'warning' : 'success',
                'Docenti senza codice fiscale',
                (int) $teacherStats->no_tax_code,
                'Il codice fiscale è obbligatorio per i documenti fiscali (CU, ritenuta d\'acconto).',
                'Vai in Risorse Umane → Docenti → Modifica docente → sezione Dati fiscali.',
                $teacherStats->no_tax_code > 0 ? $teacherUrl('no_tax_code') : null
            ),
            $this->item(
                $teacherStats->no_iban > 0 ? 'warning' : 'success',
                'Docenti senza IBAN',
                (int) $teacherStats->no_iban,
                'Senza IBAN non è possibile registrare i pagamenti bancari al docente.',
                'Vai in Risorse Umane → Docenti → Modifica docente → sezione Dati amministrativi → IBAN.',
                $teacherStats->no_iban > 0 ? $teacherUrl('no_iban') : null
            ),
        ];

        // Extra: docenti con ore nel periodo ma tariffa mancante
        if ($from || $to || $year) {
            $params = array_values(array_filter([
                $from?->toDateTimeString(),
                $to?->toDateTimeString(),
                $year,
            ]));

            $n = DB::selectOne("
                SELECT COUNT(DISTINCT teacher_id) AS cnt
                FROM lessons
                WHERE teacher_id IS NOT NULL
                  AND cancelled_at IS NULL
                  AND counts_as_consumed = 1
                  " . ($from ? "AND starts_at >= ?" : "") . "
                  " . ($to   ? "AND starts_at <= ?" : "") . "
                  " . ($year ? "AND contract_id IN (SELECT id FROM contracts WHERE academic_year = ?)" : "") . "
                  AND teacher_id IN (
                      SELECT id FROM users
                      WHERE teacher_hourly_rate_gross IS NULL OR teacher_hourly_rate_gross <= 0
                  )
            ", $params);

            $items[] = $this->item(
                $n->cnt > 0 ? 'danger' : 'success',
                'Docenti con ore lavorate nel periodo ma senza tariffa',
                (int) $n->cnt,
                'Questi docenti hanno lezioni consumate nel periodo selezionato ma non hanno tariffa oraria: le paghe non possono essere calcolate.',
                'Imposta la tariffa oraria in Risorse Umane → Docenti prima di generare il report paghe.',
                $n->cnt > 0 ? $teacherUrl('no_rate') : null
            );
        }

        return $items;
    }

    /* ==================================================================
     *  STUDENTI  — query aggregate
     * ================================================================== */

    protected function checkStudenti(): array
    {
        $row = DB::selectOne("
            SELECT
                /* senza email né telefono */
                (SELECT COUNT(*) FROM students s
                 WHERE (s.email IS NULL OR s.email = '')
                   AND (s.phone IS NULL OR s.phone = '')
                ) AS no_contacts,

                /* minorenni senza genitore */
                (SELECT COUNT(*) FROM students s
                 WHERE s.is_minor = 1
                   AND (s.parent_first_name IS NULL OR s.parent_first_name = '')
                ) AS minor_no_parent,

                /* duplicati per nome+cognome */
                (SELECT COUNT(*) FROM (
                    SELECT LOWER(first_name) fn, LOWER(last_name) ln
                    FROM students
                    WHERE first_name IS NOT NULL AND last_name IS NOT NULL
                    GROUP BY LOWER(first_name), LOWER(last_name)
                    HAVING COUNT(*) > 1
                ) dupes) AS duplicates
        ");

        $studentBase = '/admin/students';
        $studentUrl = fn (string $anomaly) => $this->tableUrl($studentBase, [
            'anomaly' => ['value' => $anomaly],
        ]);

        return [
            $this->item(
                $row->no_contacts > 0 ? 'warning' : 'success',
                'Studenti senza recapiti (email e telefono)',
                (int) $row->no_contacts,
                'Senza recapiti non è possibile inviare notifiche automatiche, promemoria lezioni o comunicazioni di pagamento.',
                'Apri la scheda studente e completa almeno uno tra email e telefono.',
                $row->no_contacts > 0 ? $studentUrl('no_contacts') : null
            ),
            $this->item(
                $row->minor_no_parent > 0 ? 'danger' : 'success',
                'Studenti minorenni senza dati genitore',
                (int) $row->minor_no_parent,
                'Gli studenti minorenni devono avere i dati del genitore/tutore legale per la validità del contratto.',
                'Apri la scheda studente → sezione Dati genitore/tutore e compila nome, cognome e contatti.',
                $row->minor_no_parent > 0 ? $studentUrl('minor_no_parent') : null
            ),
            $this->item(
                $row->duplicates > 0 ? 'warning' : 'success',
                'Possibili studenti duplicati',
                (int) $row->duplicates,
                'Esistono più studenti con la stessa combinazione nome/cognome. Potrebbero essere duplicati creati per errore.',
                'Cerca per nome nella lista studenti, verifica i duplicati e unisci o elimina i record ridondanti mantenendo quello con più dati completi.',
                $row->duplicates > 0 ? $studentUrl('duplicates') : null
            ),
        ];
    }

    /* ==================================================================
     *  RATE  — tutto via DB::table (nessun SoftDeletes)
     * ================================================================== */

    protected function checkRate(?string $year): array
    {
        $yearJoin = $year
            ? " AND EXISTS (SELECT 1 FROM contracts c WHERE c.id = i.contract_id AND c.academic_year = " . DB::connection()->getPdo()->quote($year) . ")"
            : '';

        $row = DB::selectOne("
            SELECT
                /* scadute non pagate */
                (SELECT COUNT(*) FROM installments i
                 WHERE (i.status != 'paid' OR i.status IS NULL) AND i.paid_at IS NULL
                   AND i.due_date < CURDATE()
                   {$yearJoin}
                ) AS overdue,

                /* in scadenza 7 giorni */
                (SELECT COUNT(*) FROM installments i
                 WHERE (i.status != 'paid' OR i.status IS NULL) AND i.paid_at IS NULL
                   AND i.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                   {$yearJoin}
                ) AS due_soon,

                /* senza importo */
                (SELECT COUNT(*) FROM installments i
                 WHERE (i.amount IS NULL OR i.amount <= 0)
                   {$yearJoin}
                ) AS no_amount,

                /* senza data scadenza */
                (SELECT COUNT(*) FROM installments i
                 WHERE i.due_date IS NULL
                   {$yearJoin}
                ) AS no_due_date,

                /* contratti con residuo ma tutte le rate pagate */
                (SELECT COUNT(*) FROM contracts c
                 WHERE c.status = 'active'
                   AND c.payment_mode = 'installments'
                   AND (COALESCE(c.course_price,0) + COALESCE(c.enrollment_fee,0) - COALESCE(c.deposit,0)) > 0
                   " . ($year ? "AND c.academic_year = " . DB::connection()->getPdo()->quote($year) : "") . "
                   AND EXISTS (SELECT 1 FROM installments i2 WHERE i2.contract_id = c.id)
                   AND NOT EXISTS (
                       SELECT 1 FROM installments i3
                       WHERE i3.contract_id = c.id
                         AND (i3.status != 'paid' OR i3.status IS NULL) AND i3.paid_at IS NULL
                   )
                ) AS residual_all_paid
        ");

        $payBase = '/admin/installments';
        $payFilters = [];

        if ($year) {
            $payFilters['academic_year'] = ['value' => $year];
        }

        $payStatusUrl = fn (string $status) => $this->tableUrl($payBase, $payFilters + [
            'status' => ['value' => $status],
        ]);

        $payAnomalyUrl = fn (string $anomaly) => $this->tableUrl($payBase, $payFilters + [
            'anomaly' => ['value' => $anomaly],
        ]);

        return [
            $this->item(
                $row->overdue > 0 ? 'danger' : 'success',
                'Rate scadute non pagate',
                (int) $row->overdue,
                'Esistono rate con data di scadenza già passata che non risultano pagate. Rischio di mancati incassi.',
                'Vai in Scadenze e pagamenti → filtra per stato "Scadute" → contatta il cliente e registra il pagamento o pianifica un sollecito.',
                $row->overdue > 0 ? $payStatusUrl('overdue') : null
            ),
            $this->item(
                $row->due_soon > 0 ? 'warning' : 'success',
                'Rate in scadenza nei prossimi 7 giorni',
                (int) $row->due_soon,
                'Queste rate scadono a breve. È utile inviare un promemoria al cliente prima della scadenza.',
                'Vai in Scadenze e pagamenti → filtra per stato "In scadenza 7 giorni" → invia comunicazione e prepara la registrazione del pagamento.',
                $row->due_soon > 0 ? $payStatusUrl('due_7') : null
            ),
            $this->item(
                $row->no_amount > 0 ? 'warning' : 'success',
                'Rate senza importo impostato',
                (int) $row->no_amount,
                'Rate senza importo non contribuiscono ai report finanziari e non possono essere usate per i solleciti automatici.',
                'Apri ogni rata interessata in Scadenze e pagamenti e imposta l\'importo corretto.',
                $row->no_amount > 0 ? $payAnomalyUrl('no_amount') : null
            ),
            $this->item(
                $row->no_due_date > 0 ? 'warning' : 'success',
                'Rate senza data di scadenza',
                (int) $row->no_due_date,
                'Senza data di scadenza le rate non appaiono nelle liste "in scadenza" e non attivano i promemoria.',
                'Apri ogni rata e imposta la data di scadenza prevista.',
                $row->no_due_date > 0 ? $payAnomalyUrl('no_due_date') : null
            ),
            $this->item(
                $row->residual_all_paid > 0 ? 'warning' : 'success',
                'Contratti con residuo ma tutte le rate pagate',
                (int) $row->residual_all_paid,
                'Il totale delle rate pagate non copre il residuo del contratto. Potrebbe mancare una rata o l\'importo di una rata è errato.',
                'Verifica il contratto: controlla la somma delle rate vs il prezzo del contratto. Potrebbe essere necessario aggiungere una rata mancante o correggere gli importi.',
                $row->residual_all_paid > 0 ? $this->tableUrl('/admin/contracts', array_filter([
                    'academic_year' => $year ? ['value' => $year] : null,
                    'anomaly' => ['value' => 'residual_all_paid'],
                ])) : null
            ),
        ];
    }

    /* ================================================================== */
    /*  HELPER                                                             */
    /* ================================================================== */

    protected function item(
        string  $severity,
        string  $label,
        int     $count,
        ?string $description,
        ?string $howToFix,
        ?string $url = null
    ): array {
        return compact('severity', 'label', 'count', 'description', 'howToFix', 'url');
    }

    protected function tableUrl(string $base, array $filters): string
    {
        return $base . '?' . http_build_query(['tableFilters' => $filters]);
    }

    public function getTotalIssues(): int
    {
        $total = 0;
        foreach ($this->results as $section) {
            foreach ($section as $item) {
                if (in_array($item['severity'], ['danger', 'warning']) && $item['count'] > 0) {
                    $total++;
                }
            }
        }
        return $total;
    }

    public function getSectionLabel(string $key): string
    {
        return match ($key) {
            'lezioni'   => '📅 Lezioni',
            'contratti' => '📄 Contratti',
            'docenti'   => '👨‍🏫 Docenti',
            'studenti'  => '👥 Studenti',
            'rate'      => '💶 Rate e pagamenti',
            default     => $key,
        };
    }

    public function getSectionIssueCount(array $section): int
    {
        return collect($section)->filter(
            fn ($i) => in_array($i['severity'], ['danger', 'warning']) && $i['count'] > 0
        )->count();
    }
}
