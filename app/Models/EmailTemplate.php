<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'category',
        'subject',
        'body_html',
        'available_variables',
        'trigger_event',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'available_variables' => 'array',
        'is_active'           => 'boolean',
    ];

    // ─── Elenco degli eventi trigger disponibili ──────────────────────────────

    public const TRIGGER_EVENTS = [
        'student.created'              => 'Studente creato',
        'lesson.cancelled.recoverable' => 'Lezione annullata (con recupero)',
        'lesson.cancelled.consumed'    => 'Lezione annullata (ore scalate)',
        'lesson.cancelled.permanent'   => 'Lezione annullata (definitivo)',
        'contract.sent'                => 'Contratto inviato',
        'material.assigned'            => 'Materiale didattico assegnato',
    ];

    // ─── Helper: recupera per slug ────────────────────────────────────────────

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->where('is_active', true)->first();
    }

    public static function findByEvent(string $event): ?self
    {
        return static::where('trigger_event', $event)->where('is_active', true)->first();
    }

    // ─── Sostituisce le variabili {{chiave}} nel soggetto e nel corpo ─────────

    public function render(array $variables): array
    {
        $subject = $this->subject;
        $body    = $this->body_html;

        foreach ($variables as $key => $value) {
            $subject = str_replace('{{' . $key . '}}', (string) $value, $subject);
            $body    = str_replace('{{' . $key . '}}', (string) $value, $body);
        }

        return ['subject' => $subject, 'body' => $body];
    }
}
