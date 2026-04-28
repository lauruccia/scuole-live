<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeworkSubmission extends Model
{
    protected $table = 'homework_submissions';

    protected $fillable = [
        'homework_id',
        'student_id',
        'file_path',
        'file_name',
        'file_mime',
        'student_note',
        'grade',
        'teacher_feedback',
        'status',
        'submitted_at',
        'graded_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'graded_at'    => 'datetime',
    ];

    public const STATUSES = [
        'pending'   => 'In attesa',
        'submitted' => 'Consegnato',
        'graded'    => 'Valutato',
    ];

    // ─── Relazioni ─────────────────────────────────────────────────────────

    public function homework(): BelongsTo
    {
        return $this->belongsTo(Homework::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    // ─── Helper ────────────────────────────────────────────────────────────

    public function isSubmitted(): bool
    {
        return in_array($this->status, ['submitted', 'graded']);
    }

    public function isGraded(): bool
    {
        return $this->status === 'graded';
    }
}
