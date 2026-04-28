<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class CourseMaterial extends Model
{
    protected $table = 'course_materials';

    protected $fillable = [
        'uploaded_by',
        'language',
        'title',
        'description',
        'external_url',
        'file_path',
        'file_name',
        'file_mime',
        'file_size',
        'material_type',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public const MATERIAL_TYPES = [
        'handout'  => 'Dispensa / Handout',
        'exercise' => 'Esercizio',
        'video'    => 'Video (link)',
        'image'    => 'Immagine',
        'other'    => 'Altro',
    ];

    // ─── Relazioni ─────────────────────────────────────────────────────────

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Contratti/studenti a cui questo materiale è assegnato.
     * La pivot porta: is_visible, assigned_at
     */
    public function contracts(): BelongsToMany
    {
        return $this->belongsToMany(Contract::class, 'contract_course_material')
                    ->withPivot('is_visible', 'assigned_at')
                    ->withTimestamps()
                    ->orderByPivot('assigned_at', 'desc');
    }

    // ─── Helper ────────────────────────────────────────────────────────────

    /** True se il materiale è un link esterno (YouTube, Vimeo, ecc.) */
    public function getIsLinkAttribute(): bool
    {
        return ! empty($this->external_url);
    }

    /** URL per aprire/scaricare il materiale */
    public function getDownloadUrlAttribute(): string
    {
        if ($this->is_link) {
            return $this->external_url;
        }
        return Storage::disk('public')->url($this->file_path);
    }

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = (int) $this->file_size;
        if ($bytes < 1024)    return "{$bytes} B";
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->material_type) {
            'video'    => '▶️',
            'exercise' => '✏️',
            'handout'  => '📄',
            'image'    => '🖼️',
            default    => '📎',
        };
    }
}
