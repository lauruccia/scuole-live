<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeStudentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $firstName,
        public string $studentEmail,
        public string $loginPassword,
    ) {}

    public function build(): self
    {
        return $this
            ->subject('Benvenuto/a in A&A Language Center!')
            ->view('emails.welcome-student');
    }
}
