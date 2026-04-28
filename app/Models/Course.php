<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = [
        'name',
        'lessons_count',
        'description',
        'course_price',
        'enrollment_fee',
    ];

    protected $casts = [
        'course_price' => 'decimal:2',
        'enrollment_fee' => 'decimal:2',
        'lessons_count' => 'integer',
    ];
}
