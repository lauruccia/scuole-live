<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CoursePurchase;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    // ── Step 1: Catalogo corsi ────────────────────────────────────────────────

    public function catalogo(Request $request)
    {
        $query = Course::where('is_public', true)
            ->where('is_active', true);

        // Filtro ore
        if ($request->filled('ore')) {
            $query->where('hours_purchased', $request->ore);
        }

        // Filtro tipologia
        if ($request->filled('tipo')) {
            $query->where('lesson_type', $request->tipo);
        }

        $courses = $query->orderBy('name')->get();

        // Valori distinti per popolare i filtri (su tutti i corsi pubblici, non solo quelli filtrati)
        $allPublic = Course::where('is_public', true)->where('is_active', true);

        $availableOre = (clone $allPublic)
            ->whereNotNull('hours_purchased')
            ->where('hours_purchased', '>', 0)
            ->orderBy('hours_purchased')
            ->distinct()
            ->pluck('hours_purchased');

        $availableTipi = (clone $allPublic)
            ->whereNotNull('lesson_type')
            ->where('lesson_type', '!=', '')
            ->orderBy('lesson_type')
            ->distinct()
            ->pluck('lesson_type');

        return view('checkout.catalogo', compact('courses', 'availableOre', 'availableTipi'));
    }

    // ── Step 2: Pagina corso singolo + form dati ──────────────────────────────

    public function show(Course $course)
    {
        abort_unless($course->is_public && $course->is_active, 404);

        $prefill = [];
        if ($user = Auth::user()) {
            $prefill = [
                'billing_first_name' => $user->first_name ?? '',
                'billing_last_name'  => $user->last_name ?? '',
                'billing_email'      => $user->email,
            ];
        }

        return view('checkout.show', compact('course', 'prefill'));
    }

    // ── Step 3: Riceve dati + metodo, crea purchase, redirige al gateway ──────

    public function store(Request $request, Course $course)
    {
        abort_unless($course->is_public && $course->is_active, 404);

        $data = $request->validate([
            'billing_type'       => 'required|in:private,company',
            'billing_first_name' => 'required_if:billing_type,private|nullable|string|max:100',
            'billing_last_name'  => 'required_if:billing_type,private|nullable|string|max:100',
            'billing_email'      => 'required|email|max:200',
            'billing_phone'      => 'nullable|string|max:30',
            'billing_address'    => 'nullable|string|max:200',
            'billing_city'       => 'nullable|string|max:100',
            'billing_zip'        => 'nullable|string|max:10',
            'billing_country'    => 'nullable|string|max:5',
            'billing_tax_code'   => 'nullable|string|max:20',
            'company_name'       => 'required_if:billing_type,company|nullable|string|max:200',
            'vat_number'         => 'nullable|string|max:20',
            'payment_method'     => 'required|in:stripe,paypal,bonifico',
            'privacy'            => 'accepted',
        ]);

        $purchase = CoursePurchase::create([
            ...$data,
            'course_id'      => $course->id,
            'user_id'        => Auth::id(),
            'amount'         => $course->total_price,
            'payment_status' => 'pending',
        ]);

        return match($data['payment_method']) {
            'stripe'   => $this->payments->redirectStripe($purchase, $course),
            'paypal'   => $this->payments->redirectPaypal($purchase, $course),
            'bonifico' => redirect()->route('checkout.bonifico', $purchase),
        };
    }

    // ── Bonifico: mostra istruzioni + riferimento ─────────────────────────────

    public function bonifico(CoursePurchase $purchase)
    {
        abort_unless($purchase->payment_method === 'bonifico' && $purchase->payment_status === 'pending', 404);

        // Genera riferimento univoco se non ancora presente
        if (! $purchase->bank_transfer_ref) {
            $purchase->update([
                'bank_transfer_ref' => CoursePurchase::generateBankRef($purchase->id),
            ]);
        }

        return view('checkout.bonifico', compact('purchase'));
    }

    // ── Stripe: return URL dopo pagamento ─────────────────────────────────────

    public function stripeReturn(Request $request)
    {
        $sessionId = $request->get('session_id');
        $purchase  = CoursePurchase::where('stripe_session_id', $sessionId)->firstOrFail();

        // Verifica lo stato via Stripe SDK
        try {
            $result = $this->payments->verifyStripeSession($sessionId);

            if ($result === 'paid') {
                $this->payments->confirmPurchase($purchase, 'stripe', $result);
                return redirect()->route('checkout.grazie', $purchase)->with('success', true);
            }
        } catch (\Throwable $e) {
            Log::error('Stripe return error: ' . $e->getMessage());
        }

        return redirect()->route('checkout.errore', $purchase);
    }

    // ── PayPal: return URL dopo pagamento ─────────────────────────────────────

    public function paypalReturn(Request $request)
    {
        $orderId  = $request->get('token'); // PayPal passa il token = order_id
        $purchase = CoursePurchase::where('paypal_order_id', $orderId)->firstOrFail();

        try {
            $result = $this->payments->capturePaypalOrder($orderId);

            if ($result === 'COMPLETED') {
                $this->payments->confirmPurchase($purchase, 'paypal', $result);
                return redirect()->route('checkout.grazie', $purchase)->with('success', true);
            }
        } catch (\Throwable $e) {
            Log::error('PayPal return error: ' . $e->getMessage());
        }

        return redirect()->route('checkout.errore', $purchase);
    }

    // ── PayPal: cancel URL ────────────────────────────────────────────────────

    public function paypalCancel(Request $request)
    {
        $orderId  = $request->get('token');
        if ($orderId) {
            CoursePurchase::where('paypal_order_id', $orderId)
                ->update(['payment_status' => 'cancelled']);
        }
        return redirect()->route('checkout.catalogo')->with('info', 'Pagamento annullato.');
    }

    // ── Pagina di ringraziamento ───────────────────────────────────────────────

    public function grazie(CoursePurchase $purchase)
    {
        return view('checkout.grazie', compact('purchase'));
    }

    // ── Pagina errore ─────────────────────────────────────────────────────────

    public function errore(CoursePurchase $purchase)
    {
        return view('checkout.errore', compact('purchase'));
    }
}
