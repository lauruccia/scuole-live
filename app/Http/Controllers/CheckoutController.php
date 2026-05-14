<?php

namespace App\Http\Controllers;

use App\Mail\BonificoInstructionsMail;
use App\Models\Course;
use App\Models\CoursePurchase;
use App\Models\SchoolSetting;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

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

        // Metodi di pagamento abilitati lato admin (vedi Impostazioni > Metodi di pagamento).
        // La view nasconde le radio dei metodi disabilitati e mette default sul primo attivo.
        $enabledPaymentMethods = SchoolSetting::paymentEnabledMethods();

        return view('checkout.show', compact('course', 'prefill', 'enabledPaymentMethods'));
    }

    // ── Step 3: Riceve dati + metodo, crea purchase, redirige al gateway ──────

    public function store(Request $request, Course $course)
    {
        abort_unless($course->is_public && $course->is_active, 404);

        // Metodi di pagamento ammessi: solo quelli che l'admin ha abilitato.
        // Difesa-in-profondita': il client puo' aver bypassato la radio nascosta
        // (es. tampering del form), qui blocchiamo a livello validazione.
        $enabledPaymentMethods = SchoolSetting::paymentEnabledMethods();

        if (empty($enabledPaymentMethods)) {
            // Edge case: tutti i metodi disattivi. Non si puo' procedere.
            return redirect()->route('checkout.show', $course)
                ->with('danger', 'Al momento non e\' possibile completare il pagamento online. Contatta la segreteria.');
        }

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
            // CF italiano validato con checksum (rule custom)
            'billing_tax_code'   => ['nullable', 'string', 'max:20', new \App\Rules\CodiceFiscale],
            'company_name'       => 'required_if:billing_type,company|nullable|string|max:200',
            // P.IVA italiana 11 cifre con checksum mod-10
            'vat_number'         => ['nullable', 'string', 'max:20', new \App\Rules\PartitaIva],
            // Validazione dinamica: solo i metodi abilitati dall'admin sono accettati.
            'payment_method'     => ['required', Rule::in($enabledPaymentMethods)],
            'privacy'            => 'accepted',
        ], [
            'payment_method.in' => 'Il metodo di pagamento selezionato non e\' attualmente disponibile.',
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

        // ── Ownership check (IDOR fix) ────────────────────────────────────────
        // Se il purchase è associato a un utente autenticato, solo quell'utente
        // (o lo staff) può vedere la pagina. Se user_id è null (checkout guest),
        // l'acquisto non è collegato a nessun account → lasciamo passare.
        if ($purchase->user_id !== null) {
            $currentUser = Auth::user();

            if (! $currentUser) {
                // Acquisto registrato a un utente ma visitatore non autenticato.
                abort(403, 'Accesso non autorizzato.');
            }

            $isStaff = $currentUser->hasAnyRole([
                'Superadmin', 'superadmin', 'super_admin',
                'Amministrazione', 'Segreteria',
            ]);

            if (! $isStaff && (int) $purchase->user_id !== (int) $currentUser->id) {
                abort(403, 'Non puoi accedere a questo ordine.');
            }
        }

        // Genera riferimento univoco se non ancora presente.
        // E' anche il "marker" per decidere se inviare l'email: la prima volta
        // che generiamo il ref, mandiamo le istruzioni via email; alle visite
        // successive saltiamo l'invio (idempotente).
        $isFirstView = ! $purchase->bank_transfer_ref;

        if ($isFirstView) {
            $purchase->update([
                'bank_transfer_ref' => CoursePurchase::generateBankRef($purchase->id),
            ]);

            // Invia istruzioni via email (in coda → resiliente a SMTP lento).
            // Try/catch per evitare che un fallimento mail blocchi il checkout:
            // l'utente vede comunque la pagina con i dati.
            try {
                Mail::to($purchase->billing_email)
                    ->queue(new BonificoInstructionsMail($purchase));
                Log::info('Bonifico instructions email queued', [
                    'purchase_id' => $purchase->id,
                    'to'          => $purchase->billing_email,
                ]);
            } catch (\Throwable $e) {
                Log::error('Bonifico email NON accodata: ' . $e->getMessage(), [
                    'purchase_id' => $purchase->id,
                ]);
                report($e);
            }
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
                return redirect()->to($this->signedGrazieUrl($purchase))->with('success', true);
            }
        } catch (\Throwable $e) {
            Log::error('Stripe return error: ' . $e->getMessage());
        }

        return redirect()->to($this->signedErroreUrl($purchase));
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
                return redirect()->to($this->signedGrazieUrl($purchase))->with('success', true);
            }
        } catch (\Throwable $e) {
            Log::error('PayPal return error: ' . $e->getMessage());
        }

        return redirect()->to($this->signedErroreUrl($purchase));
    }

    /**
     * URL firmato (24h) per la pagina di ringraziamento.
     * Senza la firma, l'accesso alla rotta restituisce 403 — evita che chi
     * conosce un purchase id arbitrario possa vedere i dati billing.
     */
    private function signedGrazieUrl(CoursePurchase $purchase): string
    {
        return URL::temporarySignedRoute('checkout.grazie', now()->addHours(24), ['purchase' => $purchase->id]);
    }

    /** URL firmato (24h) per la pagina di errore. */
    private function signedErroreUrl(CoursePurchase $purchase): string
    {
        return URL::temporarySignedRoute('checkout.errore', now()->addHours(24), ['purchase' => $purchase->id]);
    }

    // ── PayPal: cancel URL ────────────────────────────────────────�