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

        $data = $this->validateCheckout($request, $enabledPaymentMethods);

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

    // ── Pagina "Corso di lingua": lingua (libera) + ore/tipologia scelte tra i Corsi pubblici ──
    // Nessuna tabella dedicata: le scelte sono i Corsi attivi e pubblici del backend.

    private function languageOptions(): array
    {
        return array_values(\App\Filament\Resources\ContractResource::subjectOptions());
    }

    private function configuratorCourses()
    {
        return Course::where('is_active', true)->where('is_public', true)
            ->where('hours_purchased', '>', 0)
            ->orderBy('hours_purchased')->orderBy('name')
            ->get();
    }

    public function showConfigurator()
    {
        $courses   = $this->configuratorCourses();
        $languages = $this->languageOptions();
        abort_if($courses->isEmpty() || empty($languages), 404);

        // Preselezione: Corso più economico
        $course = $courses->sortBy(fn ($c) => $c->total_price)->first();

        $prefill = [];
        if ($user = Auth::user()) {
            $prefill = [
                'billing_first_name' => $user->first_name ?? '',
                'billing_last_name'  => $user->last_name ?? '',
                'billing_email'      => $user->email,
            ];
        }

        $enabledPaymentMethods = SchoolSetting::paymentEnabledMethods();

        // Il prezzo mostrato è informativo: quello vero viene riletto dal Corso in storeConfigurator().
        $variantsJson = $courses->map(function ($c) {
            $ore = (float) $c->hours_purchased;

            return [
                'id'         => $c->id,
                'hours'      => $ore,
                'hoursLabel' => rtrim(rtrim(number_format($ore, 2, ',', ''), '0'), ','),
                'type'       => trim((string) $c->lesson_type) !== '' ? $c->lesson_type : $c->name,
                'enrollment' => (float) $c->enrollment_fee,
                'price'      => (float) $c->course_price,
                'total'      => (float) $c->total_price,
                'image'      => $c->image_url,
            ];
        })->values();

        $configurator     = true;
        // Nessuna preselezione: solo il ripristino dopo un errore di validazione.
        $selectedCourseId = old('course_id');
        $selectedLanguage = old('language', '');

        return view('checkout.show', compact(
            'course', 'prefill', 'enabledPaymentMethods',
            'configurator', 'variantsJson', 'languages', 'selectedCourseId', 'selectedLanguage'
        ));
    }

    public function storeConfigurator(Request $request)
    {
        $enabledPaymentMethods = SchoolSetting::paymentEnabledMethods();

        if (empty($enabledPaymentMethods)) {
            return redirect()->route('checkout.acquista')
                ->with('danger', 'Al momento non e\' possibile completare il pagamento online. Contatta la segreteria.');
        }

        $choice = $request->validate([
            'course_id' => 'required|integer',
            'language'  => ['required', Rule::in($this->languageOptions())],
        ], [
            'course_id.required' => 'Seleziona ore e tipologia di lezioni.',
            'language.required'  => 'Seleziona la lingua.',
            'language.in'        => 'La lingua selezionata non e\' disponibile.',
        ]);

        // Il client non decide mai prezzo né corso: il Corso deve essere attivo, pubblico e con ore.
        $course = $this->configuratorCourses()->firstWhere('id', (int) $choice['course_id']);

        if (! $course) {
            return redirect()->route('checkout.acquista')
                ->withInput()
                ->withErrors(['course_id' => 'La combinazione scelta non e\' piu\' disponibile. Riprova.']);
        }

        $data = $this->validateCheckout($request, $enabledPaymentMethods);

        $purchase = CoursePurchase::create([
            ...$data,
            'course_id'      => $course->id,
            'language'       => $choice['language'],
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

    /**
     * Validazione condivisa dei dati di fatturazione + metodo di pagamento
     * (checkout corso singolo e checkout prodotto variabile).
     */
    private function validateCheckout(Request $request, array $enabledPaymentMethods): array
    {
        return $request->validate([
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
            // Messaggi in italiano (l'app non ha la cartella lang/it)
            'required'                => 'Il campo :attribute è obbligatorio.',
            'required_if'             => 'Il campo :attribute è obbligatorio.',
            'email'                   => 'Inserisci un indirizzo email valido.',
            'max'                     => 'Il campo :attribute è troppo lungo (massimo :max caratteri).',
            'string'                  => 'Il campo :attribute non è valido.',
            'in'                      => 'Il valore del campo :attribute non è valido.',
            'privacy.accepted'        => 'Per procedere devi accettare l\'informativa sulla privacy.',
            'payment_method.required' => 'Seleziona un metodo di pagamento.',
            'payment_method.in'       => 'Il metodo di pagamento selezionato non e\' attualmente disponibile.',
        ], [
            'billing_type'       => 'tipo di fatturazione',
            'billing_first_name' => 'nome',
            'billing_last_name'  => 'cognome',
            'billing_email'      => 'email',
            'billing_phone'      => 'telefono',
            'billing_address'    => 'indirizzo',
            'billing_city'       => 'città',
            'billing_zip'        => 'CAP',
            'billing_country'    => 'nazione',
            'billing_tax_code'   => 'codice fiscale',
            'company_name'       => 'ragione sociale',
            'vat_number'         => 'partita IVA',
            'payment_method'     => 'metodo di pagamento',
        ]);
    }
}
