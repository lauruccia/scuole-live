<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\CoursePurchase;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
        $token       = $this->getPaypalToken();
        $baseUrl     = config('services.paypal.base_url'); // https://api-m.paypal.com o sandbox
        $courseName  = mb_substr($course->name, 0, 127);

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
                'return_url' => route('checkout.paypal.return'),
                'cancel_url' => route('checkout.paypal.cancel'),
                'brand_name' => config('app.name'),
                'locale'     => 'it-IT',
                'user_action' => 'PAY_NOW',
            ],
        ];

        $response = $this->paypalPost($baseUrl . '/v2/checkout/orders', $token, $body);

        $orderId = $response['id'] ?? null;
        if (! $orderId) {
            Log::error('PayPal create order failed', $response);
            abort(500, 'Errore PayPal. Riprova.');
        }

        $purchase->update(['paypal_order_id' => $orderId]);

        // Trova il link approve
        $approveUrl = collect($response['links'] ?? [])
            ->firstWhere('rel', 'approve')['href'] ?? null;

        if (! $approveUrl) {
            abort(500, 'Link PayPal non trovato. Riprova.');
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

    private function getPaypalToken(): string
    {
        $clientId = config('services.paypal.client_id');
        $secret   = config('services.paypal.secret');
        $baseUrl  = config('services.paypal.base_url');

        $ch = curl_init($baseUrl . '/v1/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $clientId . ':' . $secret,
            CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
        ]);
        $res   = json_decode(curl_exec($ch), true);
        curl_close($ch);
        return $res['access_token'] ?? '';
    }

    private function paypalPost(string $url, string $token, array $body): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($body),
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        return json_decode($res, true) ?? [];
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
        return Student::create([
            'first_name' => $purchase->billing_first_name ?? '',
            'last_name'  => $purchase->billing_last_name ?? '',
            'email'      => $purchase->billing_email,
            'phone'      => $purchase->billing_phone,
            'user_id'    => $purchase->user_id,
        ]);
    }

    private function createContractFromPurchase(CoursePurchase $purchase, Student $student): Contract
    {
        $course = $purchase->course;

        $contract = Contract::create([
            'status'        => 'pending', // la segreteria completerà orari/docente
            'billing_type'  => $purchase->billing_type,
            'billing_first_name' => $purchase->billing_first_name,
            'billing_last_name'  => $purchase->billing_last_name,
            'billing_email'      => $purchase->billing_email,
            'billing_phone'      => $purchase->billing_phone,
            'billing_address'    => $purchase->billing_address,
            'billing_city'       => $purchase->billing_city,
            'billing_zip'        => $purchase->billing_zip,
            'billing_country'    => $purchase->billing_country ?? 'IT',
            'billing_tax_code'   => $purchase->billing_tax_code,
            'company_name'       => $purchase->company_name,
            'vat_number'         => $purchase->vat_number,
            'course_id'          => $course->id,
            'hours_purchased'    => $course->hours_purchased,
            'languages'          => $course->language_id ? [$course->language_id] : [],
            'lesson_type'        => $course->lesson_type,
            'course_price'       => $course->course_price,
            'enrollment_fee'     => $course->enrollment_fee,
            'notes'              => 'Acquisto online — #' . $purchase->id . ' — ' . $purchase->payment_method_label,
        ]);

        // Aggancia lo studente al contratto
        $contract->students()->syncWithoutDetaching([$student->id]);

        return $contract;
    }

    private function sendConfirmationEmail(CoursePurchase $purchase, Contract $contract): void
    {
        try {
            Mail::to($purchase->billing_email)
                ->send(new \App\Mail\PurchaseConfirmationMail($purchase, $contract));
        } catch (\Throwable $e) {
            Log::error('Purchase confirmation email failed: ' . $e->getMessage());
        }
    }
}
