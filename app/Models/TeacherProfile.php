<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * TeacherProfile — profilo pubblico di un insegnante mostrato sul sito
 * (elenco /insegnanti + pagina singola /insegnanti/{slug}).
 *
 * Da NON confondere con il modulo HR "Docenti" (TeacherResource, basato
 * su User con ruolo Docente, usato per contratti/paghe/materie interne):
 * qui si gestisce solo il contenuto pubblico (bio, materie, foto).
 */
class TeacherProfile extends Model
{
    protected $table = 'teacher_profiles';

    protected $fillable = [
        'name', 'slug', 'language', 'qualifications', 'certifications',
        'bio', 'photo', 'order', 'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'order' => 'integer',
    ];

    // ─── Scope ───────────────────────────────────────────────────────────────

    /** Solo profili pubblicati, in ordine di visualizzazione. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->orderBy('order')
            ->orderBy('name');
    }

    // ─── Helper ──────────────────────────────────────────────────────────────

    /** Genera uno slug unico a partire dal nome. */
    public static function generateSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'insegnante';
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
