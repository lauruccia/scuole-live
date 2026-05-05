<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\CoursePurchase;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentService
{
    // ──────────────────────────────────────────────────────────────────────────
    // STRIPE
    // ──────────────────────────────────────────────────────────────────────────

    public function redirectStripe(CoursePurchase $purchase, Course $course)
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        $courseName = $course->name;
        $lineItems  = [];

        // Quota iscrizione
        if ((float) $course->enrollment_fee > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency'     => 'eur',
                    'unit_amount'  => (int) round($course->enrollment_fee * 100),
                    'product_data' => ['name' => 'Quota iscrizione — ' . $courseName],
                ],
                'quantity' => 1,
            ];
        }

        // Quota corso
        if ((float) $course->course_price > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency'     => 'eur',
                    'unit_amount'  => (int) round($course->course_price * 100),
                    'product_data' => ['name' => $courseName],
                ],
                'quantity' => 1,
            ];
        }

        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items'           => $lineItems,
            'mode'                 => 'payment',
            'customer_email'       => $purchase->billing_email,
            'success_url'          => route('checkout.stripe.return') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'           => route('checkout.catalogo'),
            'metadata'             => [
                'purchase_id' => $purchase->id,
            ],
        ]);

        $purchase->update(['stripe_session_id' => $session->id]);

        return redirect($session->url);
    }

    public function verifyStripeSession(string $sessionId): string
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
        $session = \Stripe\Checkout\Session::retrieve($sessionId);
        return $session->payment_status; // 'paid' | 'unpaid'
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PAYPAL
    // ──────────────────────────────────────────────────────────────────────────

    public function redirectPaypal(CoursePurchase $purchase, Course $course)
    {
        $baseUrl    = config('services.paypal.base_url');
        $courseName = mb_substr($course->name, 0, 127);

        $body = [
            'intent'         => 'CAPTURE',
            'purchase_units' => [[
                'amount'      => [
                    'currency_code' => 'EUR',
                    'value'         => number_format($purchase->amount, 2, '.', ''),
                ],
                'description' => $courseName,
            ]],
            'application_context' => [
                'return_url'  => route('checkout.paypal.return'),
                'cancel_url'  => route('checkout.paypal.cancel'),
                'brand_name'  => config('app.name'),
                'locale'      => 'it-IT',
                'user_action' => 'PAY_NOW',
            ],
        ];

        // Retry automatico: fino a 3 tentativi con backoff esponenziale (1s, 2s)
        $maxAttempts = 3;
        $response    = null;
        $lastError   = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $token    = $this->getPaypalToken();
                $response = $this->paypalPost($baseUrl . '/v2/checkout/orders', $token, $body);
                break; // successo → esci dal loop
            } catch (\RuntimeException $e) {
                $lastError = $e;
                Log::warning("PayPal createOrder tentativo {$attempt}/{$maxAttempts} fallito: " . $e->getMessage());
                if ($attempt < $maxAttempts) {
                    sleep(2 ** ($attempt - 1)); // 1s, 2s
                }
            }
        }

        if ($response === null) {
            Log::error('PayPal createOrder fallito dopo ' . $maxAttempts . ' tentativi', [
                'purchase_id' => $purchase->id,
                'error'       => $lastError?->getMessage(),
            ]);
            return redirect(route('checkout.catalogo'))
                ->with('danger', 'Il servizio PayPal non è al momento disponibile. Riprova tra qualche minuto oppure scegli un altro metodo di pagamento.');
        }

        $orderId = $response['id'] ?? null;
        if (! $orderId) {
            Log::error('PayPal create order: risposta senza order ID', $response);
            return redirect(route('checkout.catalogo'))
                ->with('danger', 'Errore nella creazione dell\'ordine PayPal. Riprova o contatta la segreteria.');
        }

        $purchase->update(['paypal_order_id' => $orderId]);

        $approveUrl = collect($response['links'] ?? [])
            ->firstWhere('rel', 'approve')['href'] ?? null;

        if (! $approveUrl) {
            Log::error('PayPal create order: link approve non trovato', $response);
            return redirect(route('checkout.catalogo'))
                ->with('danger', 'Errore nel collegamento a PayPal. Riprova o contatta la segreteria.');
        }

        return redirect($approveUrl);
    }

    public function capturePaypalOrder(string $orderId): string
    {
        $token   = $this->getPaypalToken();
        $baseUrl = config('services.paypal.base_url');
        $result  = $this->paypalPost($baseUrl . '/v2/checkout/orders/' . $orderId . '/capture', $token, []);
        return $result['status'] ?? 'UNKNOWN'; // COMPLETED | VOIDED | …
    }

    /**
     * Ottiene un access token PayPal OAuth2.
     * Usa Http facade (Guzzle) con timeout esplicito e gestione errori.
     *
     * @throws \RuntimeException se l'autenticazione fallisce
     */
    private function getPaypalToken(): string
    {
        $clientId = config('services.paypal.client_id');
        $secret   = config('services.paypal.secret');
        $baseUrl  = rtrim((string) config('services.paypal.base_url'), '/');

        $response = Http::timeout(15)
            ->withBasicAuth($clientId, $secret)
            ->asForm()
            ->post("{$baseUrl}/v1/oauth2/token", ['grant_type' => 'client_credentials']);

        if (! $response->successful()) {
            Log::error('PayPal: impossibile ottenere access token', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('PayPal authentication failed (HTTP ' . $response->status() . ')');
        }

        $token = $response->json('access_token');

        if (empty($token)) {
            Log::error('PayPal: access_token mancante nella risposta', ['body' => $response->body()]);
            throw new \RuntimeException('PayPal returned empty access token');
        }

        return $token;
    }

    /**
     * Esegue una POST autenticata verso l'API PayPal.
     * Ritorna l'array decodificato o lancia eccezione su errore di rete.
     *
     * @throws \RuntimeException su errore HTTP o di rete
     */
    private function paypalPost(string $url, string $token, array $body): array
    {
        $response = Http::timeout(20)
            ->withToken($token)
            ->post($url, $body);

        if (! $response->successful()) {
            Log::error('PayPal API error', [
                'url'    => $url,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('PayPal API error (HTTP ' . $response->status() . ')');
        }

        return $response->json() ?? [];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CONFERMA ACQUISTO → crea contratto
    // ──────────────────────────────────────────────────────────────────────────

    public function confirmPurchase(CoursePurchase $purchase, string $method, string $gatewayStatus): void
    {
        if ($purchase->isPaid()) {
            return; // idempotente
        }

        DB::transaction(function () use ($purchase, $method) {
            // 1. Aggiorna stato acquisto
            $purchase->update([
                'payment_status' => 'paid',
                'paid_at'        => now(),
            ]);

            // 2. Crea (o trova) lo student
            $student = $this->findOrCreateStudent($purchase);

            // 3. Crea il contratto in stato pending
            $contract = $this->createContractFromPurchase($purchase, $student);

            // 4. Collega acquisto al contratto
            $purchase->update(['contract_id' => $contract->id]);

            // 5. Email di conferma
            $this->sendConfirmationEmail($purchase, $contract);
        });
    }

    private function findOrCreateStudent(CoursePurchase $purchase): Student
    {
        // Se l'utente autenticato ha già uno student associato
        if ($purchase->user_id) {
            $student = Student::where('user_id', $purchase->user_id)->first();
            if ($student) return $student;
        }

        // Cerca per email
        $student = Student::where('email', $purchase->billing_email)->first();
        if ($student) return $student;

        // Crea nuovo studente

        // Crea nuovo studente
        return Student::create([
            'first_name' => $purchase->billing_first_name ?? '',
            'last_name'  => $purchase->billing_last_name ?? '',
            'email'      => $purchase->billing_email,
            'phone'      => $purchase->billing_phone,
            'user_id'    => $purchase->user_id,
        ]);
    }

    /**
     * Crea un nuovo Contract dal CoursePurchase confermato.
     *
     * Il contratto nasce in stato "pending": l'amministrazione lo completerà
     * con slot orari, docente e date prima di passarlo a "active".
     */
    private function createContractFromPurchase(CoursePurchase $purchase, Student $student): Contract
    {
        $contract = Contract::create([
            'course_id'           => $purchase->course_id,
            'status'              => 'pending',
            'course_price'        => $purchase->amount,
            'billing_type'        => $purchase->billing_type,
            'billing_first_name'  => $purchase->billing_first_name,
            'billing_last_name'   => $purchase->billing_last_name,
            'billing_email'       => $purchase->billing_email,
            'billing_phone'       => $purchase->billing_phone,
            'billing_address'     => $purchase->billing_address,
            'billing_city'        => $purchase->billing_city,
            'billing_zip'         => $purchase->billing_zip,
            'billing_country'     => $purchase->billing_country,
            'billing_tax_code'    => $purchase->billing_tax_code,
            'company_name'        => $purchase->company_name,
            'vat_number'          => $purchase->vat_number,
        ]);

        // Aggancia lo studente come pivot
        $contract->students()->attach($student->id);

        return $contract;
    }

    /**
     * Dispatcha l'invio email di conferma in queue (resiliente: tries=3, backoff).
     * Il Job notifica Sentry in caso di fallimento finale.
     */
    private function sendConfirmationEmail(CoursePurchase $purchase, Contract $contract): void
    {
        \App\Jobs\SendPurchaseConfirmationJob::dispatch($purchase->id, $contract->id);
    }
}
