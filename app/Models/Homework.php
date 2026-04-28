<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Homework extends Model
{
    protected $table = 'homeworks';

    protected $fillable = [
        'contract_id',
        'teacher_id',
        'title',
        'instructions',
        'attachment_path',
        'attachment_name',
        'due_at',
        'language',
    ];

    protected $casts = [
        'due_at' => 'datetime',
    ];

    // ─── Relazioni ─────────────────────────────────────────────────────────

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(HomeworkSubmission::class);
    }

    // ─── Helper ────────────────────────────────────────────────────────────

    public function submissionForStudent(int $studentId): ?HomeworkSubmission
    {
        return $this->submissions()->where('student_id', $studentId)->first();
    }

    public function isPastDue(): bool
    {
        return $this->due_at && $this->due_at->isPast();
    }
}
