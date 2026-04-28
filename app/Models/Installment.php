<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Installment extends Model
{
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
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

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
