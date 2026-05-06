<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Email di benvenuto inviata a chi compila il form pubblico /iscriviti.
 *
 * Sostituisce l'HTML inline che era hardcoded in PublicController e che
 * conteneva indirizzi/telefono/dominio errati o legacy.
 *
 * Tutto il branding (nome scuola, indirizzo, contatti, link policy) viene
 * letto da SchoolSetting nella view emails.lead-welcome — niente piu' rebrand
 * sparsi nei file PHP.
 *
 * Resilienza:
 *  - tries=3 + backoff progressivo Laravel default
 *  - inviata via Mail::queue → non blocca la response del form
 */
class LeadWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public Lead $lead,
        public ?string $userMessage = null,
    ) {}

    public function build(): self
    {
        return $this
            ->subject('Richiesta ricevuta — ' . \App\Models\SchoolSetting::schoolName())
            ->view('emails.lead-welcome');
    }
}
