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
        $body      = $request->getContent();
        $eventType = $request->input('event_type');

        Log::info('PayPal webhook: ' . $eventType);

        if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
            $orderId  = data_get($request->input('resource'), 'supplementary_data.related_ids.order_id')
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
}
