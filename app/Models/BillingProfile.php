<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingProfile extends Model
{
    protected $fillable = [
        'type',
        'company_id',
        'first_name',
        'last_name',
        'fiscal_code',
        'vat_number',
        'sdi_code',
        'pec',
        'email',
        'phone',
        'address',
        'zip',
        'city',
        'province',
        'country',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->type === 'company' && $this->company) {
            return $this->company->name;
        }

        return trim(($this->last_name ?? '') . ' ' . ($this->first_name ?? '')) ?: '—';
    }
}
