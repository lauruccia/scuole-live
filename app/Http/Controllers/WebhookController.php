<?php

namespace App\Http\Controllers;

use App\Models\CoursePurchase;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    // ── Stripe webhook ────────────────────────────────────────────────────────

    public function stripe(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        // Verifica firma
        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature failed');
            return response('Invalid signature', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session  = $event->data->object;
            $purchase = CoursePurchase::where('stripe_session_id', $session->id)->first();

            if ($purchase && ! $purchase->isPaid()) {
                $this->payments->confirmPurchase($purchase, 'stripe', 'paid');
            }
        }

        if ($event->type === 'checkout.session.expired') {
            $session  = $event->data->object;
            CoursePurchase::where('stripe_session_id', $session->id)
                ->where('payment_status', 'pending')
                ->update(['payment_status' => 'cancelled']);
        }

        return response('OK', 200);
    }

    // ── PayPal webhook ────────────────────────────────────────────────────────

    public function paypal(Request $request)
    {
        // ── Verifica firma PayPal (API verify-webhook-signature) ──────────────
        if (! $this->verifyPayPalSignature($request)) {
            Log::warning('PayPal webhook: firma non valida', [
                'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'),
            ]);
            return response('Invalid signature', 400);
        }

        $eventType = $request->input('event_type');
        Log::info('PayPal webhook: ' . $eventType);

        if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
            $orderId = data_get($request->input('resource'), 'supplementary_data.related_ids.order_id')
                    ?? data_get($request->input('resource'), 'id');

            if ($orderId) {
                $purchase = CoursePurchase::where('paypal_order_id', $orderId)->first();
                if ($purchase && ! $purchase->isPaid()) {
                    $this->payments->confirmPurchase($purchase, 'paypal', 'COMPLETED');
                }
            }
        }

        return response('OK', 200);
    }

    /**
     * Verifica la firma del webhook PayPal tramite l'API ufficiale
     * verify-webhook-signature.
     *
     * Documentazione: https://developer.paypal.com/api/webhooks/v1/#verify-webhook-signature_post
     */
    private function verifyPayPalSignature(Request $request): bool
    {
        $webhookId = config('services.paypal.webhook_id');

        // SICUREZZA: in PRODUZIONE, se il webhook_id NON e' configurato BLOCCHIAMO
        //   la richiesta. Senza verifica firma, chiunque potrebbe simulare un webhook
        //   PayPal e completare ordini fittizi (fraud risk).
        //   Solo locale/staging puo' saltare la verifica per sviluppo.
        if (empty($webhookId)) {
            if (app()->environment('production')) {
                Log::error('PayPal webhook: PAYPAL_WEBHOOK_ID non configurato in produzione — richiesta BLOCCATA per sicurezza', [
                    'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'),
                    'remote_ip'       => $request->ip(),
                ]);
                return false;
            }
            Log::warning('PayPal webhook: PAYPAL_WEBHOOK_ID non configurato — verifica firma saltata (ambiente non-production)');
            return true;
        }

        $clientId     = config('services.paypal.client_id');
        $clientSecret = config('services.paypal.secret');
        $baseUrl      = rtrim(config('services.paypal.base_url', 'https://api-m.sandbox.paypal.com'), '/');

        try {
            // 1. Ottieni access token
            $tokenResponse = \Illuminate\Support\Facades\Http::withBasicAuth($clientId, $clientSecret)
                ->asForm()
                ->post("{$baseUrl}/v1/oauth2/token", ['grant_type' => 'client_credentials']);

            if (! $tokenResponse->successful()) {
                Log::error('PayPal webhook: impossibile ottenere access token', $tokenResponse->json());
                return false;
            }

            $accessToken = $tokenResponse->json('access_token');

            // 2. Chiama verify-webhook-signature
            $verifyResponse = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->post("{$baseUrl}/v1/notifications/verify-webhook-signature", [
                    'auth_algo'         => $request->header('PAYPAL-AUTH-ALGO'),
                    'cert_url'          => $request->header('PAYPAL-CERT-URL'),
                    'transmission_id'   => $request->header('PAYPAL-TRANSMISSION-ID'),
                    'transmission_sig'  => $request->header('PAYPAL-TRANSMISSION-SIG'),
                    'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
                    'webhook_id'        => $webhookId,
                    'webhook_event'     => $request->json()->all(),
                ]);

            if (! $verifyResponse->successful()) {
                Log::error('PayPal webhook: errore API verify-webhook-signature', $verifyResponse->json());
                return false;
            }

            $status = $verifyResponse->json('verification_status');
            return $status === 'SUCCESS';

        } catch (\Throwable $e) {
            Log::error('PayPal webhook: eccezione durante verifica firma — ' . $e->getMessage());
            return false;
        }
    }
}
