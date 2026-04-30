<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractLessonSlot extends Model
{
    protected $table = 'contract_lesson_slots';

    protected $fillable = [
        'contract_id',
        'student_id',
        'teacher_id',
        'weekly_day',
        'weekly_time',
        'duration_minutes',
        'is_active',
        'starts_at',
        'ends_at',
        'meet_url',
    ];

    protected $casts = [
        'contract_id'      => 'integer',
        'student_id'       => 'integer',
        'teacher_id'       => 'integer',
        'weekly_day'       => 'integer',
        'weekly_time'      => 'string',
        'duration_minutes' => 'integer',
        'is_active'        => 'boolean',
        'starts_at'        => 'date',
        'ends_at'          => 'date',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $slot) {
            $duration = (int) ($slot->duration_minutes ?? 60);

            if (! in_array($duration, [30, 60, 90], true)) {
                $duration = 60;
            }

            $slot->duration_minutes = $duration;

            if ($slot->weekly_time !== null) {
                $time = trim((string) $slot->weekly_time);

                if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
                    $slot->weekly_time = $time . ':00';
                }
            }
        });
    }
}