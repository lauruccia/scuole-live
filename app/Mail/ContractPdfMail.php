<?php

namespace App\Mail;

use App\Models\Contract;
use App\Models\SchoolSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractPdfMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Contract $contract,
        public string $pdfBinary
    ) {}

    public function build()
    {
        return $this->subject('Contratto ' . SchoolSetting::schoolName() . ' – #' . $this->contract->id)
            ->view('emails.contract-pdf')
            ->attachData($this->pdfBinary, 'Contratto_' . $this->contract->id . '.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}
