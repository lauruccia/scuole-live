<?php

namespace App\Jobs;

use App\Mail\PurchaseConfirmationMail;
use App\Models\Contract;
use App\Models\CoursePurchase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Invia l'email di conferma dell'acquisto in coda asincrona.
 *
 * Resilienza:
 *  - tries = 3 (con backoff progressivo 30s → 2min)
 *  - in caso di fallimento finale, failed() segnala a Sentry / log
 *
 * Vantaggi rispetto all'invio sincrono:
 *  - Il webhook Stripe/PayPal risponde subito (non aspetta SMTP)
 *  - Se l'SMTP è momentaneamente irraggiungibile, retry automatico
 *  - Tracciabilità completa via Failed Jobs e Sentry
 */
class SendPurchaseConfirmationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Numero massimo di tentativi.
     */
    public int $tries = 3;

    /**
     * Backoff in secondi tra un tentativo e l'altro.
     * @return int[]
     */
    public function backoff(): array
    {
        return [30, 120]; // primo retry dopo 30s, secondo dopo 2 minuti
    }

    /**
     * Timeout per singolo tentativo (in secondi).
     * SMTP lento può impiegare anche >30s, quindi diamo margine.
     */
    public int $timeout = 60;

    public function __construct(
        public readonly int $purchaseId,
        public readonly int $contractId,
    ) {}

    public function handle(): void
    {
        $purchase = CoursePurchase::find($this->purchaseId);
        $contract = Contract::find($this->contractId);

        if (! $purchase || ! $contract) {
            Log::warning('SendPurchaseConfirmationJob: purchase o contract non trovati', [
                'purchase_id' => $this->purchaseId,
                'contract_id' => $this->contractId,
            ]);
            return;
        }

        if (empty($purchase->billing_email)) {
            Log::warning('SendPurchaseConfirmationJob: billing_email vuoto, salto invio', [
                'purchase_id' => $this->purchaseId,
            ]);
            return;
        }

        Mail::to($purchase->billing_email)
            ->send(new PurchaseConfirmationMail($purchase, $contract));

        Log::info('SendPurchaseConfirmationJob: email inviata', [
            'purchase_id' => $this->purchaseId,
            'to'          => $purchase->billing_email,
        ]);
    }

    /**
     * Callback chiamato dopo il fallimento dell'ultimo tentativo.
     */
    public function failed(?Throwable $e): void
    {
        Log::error('SendPurchaseConfirmationJob: fallito definitivamente', [
            'purchase_id' => $this->purchaseId,
            'contract_id' => $this->contractId,
            'error'       => $e?->getMessage(),
        ]);

        if ($e !== null) {
            report($e); // notifica Sentry
        }
    }
}
