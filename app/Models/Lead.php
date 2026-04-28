<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Student;
use App\Models\User;

class Lead extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'course_interest',
        'interest_notes',
        'source',
        'status',
        'loss_reason',
        'assigned_to',
        'followup_at',
        'notes',
        'converted_student_id',
        'converted_at',
    ];

    protected $casts = [
        'followup_at'    => 'date',
        'converted_at'   => 'datetime',
    ];

    // ─── Costanti pipeline ───────────────────────────────────────────────────

    public const STATUSES = [
        'new'           => 'Nuovo',
        'contacted'     => 'Contattato',
        'proposal_sent' => 'Proposta inviata',
        'enrolled'      => 'Iscritto',
        'lost'          => 'Perso',
    ];

    public const SOURCES = [
        'manual'   => 'Inserimento manuale',
        'website'  => 'Sito web',
        'referral' => 'Passaparola',
        'social'   => 'Social media',
        'google'   => 'Google Ads',
        'other'    => 'Altro',
    ];

    // ─── Attributi ───────────────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCES[$this->source] ?? $this->source;
    }

    public function isConverted(): bool
    {
        return $this->converted_student_id !== null;
    }

    public function hasFollowupToday(): bool
    {
        return $this->followup_at !== null && $this->followup_at->isToday();
    }

    public function hasOverdueFollowup(): bool
    {
        return $this->followup_at !== null && $this->followup_at->isPast() && ! $this->followup_at->isToday();
    }

    // ─── Relazioni ───────────────────────────────────────────────────────────

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function convertedStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'converted_student_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->latest('occurred_at');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeFollowupToday($query)
    {
        return $query->whereDate('followup_at', today());
    }

    public function scopeFollowupOverdue($query)
    {
        return $query->whereDate('followup_at', '<', today())
                     ->whereNotIn('status', ['enrolled', 'lost']);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['enrolled', 'lost']);
    }
}
