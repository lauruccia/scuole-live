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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;


class Contract extends Model
{
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
        'hours_consumed',

        'notes',

        // Nuovo sistema
        'company_id',
        'billing_profile_id',

        'languages',
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

        'hours_purchased'        => 'integer',
        'hours_consumed'         => 'integer',

            'languages' => 'array',
    ];

    protected $appends = [
        'billing_display_name',
        'total',
        'residual',
        'hours_remaining',
    ];

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

    /**
     * Pivot rows (ContractStudent)
     */
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

    /**
     * Ricalcola ore consumate leggendo le lezioni "counts_as_consumed = 1"
     */
    public static function recalcConsumedHours(int $contractId): void
{
    DB::transaction(function () use ($contractId) {

        // Somma ore consumate: ceil(minuti/60), minimo 1 per lezione
        $lessons = DB::table('lessons')
            ->where('contract_id', $contractId)
            ->where('counts_as_consumed', 1)
            ->get(['starts_at', 'ends_at', 'duration_minutes']);

        $sum = 0;

        foreach ($lessons as $l) {
            $mins = null;

            if (!empty($l->starts_at) && !empty($l->ends_at)) {
                $mins = (int) (strtotime($l->ends_at) - strtotime($l->starts_at)) / 60;
            }

            if ($mins === null || $mins <= 0) {
                $mins = (int) ($l->duration_minutes ?? 60);
            }

            $mins = max(1, $mins);
            $sum += max(1, (int) ceil($mins / 60));
        }

        // ✅ update diretto: NON scatena observer/events del Contract
        DB::table('contracts')
            ->where('id', $contractId)
            ->update([
                'hours_consumed' => $sum,
                'updated_at'     => now(),
            ]);
    });
}

    /* -----------------------------------------------------------------
     |  AUTO SYNC (AFTER COMMIT)
     | ----------------------------------------------------------------- */

    protected static function booted(): void
    {
        // ✅ Allinea i due flag per evitare incoerenze (retrocompatibilità)
        static::saving(function (self $contract) {
            if ($contract->isCompany()) {
                $contract->billing_is_student = false;
                $contract->billing_is_beneficiary = false;
                return;
            }

            // Se l’interfaccia usa billing_is_student, teniamo billing_is_beneficiary coerente
            if (! is_null($contract->billing_is_student)) {
                $contract->billing_is_beneficiary = (bool) $contract->billing_is_student;
            }
        });

        static::saved(function (self $contract) {
            DB::afterCommit(function () use ($contract) {
                $contractId = (int) $contract->getKey();

                $lock = Cache::lock("contract_post_save_pipeline:{$contractId}", 30);
                if (! $lock->get()) {
                    return;
                }

                try {
                    /** @var self|null $fresh */
                    $fresh = self::query()->with('beneficiaries')->find($contractId);
                    if (! $fresh) {
                        return;
                    }

                    // ✅ 1) PRIVATO + intestatario coincide con studente
                    // (usa billing_is_student, e accetta anche billing_is_beneficiary per retro)
                    $isBillingStudent = (bool) ($fresh->billing_is_student ?? false) || (bool) ($fresh->billing_is_beneficiary ?? false);

                    if ($fresh->isPrivate() && $isBillingStudent) {
                        retry(3, function () use ($fresh) {
                            self::syncBillingBeneficiaryStudent($fresh);
                        }, 250);

                        $fresh->load('beneficiaries');
                    }

                    // 2) SEMPRE: sync slot dai beneficiari
                    retry(3, function () use ($fresh) {
                        self::syncLessonSlotsFromBeneficiaries($fresh);
                    }, 250);

                    // 3) auto-genera lezioni se ha starts_at e almeno uno slot attivo
                    // 3) auto-genera lezioni SOLO se il contratto ha cambiato campi che impattano la pianificazione
$changes = array_keys($fresh->getChanges());

// campi che NON devono mai scatenare rigenerazione
$ignore = [
    'hours_consumed',
    'ends_at',
    'updated_at',
];

// se ha cambiato solo campi ignorati, stop
$onlyIgnored = !empty($changes) && collect($changes)->every(fn ($f) => in_array($f, $ignore, true));
if ($onlyIgnored) {
    return;
}

// rigenera SOLO se ha cambiato uno dei campi "schedulazione"
$regenOn = [
    'starts_at',
    'course_id',
    'language_id',
    'lesson_type',
    'languages', // se ti serve per lingua lezioni generate
];

$shouldRegen = collect($changes)->intersect($regenOn)->isNotEmpty();

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

                // ✅ sync languages -> language_id (default = prima lingua)
$langs = $contract->languages ?? [];
$langs = array_values(array_unique(array_filter($langs, fn ($v) => filled($v))));
$contract->languages = $langs;

// default = prima lingua selezionata
$contract->language_id = $langs[0] ?? $contract->language_id ?? null;
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

            ContractLessonSlot::updateOrCreate(
                [
                    'contract_id' => $contract->id,
                    'student_id'  => (int) $cs->student_id,
                    'weekly_day'  => (int) $cs->weekly_day,
                    'weekly_time' => $time,
                ],
                [
                    'teacher_id'       => $cs->teacher_id ?: null,
                    'duration_minutes' => 60,
                    'is_active'        => true,
                    'starts_at'        => $contract->starts_at ? Carbon::parse($contract->starts_at)->toDateString() : null,
                    'ends_at'          => null,
                    'meet_url'         => $cs->meet_url,
                ]
            );
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

        foreach ($slots as $slot) {
            $time = self::normalizeTime($slot->weekly_time);
            $key  = (int) $slot->student_id . '|' . (int) $slot->weekly_day . '|' . ($time ?? '');

            if (! in_array($key, $keep, true)) {
                $slot->is_active = false;
                $slot->save();
            }
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

        if (! $student && $phone !== '' && Schema::hasColumn('students', 'phone')) {
            $student = Student::query()
                ->whereRaw("REPLACE(COALESCE(phone,''),' ','') = ?", [$phone])
                ->first();
        }

        if (! $student && $first !== '' && $last !== '') {
            $q = Student::query()
                ->whereRaw('LOWER(COALESCE(first_name,"")) = ?', [Str::of($first)->lower()->toString()])
                ->whereRaw('LOWER(COALESCE(last_name,"")) = ?', [Str::of($last)->lower()->toString()]);

            if ($contract->billing_birth_date && Schema::hasColumn('students', 'birth_date')) {
                $q->whereDate('birth_date', $contract->billing_birth_date->toDateString());
            }

            $student = $q->orderByDesc('id')->first();
        }

        if (! $student) {
            $student = Student::create([
                'first_name'     => $first !== '' ? $first : null,
                'last_name'      => $last  !== '' ? $last  : null,
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

            if (empty($student->first_name) && $first !== '') { $student->first_name = $first; $dirty = true; }
            if (empty($student->last_name)  && $last  !== '') { $student->last_name  = $last;  $dirty = true; }
            if (empty($student->email)      && $email !== '') { $student->email      = $email; $dirty = true; }

            if (Schema::hasColumn('students', 'phone') && empty($student->phone) && $phone !== '') {
                $student->phone = $phone; $dirty = true;
            }

            if (Schema::hasColumn('students', 'birth_date') && empty($student->birth_date) && $contract->billing_birth_date) {
                $student->birth_date = $contract->billing_birth_date->toDateString(); $dirty = true;
            }

            if (Schema::hasColumn('students', 'birth_place') && empty($student->birth_place) && ! empty($contract->billing_birth_place)) {
                $student->birth_place = $contract->billing_birth_place; $dirty = true;
            }

            if (Schema::hasColumn('students', 'birth_province') && empty($student->birth_province) && ! empty($contract->billing_province)) {
                $student->birth_province = $contract->billing_province; $dirty = true;
            }

            if (Schema::hasColumn('students', 'birth_country') && empty($student->birth_country) && ! empty($contract->billing_country)) {
                $student->birth_country = $contract->billing_country; $dirty = true;
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
        $cs->beneficiary_last_name  = $last  !== '' ? $last  : ($cs->beneficiary_last_name ?? null);
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

    // Se non c'è array valido, fallback su lingua singola
    if (!is_array($langs) || empty($langs)) {
        return $this->language_id ? [$this->language_id] : [];
    }

    // Normalizza
    $langs = array_values(array_unique(array_filter(array_map(function ($v) {
        $v = is_string($v) ? trim($v) : $v;
        return $v !== '' ? $v : null;
    }, $langs))));

    // Se array vuoto ma language_id c'è, fallback
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


// opzionale ma consigliato: garantisci sempre array
public function getLanguagesAttribute($value): array
{
    $arr = is_array($value) ? $value : (json_decode($value ?? '[]', true) ?: []);
    return array_values(array_filter($arr, fn($v) => filled($v)));
}


}
