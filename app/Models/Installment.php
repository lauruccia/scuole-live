<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// ⚠️  spatie/laravel-activitylog non ancora installato.
//     Dopo aver eseguito: composer require spatie/laravel-activitylog:^4.9
//     decommentare i tre blocchi marcati con [ACTIVITYLOG].

// [ACTIVITYLOG] use Spatie\Activitylog\LogOptions;
// [ACTIVITYLOG] use Spatie\Activitylog\Traits\LogsActivity;

class Installment extends Model
{
    // [ACTIVITYLOG] use LogsActivity;

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

    // ─── Activity Log (attivare dopo composer require) ────────────────────────
    //
    // [ACTIVITYLOG]
    // public function getActivitylogOptions(): LogOptions
    // {
    //     return LogOptions::defaults()
    //         ->logName('installments')
    //         ->logOnly(['status', 'amount', 'due_date', 'paid_at'])
    //         ->logOnlyDirty()
    //         ->dontSubmitEmptyLogs()
    //         ->setDescriptionForEvent(function (string $eventName): string {
    //             return match ($eventName) {
    //                 'created' => "Rata #{$this->number} creata (contratto #{$this->contract_id})",
    //                 'updated' => "Rata #{$this->number} aggiornata (contratto #{$this->contract_id})",
    //                 'deleted' => "Rata #{$this->number} eliminata (contratto #{$this->contract_id})",
    //                 default   => "Rata #{$this->number} — {$eventName}",
    //             };
    //         });
    // }

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
