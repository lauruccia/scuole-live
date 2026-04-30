<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttempt extends Model
{
    protected $table = 'quiz_attempts';

    protected $fillable = [
        'language',
        'user_id',
        'lead_id',
        'answers',
        'score',
        'total_questions',
        'result_level',
        'ip_address',
    ];

    protected $casts = [
        'answers'         => 'array',
        'score'           => 'integer',
        'total_questions' => 'integer',
    ];

    // ─── Relazioni ─────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    // ─── Helper ────────────────────────────────────────────────────────────

    public function getScorePercentAttribute(): int
    {
        if ($this->total_questions === 0) return 0;
        return (int) round(($this->score / $this->total_questions) * 100);
    }
}
