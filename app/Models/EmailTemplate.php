<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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

    // ─── Corpo: ripara HTML salvato come testo escapato ──────────────────────
    //
    // Il vecchio RichEditor (Trix) salvava il codice HTML incollato come testo:
    // "<div>&lt;p&gt;Ciao …&lt;/p&gt;<br>…</div>" → nella mail comparivano i tag.
    // Qui lo riconosciamo e lo riportiamo all'HTML originale, così i template
    // già rovinati si sistemano da soli (e al primo salvataggio restano puliti).

    protected function bodyHtml(): Attribute
    {
        return Attribute::get(fn (?string $value) => static::repairEscapedHtml($value));
    }

    public static function repairEscapedHtml(?string $html): ?string
    {
        if ($html === null || ! preg_match('/&lt;\/?(p|table|tr|td|th|strong|b|em|i|u|br|div|span|a|h[1-6]|ul|ol|li)\b/i', $html)) {
            return $html;
        }

        // I tag "veri" sono solo il contenitore di Trix: diventano a capo
        $text = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $text = preg_replace('/<\/(div|p|h[1-6]|li|blockquote)>/i', "\n", $text);
        $text = strip_tags($text);

        // Il contenuto escapato torna HTML
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }

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
