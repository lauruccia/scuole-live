<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Lesson extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'contract_id',
        'contract_student_id',
        'student_id',
        'teacher_id',
        'starts_at',
        'ends_at',
        'duration_minutes',

        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',

        'counts_as_consumed',
        'is_recoverable',

        'meet_url',
        'google_event_id',
        'google_calendar_id',
        'lesson_number',

        'notes',
        'homework',

        'completed_at',
        'completed_by',

        'recovery_of_lesson_id',
        'is_auto_recovery',

        'language_id',
    ];

    protected $casts = [
        'starts_at'          => 'datetime',
        'ends_at'            => 'datetime',
        'cancelled_at'       => 'datetime',
        'counts_as_consumed' => 'boolean',
        'is_recoverable'     => 'boolean',
        'is_auto_recovery'   => 'boolean',
        'duration_minutes'   => 'integer',
        'completed_at'       => 'datetime',
        'language_id' => 'string',
    ];

    /* RELATIONS */

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function contractStudent(): BelongsTo
    {
        return $this->belongsTo(ContractStudent::class, 'contract_student_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function originalLesson(): BelongsTo
    {
        return $this->belongsTo(self::class, 'recovery_of_lesson_id');
    }

    public function recoveryLesson(): HasOne
    {
        return $this->hasOne(self::class, 'recovery_of_lesson_id');
    }

    /* HELPERS */

    public function isCompleted(): bool
    {
        return ! is_null($this->completed_at);
    }

    public function isCancelled(): bool
    {
        return ! is_null($this->cancelled_at);
    }

    public function consumptionHours(): float
    {
        return static::computeLessonHours(
            $this->duration_minutes,
            $this->starts_at,
            $this->ends_at
        );
    }

    /**
     * Metodo statico condiviso per calcolare le ore di una lezione.
     * Usato sia da consumptionHours() che da Contract::recalcConsumedHours()
     * per garantire un calcolo coerente ovunque.
     *
     * @param  int|null        $durationMinutes  Durata esplicita in minuti (fonte autoritativa)
     * @param  mixed           $startsAt         DateTime inizio (Carbon, stringa o null)
     * @param  mixed           $endsAt           DateTime fine (Carbon, stringa o null)
     * @return float           Ore (arrotondate a 2 decimali, minimo 1 minuto)
     */
    public static function computeLessonHours($durationMinutes, $startsAt, $endsAt): float
    {
        // duration_minutes è la fonte autoritativa (configurata dallo slot).
        $mins = (int) ($durationMinutes ?? 0);

        // Fallback: calcola dalla differenza oraria reale solo se duration_minutes manca
        if ($mins <= 0 && $startsAt && $endsAt) {
            $diff = Carbon::parse($startsAt)
                ->diffInMinutes(Carbon::parse($endsAt), false);

            if ($diff > 0) {
                $mins = $diff;
            }
        }

        // Fallback finale: 60 minuti se nessun dato disponibile
        if ($mins <= 0) {
            $mins = 60;
        }

        return round(max(1, $mins) / 60, 2);
    }

    public function recomputeFlags(?Carbon $cancelledAt = null): void
    {
        $cancelledAt = $cancelledAt ?: $this->cancelled_at;

        // COMPLETATA → consuma sempre
        if ($this->isCompleted()) {
            $this->is_recoverable = false;
            $this->counts_as_consumed = true;
            return;
        }

        // ANNULLATA → regola 24h
        if ($cancelledAt) {
            if (! $this->starts_at) {
                $this->is_recoverable = false;
                $this->counts_as_consumed = true;
                return;
            }

            $startsAt = Carbon::parse($this->starts_at);

            $isRecoverable = $cancelledAt
                ->lte($startsAt->copy()->subHours(24));

            $this->is_recoverable = $isRecoverable;
            $this->counts_as_consumed = ! $isRecoverable;
            return;
        }

        // Né completata né annullata
        $this->is_recoverable = false;
        $this->counts_as_consumed = false;
    }

    protected static function booted(): void
    {
        static::saving(function (self $lesson) {
            // Default lingua lezione dal contratto, se non impostata
            if (empty($lesson->language_id) && $lesson->contract_id) {
                $contract = $lesson->relationLoaded('contract')
                    ? $lesson->contract
                    : \App\Models\Contract::query()->select('id', 'language_id')->find($lesson->contract_id);

                if ($contract && ! empty($contract->language_id)) {
                    $lesson->language_id = $contract->language_id;
                }
            }

            // Non possono coesistere completata + annullata
            if ($lesson->completed_at && $lesson->cancelled_at) {
                $lesson->cancelled_at    = null;
                $lesson->cancelled_by    = null;
                $lesson->cancellation_reason = null;
            }

            $lesson->recomputeFlags();
        });

        // NOTA: il ricalcolo di Contract::recalcConsumedHours() è gestito esclusivamente
        // da LessonObserver (con $afterCommit = true), che include anche il controllo
        // sui campi rilevanti e l'invio delle email di cancellazione.
        // NON duplicare la chiamata qui per evitare doppio ricalcolo.
    }


}
