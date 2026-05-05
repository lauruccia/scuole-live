<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable generico per l'invio di email basate su template DB (EmailTemplateService).
 *
 * Supporta:
 * - HTML pre-renderizzato con layout
 * - Allegati binari (base64 internamente per serializzazione corretta in coda)
 * - Invio in coda via queue:work (ShouldQueue)
 *
 * Non usare direttamente — istanziato da EmailTemplateService::sendTemplate().
 */
class TemplateMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $htmlContent   HTML completo già renderizzato con layout
     * @param  string  $mailSubject   Oggetto dell'email
     * @param  array   $attachments   Array di ['data' => binary, 'name' => 'file.pdf', 'mime' => '...']
     */
    public function __construct(
        private readonly string $htmlContent,
        private readonly string $mailSubject,
        private readonly array  $attachments = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(
                config('mail.from.address'),
                config('mail.from.name')
            ),
            subject: $this->mailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->htmlContent);
    }

    public function attachments(): array
    {
        return collect($this->attachments)
            ->map(fn ($att) => \Illuminate\Mail\Mailables\Attachment::fromData(
                fn () => $att['data'],
                $att['name'] ?? 'allegato'
            )->withMime($att['mime'] ?? 'application/octet-stream'))
            ->all();
    }
}
