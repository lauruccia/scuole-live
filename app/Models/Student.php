<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Contract;
use App\Models\Lesson;
use App\Models\User;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Student extends Model
{
    use LogsActivity, SoftDeletes;

    // ─── Activity Log (GDPR) ──────────────────────────────────────────────────
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('gdpr')
            ->logOnly([
                'first_name', 'last_name', 'email', 'phone',
                'fiscal_code',
                'birth_date', 'birth_place', 'birth_province', 'birth_country',
                'residence_address', 'residence_zip', 'residence_city',
                'residence_province', 'residence_country',
                'parent_first_name', 'parent_last_name', 'parent_email', 'parent_phone',
                'is_minor',
                'employer_name', 'employer_vat_number',
                'user_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => "Studente #{$this->id} creato — {$this->full_name}",
                'updated' => "Studente #{$this->id} aggiornato — {$this->full_name}",
                'deleted' => "Studente #{$this->id} eliminato — {$this->full_name}",
                default   => "Studente #{$this->id} — {$eventName}",
            });
    }

    protected $fillable = [
        'user_id',

        'first_name',
        'last_name',
        'email',
        'phone',

        'residence_address',
        'residence_zip',
        'residence_city',
        'residence_province',
        'residence_country',

        'birth_date',
        'is_minor',

        'fiscal_code',
        'birth_place',
        'birth_province',
        'birth_country',

        'parent_first_name',
        'parent_last_name',
        'parent_email',
        'parent_phone',

        'employer_name',
        'employer_vat_number',

        'notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_minor'   => 'boolean',
    ];

    protected $appends = [
        'full_name',
        // from_contract, contracts_count, hours_purchased_total rimossi da appends:
        // eseguivano 3 query aggiuntive per ogni studente caricato in lista (N+1).
        // Restano disponibili come attributi calcolati (es. $student->contracts_count)
        // ma non vengono più eseguiti automaticamente ad ogni load.
    ];

    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(\App\Models\Enrollment::class);
    }

    public function contracts(): BelongsToMany
    {
        return $this->belongsToMany(Contract::class, 'contract_students', 'student_id', 'contract_id')
            ->withPivot(['teacher_id', 'weekly_day', 'weekly_time', 'meet_url'])
            ->withTimestamps();
    }

    public function getFromContractAttribute(): bool
    {
        if ($this->relationLoaded('contracts')) {
            return $this->contracts->isNotEmpty();
        }

        return $this->contracts()->exists();
    }

    public function getContractsCountAttribute(): int
    {
        if ($this->relationLoaded('contracts')) {
            return $this->contracts->unique('id')->count();
        }

        return (int) $this->contracts()->distinct('contracts.id')->count('contracts.id');
    }

    public function getHoursPurchasedTotalAttribute(): float
    {
        if ($this->relationLoaded('contracts')) {
            return (float) $this->contracts->sum('hours_purchased');
        }

        return (float) $this->contracts()->sum('hours_purchased');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}