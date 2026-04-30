<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadQuote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'lead_id',
        'user_id',
        'title',
        'description',
        'amount',
        'valid_until',
        'status',
        'notes',
        'sent_at',
        'responded_at',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'valid_until'  => 'date',
        'sent_at'      => 'datetime',
        'responded_at' => 'datetime',
    ];

    public const STATUSES = [
        'draft'    => 'Bozza',
        'sent'     => 'Inviata',
        'accepted' => 'Accettata',
        'rejected' => 'Rifiutata',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->valid_until !== null && $this->valid_until->isPast();
    }
}
