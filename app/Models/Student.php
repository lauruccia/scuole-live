<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Contract;

class Student extends Model
{
    protected $fillable = [
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
        'is_minor' => 'boolean',


    ];

    protected $appends = [
        'full_name',
            'from_contract',
    'contracts_count',
    'hours_purchased_total',
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
    // esiste almeno 1 legame in contract_students
    return $this->contracts()->exists();
}

public function getContractsCountAttribute(): int
{
    return (int) $this->contracts()->distinct('contracts.id')->count('contracts.id');
}

public function getHoursPurchasedTotalAttribute(): float
{
    // somma ore acquistate su tutti i contratti collegati
    return (float) $this->contracts()->sum('hours_purchased');
}

public function lessons()
{
    return $this->hasMany(\App\Models\Lesson::class);
}



}
