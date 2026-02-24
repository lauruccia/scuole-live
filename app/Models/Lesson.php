<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lesson extends Model
{
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

        // ✅ NUOVI
        'recovery_of_lesson_id',
        'is_auto_recovery',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'counts_as_consumed' => 'boolean',
        'is_recoverable' => 'boolean',
        'is_auto_recovery' => 'boolean',
        'duration_minutes' => 'integer',
        'completed_at' => 'datetime',
    ];

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

    /**
     * Questa lezione (recupero) è collegata alla lezione originale annullata.
     */
    public function originalLesson(): BelongsTo
    {
        return $this->belongsTo(self::class, 'recovery_of_lesson_id');
    }

    /**
     * Questa lezione (originale) ha una lezione di recupero collegata.
     */
    public function recoveryLesson(): HasOne
    {
        return $this->hasOne(self::class, 'recovery_of_lesson_id');
    }

    public function consumptionHours(): int
    {
        $mins = (int) ($this->duration_minutes ?? 60);

        if ($this->starts_at && $this->ends_at) {
            $diff = Carbon::parse($this->starts_at)->diffInMinutes(Carbon::parse($this->ends_at), false);
            if ($diff > 0) $mins = $diff;
        }

        if ($mins <= 0) $mins = 60;

        return max(1, (int) ceil($mins / 60));
    }

    /**
     * Regola 24h:
     * - se annullata >= 24h prima => recuperabile (non consuma)
     * - se annullata < 24h => non recuperabile (consuma)
     */
    public function recomputeFlags(?Carbon $cancelledAt = null): void
    {
        $cancelledAt = $cancelledAt ?: $this->cancelled_at;

        if ($cancelledAt) {
            if (! $this->starts_at) {
                $this->is_recoverable = false;
                $this->counts_as_consumed = true;
                return;
            }

            $startsAt = Carbon::parse($this->starts_at);

            // ✅ recuperabile se annullata almeno 24h prima
            $isRecoverable = $cancelledAt->lte($startsAt->copy()->subHours(24));

            $this->is_recoverable = $isRecoverable;
            $this->counts_as_consumed = ! $isRecoverable;

            return;
        }

        $this->is_recoverable = false;

        if (! $this->counts_as_consumed) {
            $this->counts_as_consumed = false;
        }
    }
}
