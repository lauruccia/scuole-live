<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractSignatureOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $otpCode,
        public string $firstName,
        public int    $contractId,
        public int    $validMinutes = 15,
    ) {}

    public function build(): self
    {
        return $this
            ->subject('Codice di firma contratto #' . $this->contractId)
            ->view('emails.contract-signature-otp');
    }
}
