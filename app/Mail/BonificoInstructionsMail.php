<?php

namespace App\Mail;

use App\Models\CoursePurchase;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Email automatica con le istruzioni per il bonifico.
 *
 * Inviata immediatamente quando l'utente sceglie "bonifico" sul checkout.
 * Contiene:
 *  - IBAN, intestatario, importo
 *  - causale univoca (bank_transfer_ref) da inserire nel bonifico
 *  - prossimi passi
 *
 * Resiliente:
 *  - tries = 3 (Laravel queue retry automatico)
 *  - se la coda non e' configurata (sync) la mail parte subito ma try/catch
 *    nel chiamante evita che il checkout vada in errore.
 */
class BonificoInstructionsMail extends Mailable
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public CoursePurchase $purchase,
    ) {}

    public function build(): self
    {
        $this->purchase->loadMissing('course');

        $courseName = $this->purchase->course->name ?? 'Corso';

        return $this
            ->subject('Istruzioni per il bonifico — ' . $courseName)
            ->view('emails.bonifico-instructions');
    }
}
