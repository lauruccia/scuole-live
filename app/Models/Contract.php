<?php

namespace App\Models;

use App\Models\BillingProfile;
use App\Models\Company;
use App\Models\ContractLessonSlot;
use App\Models\ContractStudent;
use App\Models\Course;
use App\Models\Installment;
use App\Models\Lesson;
use App\Models\Student;
use App\Services\LessonGeneratorService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Contract extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'billing_type',
        'billing_is_beneficiary',
        'billing_is_student',

        // Privato
        'billing_first_name',
        'billing_last_name',
        'billing_email',
        'billing_phone',
        'billing_address',
        'billing_city',
        'billing_zip',
        'billing_country',
        'billing_tax_code',
        'billing_birth_date',
        'billing_birth_place',
        'billing_province',

        // Privato (professionista)
        'billing_vat_number',
        'billing_sdi',
        'billing_pec',

        // Azienda (campi "storici" su contracts)
        'company_name',
        'vat_number',
        'sdi',
        'pec',
        'company_email',
        'company_phone',
        'company_address',
        'company_city',
        'company_zip',
        'company_country',
        'company_province',

        // Corso
        'course_id',
        'language_id',
        'lesson_type',
        'admission_date',
        'starts_at',
        'ends_at',

        // Pagamenti
        'course_price',
        'enrollment_fee',
        'deposit',
        'payment_mode',
        'installments_count',
        'first_installment_date',

        // Ore
        'hours_purchased',
        'hours_full',
        'hours_consumed',

        'notes',
        'academic_year',
        'status',

        // Nuovo sistema
        'company_id',
        'billing_profile_id',

        'languages',

        // Firma digitale
        'signature_otp',
        'signature_otp_expires_at',
        'signature_otp_attempts',
        'signed_at',
        'signed_ip',
        'signed_user_agent',
    ];

    protected $casts = [
        'billing_is_beneficiary' => 'boolean',
        'billing_is_student'     => 'boolean',

        'billing_birth_date'     => 'date',
        'admission_date'         => 'date',
        'starts_at'              => 'date',
        'ends_at'                => 'date',
        'first_installment_date' => 'date',

        'course_price'           => 'decimal:2',
        'enrollment_fee'         => 'decimal:2',
        'deposit'                => 'decimal:2',

        'hours_purchased'              => 'decimal:2',
        'hours_full'                   => 'decimal:2',
        'hours_consumed'               => 'decimal:2',

        'languages'                    => 'array',

        // Firma digitale
        'signature_otp_expires_at'     => 'datetime',
        'signature_otp_attempts'       => 'integer',
        'signed_at'                    => 'datetime',
    ];

    protected $appends = [
        'billing_display_name',
        'total',
        'residual',
        'hours_remaining',
    ];

    // ─── Activity Log ─────────────────────────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('contracts')
            ->logOnly([
                'status',
                'billing_first_name', 'billing_last_name', 'billing_email',
                'company_name',
                'course_id', 'course_price', 'enrollment_fee', 'deposit',
                'payment_mode', 'installments_count',
                'hours_purchased', 'hours_consumed',
                'starts_at', 'ends_at',
                'academic_year',
                'signed_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName): string {
                $name = trim(($this->billing_first_name ?? '') . ' ' . ($this->billing_last_name ?? ''))
                     ?: ($this->company_name ?? "Contratto #{$this->id}");

                return match ($eventName) {
                    'created' => "Contratto #{$this->id} creato — {$name}",
                    'updated' => "Contratto #{$this->id} aggiornato — {$name}",
                    'deleted' => "Contratto #{$this->id} eliminato — {$name}",
                    default   => "Contratto #{$this->id} — {$eventName}",
                };
            });
    }

    /* -----------------------------------------------------------------
     |  RELATIONS
     | ----------------------------------------------------------------- */

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'contract_id');
    }

    public function beneficiaries(): HasMany
    {
        return $this->hasMany(ContractStudent::class, 'contract_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'contract_students', 'contract_id', 'student_id')
            ->withPivot([
                'teacher_id',
                'weekly_day',
                'weekly_time',
                'meet_url',

                'beneficiary_first_name',
                'beneficiary_last_name',
                'beneficiary_email',
                'beneficiary_phone',

                'beneficiary_birth_date',
                'beneficiary_birth_place',
                'beneficiary_birth_province',
                'beneficiary_birth_country',
                'beneficiary_address',
                'beneficiary_city',
                'beneficiary_zip',
                'beneficiary_country',
            ])
            ->withTimestamps();
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(CourseMaterial::class, 'contract_course_material')
                    ->withPivot('is_visible', 'assigned_at')
                    ->orderByPivot('assigned_at', 'desc');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class, 'contract_id');
    }

    public function lessonSlots(): HasMany
    {
        return $this->hasMany(ContractLessonSlot::class, 'contract_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function billingProfile(): BelongsTo
    {
        return $this->belongsTo(BillingProfile::class);
    }

    /* -----------------------------------------------------------------
     |  COMPUTED
     | ----------------------------------------------------------------- */

    public function getTotalAttribute(): float
    {
        return (float) $this->course_price + (float) $this->enrollment_fee;
    }

    public function getResidualAttribute(): float
    {
        return max(0, $this->total - (float) $this->deposit);
    }

    /** Ore personalizzate (slot fisso, auto-generate) = totale - full */
    public function getHoursPersonalAttribute(): float
    {
        return max(0.0, (float) $this->hours_purchased - (float) ($this->hours_full ?? 0));
    }

    public function getHoursRemainingAttribute(): float
    {
        return max(0, (float) $this->hours_purchased - (float) $this->hours_consumed);
    }

    public function getBillingDisplayNameAttribute(): string
    {
        if ($this->relationLoaded('billingProfile') ? $this->billingProfile : $this->billingProfile()->exists()) {
            return (string) ($this->billingProfile?->display_name ?? '—');
        }

        if (($this->billing_type ?? 'private') === 'company') {
            return (string) ($this->company_name ?: '—');
        }

        $first = trim((string) ($this->billing_first_name ?? ''));
        $last  = trim((string) ($this->billing_last_name ?? ''));

        $full = trim($last . ' ' . $first);

        return $full !== '' ? $full : '—';
    }

    public function isPrivate(): bool
    {
        return ($this->billing_type ?? 'private') !== 'company';
    }

    public function isCompany(): bool
    {
        return ($this->billing_type ?? 'private') === 'company';
    }

    // ─── Notifiche email: routing recipient ───────────────────────────────────

    /**
     * Restituisce l'intestatario del contratto come recipient.
     * Se l'email di fatturazione non è valida, restituisce null.
     *
     * @return array{email:string,name:string}|null
     */
    public function holderRecipient(): ?array
    {
        $email = trim((string) ($this->billing_email ?? ''));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $name = trim(($this->billing_first_name ?? '') . ' ' . ($this->billing_last_name ?? ''));

        if ($name === '') {
            $name = trim((string) ($this->company_name ?? '')) ?: $email;
        }

        return ['email' => $email, 'name' => $name];
    }

    /**
     * Recipients per notifiche a livello di contratto (rate, scadenza, generali).
     *
     * Logica:
     *  - To:  intestatario (billing_email), se valido.
     *         Se non valido, usa il primo studente con email valida.
     *  - CC:  tutti gli studenti con email valida ≠ To.
     *
     * Ritorna ['to' => [...], 'cc' => [...]] dove:
     *   - 'to' è null se non esiste nessun indirizzo valido
     *   - 'cc' è un array (eventualmente vuoto) di ['email', 'name']
     *
     * @return array{to: array{email:string,name:string}|null, cc: list<array{email:string,name:string}>}
     */
    public function contractNotificationRecipients(): array
    {
        // Assicura che gli studenti siano caricati (evita N+1 in loop)
        if (! $this->relationLoaded('students')) {
            $this->load('students');
        }

        $holder      = $this->holderRecipient();
        $holderEmail = $holder ? strtolower($holder['email']) : null;

        // Raccoglie tutti i recipient degli studenti con email valida
        $studentRecipients = $this->students
            ->filter(fn ($s) => filter_var(trim((string) ($s->email ?? '')), FILTER_VALIDATE_EMAIL))
            ->map(fn ($s) => [
                'email' => trim((string) $s->email),
                'name'  => $s->full_name ?: trim((string) $s->email),
            ])
            ->unique('email')
            ->values();

        if ($holder) {
            // Caso normale: To = intestatario, CC = studenti con email diversa
            $cc = $studentRecipients
                ->filter(fn ($r) => strtolower($r['email']) !== $holderEmail)
                ->values()
                ->all();

            return ['to' => $holder, 'cc' => $cc];
        }

        // Intestatario senza email valida: usiamo il primo studente come To, resto in CC
        if ($studentRecipients->isNotEmpty()) {
            $to  = $studentRecipients->first();
            $cc  = $studentRecipients->skip(1)->values()->all();

            return ['to' => $to, 'cc' => $cc];
        }

        return ['to' => null, 'cc' => []];
    }

    /**
     * Recipients per notifiche a livello di singola lezione (cancellazione, recupero).
     *
     * Logica:
     *  - To:  lo studente, se ha email valida.
     *         Altrimenti fallback all'intestatario.
     *  - CC:  l'intestatario, se ha email valida e diversa da quella dello studente.
     *
     * @param  Student  $student  Lo studente coinvolto nella lezione
     * @return array{to: array{email:string,name:string}|null, cc: list<array{email:string,name:string}>}
     */
    public function lessonNotificationRecipients(Student $student): array
    {
        $holder      = $this->holderRecipient();
        $holderEmail = $holder ? strtolower($holder['email']) : null;

        $studentEmail = trim((string) ($student->email ?? ''));

        if (filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {
            $to = [
                'email' => $studentEmail,
                'name'  => $student->full_name ?: $studentEmail,
            ];

            $cc = ($holder && strtolower($studentEmail) !== $holderEmail)
                ? [$holder]
                : [];

            return ['to' => $to, 'cc' => $cc];
        }

        // Studente senza email valida → intestatario come To, nessun CC
        return ['to' => $holder, 'cc' => []];
    }

    /**
     * Ricalcola ore consumate leggendo le lezioni "counts_as_consumed = 1"
     */
    public static function recalcConsumedHours(int $contractId): void
    {
        // Nessun DB::transaction(): questo metodo viene sempre invocato
        // da callback DB::afterCommit() (in LessonObserver), quindi siamo
        // già fuori da qualsiasi transazione aperta. Un wrapper aggiuntivo
        // non aggiunge atomicità ma introduce overhead e potenziali lock.
        $lessons = DB::table('lessons')
            ->where('contract_id', $contractId)
            ->where('counts_as_consumed', 1)
            ->get(['starts_at', 'ends_at', 'duration_minutes']);

        $sum = 0.0;

        foreach ($lessons as $l) {
            // Usa il metodo centralizzato in Lesson per garantire
            // un calcolo identico a Lesson::consumptionHours()
            $sum += Lesson::computeLessonHours(
                $l->duration_minutes,
                $l->starts_at,
                $l->ends_at
            );
        }

        DB::table('contracts')
            ->where('id', $contractId)
            ->update([
                'hours_consumed' => round($sum, 2),
                'updated_at'     => now(),
            ]);
    }

    /* -----------------------------------------------------------------
     |  AUTO SYNC (AFTER COMMIT)
     | ----------------------------------------------------------------- */

    protected static function booted(): void
    {
        // ── Cascade SoftDelete ────────────────────────────────────────────────
        // Quando un contratto viene soft-deleted, soft-deleta anche tutte le sue
        // lezioni, così non compaiono come "orfane" in nessun listing o report.
        // Le installment NON vengono cascate: restano visibili per i report finanziari.
        static::deleting(function (self $contract) {
            // Soft delete solo se il modello usa SoftDeletes (non forceDelete)
            if (! $contract->isForceDeleting()) {
                $contract->lessons()->whereNull('deleted_at')->update([
                    'deleted_at' => now(),
                ]);
            }
        });

        // ── Cascade Restore ───────────────────────────────────────────────────
        // Se un contratto viene ripristinato, ripristina anche le lezioni
        // che erano state soft-deleted assieme a lui nello stesso istante.
        static::restored(function (self $contract) {
            $contract->lessons()->onlyTrashed()->update([
                'deleted_at' => null,
            ]);
        });

        static::saving(function (self $contract) {
            // Validazione server-side date: fine deve essere dopo inizio
            // Usiamo un'eccezione generica (non ValidationException) perché
            // siamo nel model layer — Filament e il ContractResource gestiscono
            // questa eccezione e la mostrano come notifica di errore.
            if ($contract->starts_at && $contract->ends_at) {
                $start = Carbon::parse($contract->starts_at)->startOfDay();
                $end   = Carbon::parse($contract->ends_at)->startOfDay();

                if ($end->lt($start)) {
                    throw new \InvalidArgumentException(
                        'La data di fine corso non può essere precedente alla data di inizio.'
                    );
                }
            }

            if ($contract->isCompany()) {
                $contract->billing_is_student = false;
                $contract->billing_is_beneficiary = false;
                return;
            }

            if (! is_null($contract->billing_is_student)) {
                $contract->billing_is_beneficiary = (bool) $contract->billing_is_student;
            }

            // normalizza lingue già in saving
            $langs = $contract->languages ?? [];
            $langs = is_array($langs) ? $langs : (json_decode($langs ?: '[]', true) ?: []);
            $langs = array_values(array_unique(array_filter($langs, fn ($v) => filled($v))));

            $contract->languages = $langs;

            if (! empty($langs)) {
                $contract->language_id = $langs[0];
            }
        });

        static::saved(function (self $contract) {
            // ✅ IMPORTANTISSIMO: prendo i changes PRIMA del reload
            $changes = array_keys($contract->getChanges());

            DB::afterCommit(function () use ($contract, $changes) {
                $contractId = (int) $contract->getKey();

                $lock = Cache::lock("contract_post_save_pipeline:{$contractId}", 60);
                if (! $lock->get()) {
                    return;
                }

                try {
                    /** @var self|null $fresh */
                    $fresh = self::query()
                        ->with('beneficiaries')
                        ->find($contractId);

                    if (! $fresh) {
                        return;
                    }

                    // Se il contratto è appena passato a "completato":
                    // disattiva TUTTI gli slot attivi (nessuna lezione potrà più essere generata)
                    // e salta la sincronizzazione + rigenerazione per evitare effetti collaterali.
                    if (in_array('status', $changes, true) && $fresh->status === 'completed') {
                        DB::table('contract_lesson_slots')
                            ->where('contract_id', $contractId)
                            ->where('is_active', true)
                            ->update([
                                'is_active'  => false,
                                'ends_at'    => $fresh->ends_at ? Carbon::parse($fresh->ends_at)->toDateString() : now()->toDateString(),
                                'updated_at' => now(),
                            ]);

                        return;
                    }

                    // 1) PRIVATO + intestatario coincide con studente
                    $isBillingStudent =
                        (bool) ($fresh->billing_is_student ?? false) ||
                        (bool) ($fresh->billing_is_beneficiary ?? false);

                    if ($fresh->isPrivate() && $isBillingStudent) {
                        retry(3, function () use ($fresh) {
                            self::syncBillingBeneficiaryStudent($fresh);
                        }, 250);

                        $fresh->load('beneficiaries');
                    }

                    // 2) sync slot dai beneficiari
                    retry(3, function () use ($fresh) {
                        self::syncLessonSlotsFromBeneficiaries($fresh);
                    }, 250);

                    // 3) genera / rigenera lezioni solo se serve davvero
                    $ignore = [
                        'hours_consumed',
                        'ends_at',
                        'updated_at',
                    ];

                    $onlyIgnored = ! empty($changes)
                        && collect($changes)->every(fn ($f) => in_array($f, $ignore, true));

                    if ($onlyIgnored) {
                        return;
                    }

                    $regenOn = [
                        'starts_at',
                        'course_id',
                        'language_id',
                        'lesson_type',
                        'languages',
                        // Cambiare le ore acquistate modifica quante lezioni vanno generate:
                        // la rigenerazione (non forzata) aggiunge le lezioni mancanti.
                        'hours_purchased',
                        // Cambiare hours_full ridistribuisce le ore personalizzate:
                        // es. da 10h full=0 a 10h full=4 → hours_personal scende da 10 a 6
                        // → le lezioni personalizzate già generate sono in eccesso.
                        'hours_full',
                    ];

                    $shouldRegen = empty($changes)
                        ? false
                        : collect($changes)->intersect($regenOn)->isNotEmpty();

                    if ($shouldRegen && $fresh->starts_at) {
                        $hasAnySlot = ContractLessonSlot::query()
                            ->where('contract_id', $fresh->id)
                            ->where('is_active', true)
                            ->whereNotNull('student_id')
                            ->exists();

                        if ($hasAnySlot) {
                            app(LessonGeneratorService::class)->generateForContract($fresh, false);
                        }
                    }
                } finally {
                    optional($lock)->release();
                }
            });
        });
    }

    /**
     * Normalizza TIME: "17:00" -> "17:00:00"
     */
    protected static function normalizeTime(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $t = trim((string) $value);
        if ($t === '') {
            return null;
        }

        if (preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $t)) {
            return Carbon::createFromFormat('H:i:s', $t)->format('H:i:s');
        }

        if (preg_match('/^\d{1,2}:\d{2}$/', $t)) {
            return Carbon::createFromFormat('H:i', $t)->format('H:i:s');
        }

        try {
            return Carbon::parse($t)->format('H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    protected static function syncLessonSlotsFromBeneficiaries(self $contract): void
    {
        $contract->loadMissing(['beneficiaries']);

        foreach ($contract->beneficiaries as $cs) {
            if (! $cs->student_id) {
                continue;
            }

            if (! $cs->weekly_day || ! $cs->weekly_time) {
                continue;
            }

            $time = self::normalizeTime($cs->weekly_time);
            if (! $time) {
                continue;
            }

            // Usiamo withoutEvents per evitare che l'observer ContractLessonSlotObserver
            // scheduli una rigenerazione lezioni qui: il pipeline del Contract saved
            // gestisce già la rigenerazione a valle (riga ~605). Senza questa guardia,
            // entrambi i percorsi chiamerebbero generateForContract() producendo
            // lezioni duplicate (violazione unique key 'uniq_lesson_slot').
            // Nota: withoutEvents() è il metodo corretto in Eloquent (withoutObservers non esiste).
            ContractLessonSlot::withoutEvents(function () use ($contract, $cs, $time) {
                ContractLessonSlot::updateOrCreate(
                    [
                        'contract_id' => $contract->id,
                        'student_id'  => (int) $cs->student_id,
                        'weekly_day'  => (int) $cs->weekly_day,
                        'weekly_time' => $time,
                    ],
                    [
                        'teacher_id'       => $cs->teacher_id ?: null,
                        'duration_minutes' => max(1, (int) ($cs->duration_minutes ?? 60)),
                        'is_active'        => true,
                        'starts_at'        => $contract->starts_at ? Carbon::parse($contract->starts_at)->toDateString() : null,
                        'ends_at'          => null,
                        'meet_url'         => $cs->meet_url,
                    ]
                );
            });
        }

        $keep = $contract->beneficiaries
            ->filter(fn ($cs) => $cs->student_id && $cs->weekly_day && $cs->weekly_time)
            ->map(function ($cs) {
                $time = self::normalizeTime($cs->weekly_time);

                if (! $time) {
                    return null;
                }

                return (int) $cs->student_id . '|' . (int) $cs->weekly_day . '|' . $time;
            })
            ->filter()
            ->values()
            ->all();

        if (empty($keep)) {
            return;
        }

        $slots = ContractLessonSlot::query()
            ->where('contract_id', $contract->id)
            ->get();

        // Batch deactivation: un solo UPDATE invece di N query in loop
        $toDeactivate = $slots
            ->filter(function ($slot) use ($keep) {
                $time = self::normalizeTime($slot->weekly_time);
                $key  = (int) $slot->student_id . '|' . (int) $slot->weekly_day . '|' . ($time ?? '');

                return ! in_array($key, $keep, true);
            })
            ->pluck('id')
            ->all();

        if (! empty($toDeactivate)) {
            DB::table('contract_lesson_slots')
                ->whereIn('id', $toDeactivate)
                ->update(['is_active' => false, 'updated_at' => now()]);
        }
    }

    /**
     * Se intestatario = beneficiario (solo privato): crea/aggancia Student + ContractStudent
     */
    protected static function syncBillingBeneficiaryStudent(self $contract): void
    {
        $first = trim((string) ($contract->billing_first_name ?? ''));
        $last  = trim((string) ($contract->billing_last_name ?? ''));
        $email = Str::of((string) ($contract->billing_email ?? ''))->lower()->squish()->toString();
        $phone = preg_replace('/\s+/', '', trim((string) ($contract->billing_phone ?? '')));

        if ($first === '' && $last === '' && $email === '' && $phone === '') {
            return;
        }

        $student = null;

        if ($email !== '') {
            $student = Student::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();
        }

        if (! $student && $phone !== '') {
            $student = Student::query()
                ->whereRaw("REPLACE(COALESCE(phone,''),' ','') = ?", [$phone])
                ->first();
        }

        if (! $student && $first !== '' && $last !== '') {
            $q = Student::query()
                ->whereRaw('LOWER(COALESCE(first_name,"")) = ?', [Str::of($first)->lower()->toString()])
                ->whereRaw('LOWER(COALESCE(last_name,"")) = ?', [Str::of($last)->lower()->toString()]);

            if ($contract->billing_birth_date) {
                $q->whereDate('birth_date', $contract->billing_birth_date->toDateString());
            }

            $student = $q->orderByDesc('id')->first();
        }

        if (! $student) {
            $student = Student::create([
                'first_name'     => $first !== '' ? $first : null,
                'last_name'      => $last !== '' ? $last : null,
                'email'          => $email !== '' ? $email : null,
                'phone'          => $phone !== '' ? $phone : null,
                'birth_date'     => $contract->billing_birth_date?->toDateString(),
                'birth_place'    => $contract->billing_birth_place,
                'birth_province' => $contract->billing_province,
                'birth_country'  => $contract->billing_country,
                'is_minor'       => false,
            ]);
        } else {
            $dirty = false;

            if (empty($student->first_name) && $first !== '') {
                $student->first_name = $first;
                $dirty = true;
            }

            if (empty($student->last_name) && $last !== '') {
                $student->last_name = $last;
                $dirty = true;
            }

            if (empty($student->email) && $email !== '') {
                $student->email = $email;
                $dirty = true;
            }

            if (empty($student->phone) && $phone !== '') {
                $student->phone = $phone;
                $dirty = true;
            }

            if (empty($student->birth_date) && $contract->billing_birth_date) {
                $student->birth_date = $contract->billing_birth_date->toDateString();
                $dirty = true;
            }

            if (empty($student->birth_place) && ! empty($contract->billing_birth_place)) {
                $student->birth_place = $contract->billing_birth_place;
                $dirty = true;
            }

            if (empty($student->birth_province) && ! empty($contract->billing_province)) {
                $student->birth_province = $contract->billing_province;
                $dirty = true;
            }

            if (empty($student->birth_country) && ! empty($contract->billing_country)) {
                $student->birth_country = $contract->billing_country;
                $dirty = true;
            }

            if ($dirty) {
                $student->save();
            }
        }

        $cs = ContractStudent::query()
            ->where('contract_id', $contract->id)
            ->where('student_id', $student->id)
            ->first();

        if (! $cs) {
            $cs = ContractStudent::query()
                ->where('contract_id', $contract->id)
                ->orderBy('id')
                ->first() ?? new ContractStudent(['contract_id' => $contract->id]);
        }

        $cs->contract_id = $contract->id;
        $cs->student_id  = $student->id;

        $cs->beneficiary_first_name = $first !== '' ? $first : ($cs->beneficiary_first_name ?? null);
        $cs->beneficiary_last_name  = $last !== '' ? $last : ($cs->beneficiary_last_name ?? null);
        $cs->beneficiary_email      = $email !== '' ? $email : ($cs->beneficiary_email ?? null);
        $cs->beneficiary_phone      = $phone !== '' ? $phone : ($cs->beneficiary_phone ?? null);

        if (Schema::hasColumn('contract_students', 'beneficiary_birth_date') && $contract->billing_birth_date) {
            $cs->beneficiary_birth_date = $contract->billing_birth_date->toDateString();
        }

        if (Schema::hasColumn('contract_students', 'beneficiary_birth_place') && ! empty($contract->billing_birth_place)) {
            $cs->beneficiary_birth_place = $contract->billing_birth_place;
        }

        if (Schema::hasColumn('contract_students', 'beneficiary_birth_province') && ! empty($contract->billing_province)) {
            $cs->beneficiary_birth_province = $contract->billing_province;
        }

        if (Schema::hasColumn('contract_students', 'beneficiary_birth_country') && ! empty($contract->billing_country)) {
            $cs->beneficiary_birth_country = $contract->billing_country;
        }

        if (Schema::hasColumn('contract_students', 'beneficiary_address') && ! empty($contract->billing_address)) {
            $cs->beneficiary_address = $contract->billing_address;
        }

        if (Schema::hasColumn('contract_students', 'beneficiary_city') && ! empty($contract->billing_city)) {
            $cs->beneficiary_city = $contract->billing_city;
        }

        if (Schema::hasColumn('contract_students', 'beneficiary_zip') && ! empty($contract->billing_zip)) {
            $cs->beneficiary_zip = $contract->billing_zip;
        }

        if (Schema::hasColumn('contract_students', 'beneficiary_country') && ! empty($contract->billing_country)) {
            $cs->beneficiary_country = $contract->billing_country;
        }

        $cs->save();
    }

    public function getEnabledLanguages(): array
    {
        $langs = $this->languages;

        if (! is_array($langs) || empty($langs)) {
            return $this->language_id ? [$this->language_id] : [];
        }

        $langs = array_values(array_unique(array_filter(array_map(function ($v) {
            $v = is_string($v) ? trim($v) : $v;
            return $v !== '' ? $v : null;
        }, $langs))));

        if (empty($langs) && $this->language_id) {
            return [$this->language_id];
        }

        return $langs;
    }

    public function getDefaultLanguage(): ?string
    {
        $langs = $this->getEnabledLanguages();
        return $this->language_id ?: ($langs[0] ?? null);
    }

    public function getLanguagesAttribute($value): array
    {
        $arr = is_array($value) ? $value : (json_decode($value ?? '[]', true) ?: []);
        return array_values(array_filter($arr, fn ($v) => filled($v)));
    }

    /* -----------------------------------------------------------------
     |  FIRMA DIGITALE OTP
     | ----------------------------------------------------------------- */

    /** Il contratto e' gia' stato firmato digitalmente? */
    public function isSigned(): bool
    {
        return $this->signed_at !== null;
    }

    /** E' possibile firmare (firma abilitata globalmente e contratto non ancora firmato)? */
    public function isReadyToSign(): bool
    {
        return ! $this->isSigned()
            && SchoolSetting::isDigitalSignatureEnabled();
    }

    /** Genera e salva un nuovo OTP a 6 cifre, valido 15 minuti. Azzera i tentativi. */
    public function generateAndSaveOtp(): string
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->withoutTimestamps(function () use ($otp) {
            $this->update([
                'signature_otp'            => $otp,
                'signature_otp_expires_at' => now()->addMinutes(15),
                'signature_otp_attempts'   => 0,
            ]);
        });

        return $otp;
    }

    /** L'OTP e' ancora valido (non scaduto, tentativi < 5)? */
    public function isOtpValid(): bool
    {
        return $this->signature_otp !== null
            && $this->signature_otp_expires_at !== null
            && $this->signature_otp_expires_at->isFuture()
            && $this->signature_otp_attempts < 5;
    }

    /**
     * Verifica il codice inserito dallo studente.
     * Incrementa sempre i tentativi, restituisce true solo se corretto.
     */
    public function verifyOtp(string $code): bool
    {
        $this->withoutTimestamps(function () {
            $this->increment('signature_otp_attempts');
        });
        $this->refresh();

        if (! $this->isOtpValid()) {
            return false;
        }

        return hash_equals((string) $this->signature_otp, trim($code));
    }

    /** Marca il contratto come firmato e cancella i dati OTP. */
    public function markAsSigned(string $ip, string $userAgent): void
    {
        $this->withoutTimestamps(function () use ($ip, $userAgent) {
            $this->update([
                'signed_at'                => now(),
                'signed_ip'                => $ip,
                'signed_user_agent'        => $userAgent,
                'signature_otp'            => null,
                'signature_otp_expires_at' => null,
                'signature_otp_attempts'   => 0,
            ]);
        });
    }

    /**
     * Email a cui inviare l'OTP.
     * Prima scelta: billing_email del contratto. Fallback: primo studente associato.
     */
    public function getSignatureEmailAttribute(): ?string
    {
        if (filled($this->billing_email)) {
            return $this->billing_email;
        }

        return $this->students()->first()?->email;
    }
}
