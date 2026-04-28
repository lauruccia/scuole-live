<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClosureDay extends Model
{
    protected $table = 'closure_days';

    protected $fillable = [
        'start_date',
        'end_date',
        'reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];
}
