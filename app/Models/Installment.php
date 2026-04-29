<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Installment extends Model
{
    use LogsActivity;

    protected $fillable = [
        'contract_id',
        'number',
        'is_deposit',
        'due_date',
        'amount',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'is_deposit' => 'boolean',
        'due_date'   => 'date',
        'paid_at'    => 'datetime',
        'amount'     => 'decimal:2',
    ];

    // ─── Activity Log ─────────────────────────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('installments')
            ->logOnly(['status', 'amount', 'due_date', 'paid_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName): string {
                return match ($eventName) {
                    'created' => "Rata #{$this->number} creata (contratto #{$this->contract_id})",
                    'updated' => "Rata #{$this->number} aggiornata (contratto #{$this->contract_id})",
                    'deleted' => "Rata #{$this->number} eliminata (contratto #{$this->contract_id})",
                    default   => "Rata #{$this->number} — {$eventName}",
                };
            });
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    // ─── Hooks ────────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        // Impedisce la cancellazione di rate già pagate.
        static::deleting(function (self $installment) {
            if (! empty($installment->paid_at)) {
                throw new \RuntimeException(
                    "Impossibile eliminare la rata #{$installment->number} del contratto {$installment->contract_id}: è già stata pagata il {$installment->paid_at}."
                );
            }
        });
    }
}
