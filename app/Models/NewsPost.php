<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * NewsPost — notizia o evento pubblicato dallo staff sul sito pubblico.
 *
 * Pubblicato = is_published attivo E published_at nel passato (o nullo).
 * Questo permette di programmare una notizia con data futura.
 */
class NewsPost extends Model
{
    public const TYPES = [
        'news'   => 'Notizia',
        'evento' => 'Evento',
    ];

    protected $fillable = [
        'title', 'slug', 'type', 'excerpt', 'body', 'cover_image',
        'event_date', 'event_location', 'is_published', 'published_at', 'user_id',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'event_date'   => 'date',
    ];

    // ─── Relazioni ───────────────────────────────────────────────────────────

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ─── Scope ───────────────────────────────────────────────────────────────

    /** Solo contenuti visibili al pubblico, dal più recente. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(function (Builder $q) {
                $q->whereNull('published_at')
                  ->orWhere('published_at', '<=', now());
            })
            ->orderByDesc('published_at');
    }

    // ─── Helper ──────────────────────────────────────────────────────────────

    /** Etichetta leggibile del tipo (Notizia / Evento). */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    /** Genera uno slug unico a partire dal titolo. */
    public static function generateSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'post';
        $slug = $base;
        $i = 2;
        while (static::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }
}
