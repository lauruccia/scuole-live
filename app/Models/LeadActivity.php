<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    protected $fillable = [
        'lead_id',
        'user_id',
        'type',
        'subject',
        'body',
        'from_status',
        'to_status',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public const TYPES = [
        'note'          => 'Nota',
        'call'          => 'Chiamata',
        'email'         => 'Email',
        'meeting'       => 'Appuntamento',
        'whatsapp'      => 'WhatsApp',
        'status_change' => 'Cambio stato',
    ];

    public const TYPE_ICONS = [
        'note'          => '📝',
        'call'          => '📞',
        'email'         => '✉️',
        'meeting'       => '🤝',
        'whatsapp'      => '💬',
        'status_change' => '🔄',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeIconAttribute(): string
    {
        return self::TYPE_ICONS[$this->type] ?? '📌';
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
