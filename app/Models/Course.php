<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = [
        'name',
        'is_active',
        'is_public',
        'short_description',
        'description',
        'level',
        'image_path',
        // lessons_count rimosso da fillable: campo legacy (ora si usa hours_purchased).
        // La colonna esiste ancora in DB per compatibilità con dati storici,
        // ma non deve essere modificabile dall'interfaccia.
        'hours_purchased',
        'language_id',
        'lesson_type',
        'course_price',
        'enrollment_fee',
    ];

    protected $casts = [
        'course_price'    => 'decimal:2',
        'enrollment_fee'  => 'decimal:2',
        'hours_purchased' => 'decimal:2',
        'is_active'       => 'boolean',
        'is_public'       => 'boolean',
    ];

    public function purchases(): HasMany
    {
        return $this->hasMany(CoursePurchase::class);
    }

    /** Prezzo totale da pagare (iscrizione + corso) */
    public function getTotalPriceAttribute(): float
    {
        return (float) $this->course_price + (float) $this->enrollment_fee;
    }
}
