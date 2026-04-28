<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonPlan extends Model
{
    protected $fillable = [
        'contract_student_id',
        'start_date',
        'lessons_count',
        'weekly_day',
        'weekly_time',
        'duration_minutes',
        'teacher_id',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'weekly_day' => 'integer',
        'duration_minutes' => 'integer',
        'lessons_count' => 'integer',
        'is_active' => 'boolean',
    ];

    public function contractStudent(): BelongsTo
    {
        return $this->belongsTo(ContractStudent::class, 'contract_student_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
