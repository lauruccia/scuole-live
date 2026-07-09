<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Candidatura docente inviata dal form pubblico /lavora-con-noi.
 *
 * Destinatario: l'indirizzo configurato nel pannello (Contenuti sito →
 * Lavora con Noi → "Email candidature", default direzione@).
 *
 * Il CV viene salvato su storage/app/candidature (disk local, privato) e
 * allegato da lì: così la mail può essere accodata senza perdere il file
 * temporaneo dell'upload, e la scuola ha comunque una copia sul server.
 *
 * reply_to = email del candidato → la direzione risponde con un semplice
 * "Rispondi" dal proprio client di posta.
 */
class JobApplicationMail extends Mailable
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @param array{
     *     first_name:string, last_name:string, email:string, phone:string,
     *     lingua:string, laurea:?string, certificazioni:?string,
     *     esperienze:?string, message:?string
     * } $data
     * @param string $cvPath     Path relativo sul disk "local" (candidature/...)
     * @param string $cvFilename Nome file da mostrare nell'allegato
     */
    public function __construct(
        public array $data,
        public string $cvPath,
        public string $cvFilename,
    ) {}

    public function build(): self
    {
        return $this
            ->subject('Candidatura Docente — ' . $this->data['lingua'] . ' — ' . $this->data['first_name'] . ' ' . $this->data['last_name'])
            ->replyTo($this->data['email'], $this->data['first_name'] . ' ' . $this->data['last_name'])
            ->view('emails.candidatura')
            ->attachFromStorageDisk('local', $this->cvPath, $this->cvFilename);
    }
}
