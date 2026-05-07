<?php

namespace App\Models;

use App\Models\Contract;
use App\Models\ContractLessonSlot;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContractStudent extends Model
{
    protected $table = 'contract_students';

    protected $fillable = [
        'contract_id',
        'student_id',

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

        'assigned_hours',

        // legacy / compatibilità
        'weekly_day',
        'weekly_time',
        'duration_minutes',
        'teacher_id',

        'meet_url',
        'notes',
    ];

    protected $casts = [
        'weekly_day'             => 'integer',
        'duration_minutes'       => 'integer',
        'beneficiary_birth_date' => 'date',
        'assigned_hours'         => 'decimal:2',
    ];

    protected $appends = [
        'beneficiary_full_name',
    ];

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

    protected static function booted(): void
    {
        static::saving(function (self $cs) {
            // normalizza e valida assigned_hours lato server
            if ($cs->assigned_hours !== null && $cs->assigned_hours !== '') {
                $value = round((float) $cs->assigned_hours, 2);
                // Valore minimo 0.5 h (coerente con la validazione del form Filament)
                // Null = non configurato; 0 esplicito = zero ore; valori < 0.5 vengono azzerati a null
                if ($value <= 0) {
                    $cs->assigned_hours = null;
                } elseif ($value < 0.5) {
                    $cs->assigned_hours = 0.5; // arrotonda al minimo consentito
                } else {
                    $cs->assigned_hours = $value;
                }
            } else {
                $cs->assigned_hours = null;
            }

            // Se già c'è student_id, aggiorna i campi mancanti nell'anagrafica studente
            // (es. data/luogo di nascita inseriti dall'operatore nel form contratto)
            // senza sovrascrivere dati già presenti.
            if (! empty($cs->student_id)) {
                $student = Student::find((int) $cs->student_id);

                if ($student) {
                    $dirty = false;

                    $birthDate = $cs->beneficiary_birth_date
                        ? $cs->beneficiary_birth_date->toDateString()
                        : null;

                    if (! empty($birthDate) && empty($student->birth_date)) {
                        $student->birth_date = $birthDate;
                        $dirty = true;
                    }

                    if (! empty($cs->beneficiary_birth_place) && empty($student->birth_place)) {
                        $student->birth_place = $cs->beneficiary_birth_place;
                        $dirty = true;
                    }

                    if (! empty($cs->beneficiary_birth_province) && empty($student->birth_province)) {
                        $student->birth_province = $cs->beneficiary_birth_province;
                        $dirty = true;
                    }

                    if (! empty($cs->beneficiary_birth_country) && empty($student->birth_country)) {
                        $student->birth_country = $cs->beneficiary_birth_country;
                        $dirty = true;
                    }

                    if (! empty($cs->beneficiary_phone) && empty($student->phone)) {
                        $student->phone = $cs->beneficiary_phone;
                        $dirty = true;
                    }

                    if (! empty($cs->beneficiary_email) && empty($student->email)) {
                        $student->email = Str::lower(trim((string) $cs->beneficiary_email));
                        $dirty = true;
                    }

                    if ($dirty) {
                        $student->save();
                    }
                }

                return;
            }

            $first = trim((string) ($cs->beneficiary_first_name ?? ''));
            $last  = trim((string) ($cs->beneficiary_last_name ?? ''));
            $email = Str::of((string) ($cs->beneficiary_email ?? ''))->lower()->squish()->toString();
            $phone = preg_replace('/\s+/', '', trim((string) ($cs->beneficiary_phone ?? '')));

            $firstNorm = Str::of($first)->lower()->squish()->toString();
            $lastNorm  = Str::of($last)->lower()->squish()->toString();

            $birthDate = $cs->beneficiary_birth_date
                ? $cs->beneficiary_birth_date->toDateString()
                : null;

            if ($firstNorm === '' && $lastNorm === '' && $email === '' && $phone === '') {
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

            if (! $student && $firstNorm !== '' && $lastNorm !== '' && $birthDate) {
                $student = Student::query()
                    ->whereRaw('LOWER(COALESCE(first_name,"")) = ?', [$firstNorm])
                    ->whereRaw('LOWER(COALESCE(last_name,"")) = ?', [$lastNorm])
                    ->whereDate('birth_date', $birthDate)
                    ->first();
            }

            if (! $student && $firstNorm !== '' && $lastNorm !== '') {
                $student = Student::query()
                    ->whereRaw('LOWER(COALESCE(first_name,"")) = ?', [$firstNorm])
                    ->whereRaw('LOWER(COALESCE(last_name,"")) = ?', [$lastNorm])
                    ->orderByDesc('id')
                    ->first();
            }

            if (! $student) {
                $student = Student::create([
                    'first_name'     => $first !== '' ? $first : null,
                    'last_name'      => $last !== '' ? $last : null,
                    'email'          => $email !== '' ? $email : null,
                    'phone'          => $phone !== '' ? $phone : null,
                    'birth_date'     => $birthDate ?: null,

                    'birth_place'    => $cs->beneficiary_birth_place ?: null,
                    'birth_province' => $cs->beneficiary_birth_province ?: null,
                    'birth_country'  => $cs->beneficiary_birth_country ?: null,

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

                if (empty($student->birth_date) && $birthDate) {
                    $student->birth_date = $birthDate;
                    $dirty = true;
                }

                if (empty($student->birth_place) && ! empty($cs->beneficiary_birth_place)) {
                    $student->birth_place = $cs->beneficiary_birth_place;
                    $dirty = true;
                }

                if (empty($student->birth_province) && ! empty($cs->beneficiary_birth_province)) {
                    $student->birth_province = $cs->beneficiary_birth_province;
                    $dirty = true;
                }

                if (empty($student->birth_country) && ! empty($cs->beneficiary_birth_country)) {
                    $student->birth_country = $cs->beneficiary_birth_country;
                    $dirty = true;
                }

                if ($dirty) {
                    $student->save();
                }
            }

            $cs->student_id = $student->id;
        });

        static::saved(function (self $cs) {
            DB::afterCommit(function () use ($cs) {
                $contractId = (int) ($cs->contract_id ?? 0);

                if ($contractId <= 0) {
                    return;
                }

                // Compatibilità legacy:
                // se il vecchio record ha weekly_day/weekly_time valorizzati,
                // manteniamo il sync verso contract_lesson_slots.
                // Nel nuovo flusso gli slot vengono gestiti dal RelationManager.
                $lock = Cache::lock("contract_student:{$cs->id}:sync_slot", 10);

                if (! $lock->get()) {
                    return;
                }

                try {
                    if (! $cs->student_id || ! $cs->weekly_day || ! $cs->weekly_time) {
                        return;
                    }

                    $time = self::normalizeTime($cs->weekly_time);

                    if (! $time) {
                        return;
                    }

                    $contract = Contract::query()->find($contractId);

                    // withoutEvents: evita che ContractLessonSlotObserver scheduli
                    // una rigenerazione lezioni in parallelo. Il ramo Contract::saved()
                    // gestisce già la rigenerazione principale; una seconda chiamata a
                    // generateForContract() causerebbe Duplicate entry su uniq_lesson_slot.
                    // Nota: withoutEvents() è il metodo corretto in Eloquent (withoutObservers non esiste).
                    ContractLessonSlot::withoutEvents(function () use ($contractId, $cs, $time, $contract) {
                        ContractLessonSlot::updateOrCreate(
                            [
                                'contract_id' => $contractId,
                                'student_id'  => (int) $cs->student_id,
                                'weekly_day'  => (int) $cs->weekly_day,
                                'weekly_time' => $time,
                            ],
                            [
                                'teacher_id'       => $cs->teacher_id ?: null,
                                'duration_minutes' => max(1, (int) ($cs->duration_minutes ?? 60)),
                                'is_active'        => true,
                                'starts_at'        => $contract?->starts_at?->toDateString(),
                                'ends_at'          => null,
                                'meet_url'         => $cs->meet_url,
                            ]
                        );
                    });
                } finally {
                    optional($lock)->release();
                }
            });
        });
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function getBeneficiaryFullNameAttribute(): string
    {
        return trim(($this->beneficiary_first_name ?? '') . ' ' . ($this->beneficiary_last_name ?? ''));
    }
}
