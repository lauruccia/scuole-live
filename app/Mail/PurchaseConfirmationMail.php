<?php

namespace App\Mail;

use App\Models\Contract;
use App\Models\CoursePurchase;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PurchaseConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CoursePurchase $purchase,
        public Contract $contract,
    ) {}

    public function build(): self
    {
        return $this
            ->subject('Conferma iscrizione — ' . $this->purchase->course->name)
            ->view('emails.purchase-confirmation');
    }
}
