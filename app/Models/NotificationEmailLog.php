<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationEmailLog extends Model
{
    protected $fillable = [
        'type',
        'student_id',
        'installment_id',
        'contract_id',
        'reference_date',
        'email',
        'sent_at',
    ];

    protected $casts = [
        'reference_date' => 'date',
        'sent_at' => 'datetime',
    ];
}