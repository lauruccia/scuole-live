<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class GoogleAccount extends Model
{
    protected $fillable = [
        'label',
        'email',
        'calendar_id',
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function isConnected(): bool
    {
        return !empty($this->access_token)
            && !empty($this->refresh_token);
    }

    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return true;
        }

        return Carbon::now()->greaterThan($this->expires_at);
    }
}
