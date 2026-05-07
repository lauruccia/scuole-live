<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BillingProfile extends Model
{
    use LogsActivity;

    // ─── Activity Log (dati fatturazione) ────────────────────────────────────
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('gdpr')
            ->logOnly([
                'type', 'company_id',
                'first_name', 'last_name',
                'fiscal_code', 'vat_number', 'sdi_code', 'pec',
                'email', 'phone',
                'address', 'zip', 'city', 'province', 'country',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => "Profilo fatturazione #{$this->id} creato — {$this->display_name}",
                'updated' => "Profilo fatturazione #{$this->id} aggiornato — {$this->display_name}",
                'deleted' => "Profilo fatturazione #{$this->id} eliminato — {$this->display_name}",
                default   => "Profilo fatturazione #{$this->id} — {$eventName}",
            });
    }

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
