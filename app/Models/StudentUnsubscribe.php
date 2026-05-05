<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentUnsubscribe extends Model
{
    protected $fillable = [
        'email',
        'reason',
        'ip_address',
        'user_agent',
        'unsubscribed_at',
    ];

    protected $casts = [
        'unsubscribed_at' => 'datetime',
    ];

    /**
     * Verifica rapida (cached lookup possibile a livello superiore).
     */
    public static function isUnsubscribed(string $email): bool
    {
        return static::query()
            ->where('email', strtolower(trim($email)))
            ->exists();
    }
}
