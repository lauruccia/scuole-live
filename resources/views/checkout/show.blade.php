@extends('public.layout')

@section('title', $course->name . ' a Roma — Iscrizione | A&A Language Center')
@section('description', \Illuminate\Support\Str::limit(strip_tags($course->short_description ?? $course->description ?? ('Corso di ' . ($course->language_id ?? 'lingue') . ' a Roma con docenti madrelingua. Iscriviti subito ad A&A Language Center.')), 158))
@section('keywords', 'corso ' . strtolower($course->language_id ?? 'lingue') . ' Roma, ' . strtolower($course->name) . ', scuola di ' . strtolower($course->language_id ?? 'lingue') . ' Roma San Paolo, iscrizione corso ' . strtolower($course->language_id ?? 'lingue') . ' Roma, A&A Language Center')

@section('breadcrumb-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ route('home') }}" },
        { "@@type": "ListItem", "position": 2, "name": "Corsi", "item": "{{ route('checkout.catalogo') }}" },
        { "@@type": "ListItem", "position": 3, "name": @json($course->name), "item": "{{ route('checkout.show', $course->id) }}" }
    ]
}
</script>
@endsection

@section('extra-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Course",
    "name": @json($course->name),
    "description": @json(strip_tags($course->description ?? $course->short_description ?? 'Corso di ' . ($course->language_id ?? 'lingue') . ' a Roma con docenti madrelingua.')),
    "url": "{{ route('checkout.show', $course->id) }}",
    "inLanguage": "it",
    "educationalLevel": @json($course->level ?? 'Tutti i livelli (A1–C2 CEFR)'),
    "teaches": @json($course->language_id ?? 'Lingua straniera'),
    "courseMode": ["onsite","online"],
    "provider": {
        "@@type": "EducationalOrganization",
        "name": "A&A Language Center",
        "sameAs": "{{ config('app.url') }}",
        "url": "{{ config('app.url') }}",
        "address": {
            "@@type": "PostalAddress",
            "streetAddress": "Viale Leonardo da Vinci, 193",
            "addressLocality": "Roma",
            "postalCode": "00145",
            "addressCountry": "IT"
        }
    },
    "hasCourseInstance": {
        "@@type": "CourseInstance",
        "courseMode": "Blended",
        "courseWorkload": @json($course->hours_purchased ? 'PT' . (int)$course->hours_purchased . 'H' : 'PT40H'),
        "location": {
            "@@type": "Place",
            "name": "A&A Language Center — Roma San Paolo",
            "address": "Viale Leonardo da Vinci 193, 00145 Roma"
        }
    },
    "offers": {
        "@@type": "Offer",
        "price": @json(number_format((float)$course->total_price, 2, '.', '')),
        "priceCurrency": "EUR",
        "availability": "https://schema.org/InStock",
        "url": "{{ route('checkout.show', $course->id) }}",
        "category": "language course",
        "validFrom": "{{ now()->toIso8601String() }}"
    }
}
</script>
@endsection

@push('styles')
<style>
    /* NB: la navbar e il container .c sono gestiti dal layout globale.
       Le precedenti regole nav/.nav-brand/.nav-links con !important
       sovrascrivevano il layout e rompevano l'header. Rimosse. */

    :root {
        --blue:      #0057d9;
        --blue-dark: #001b3f;
        --blue-deep: #001126;
        --light:     #f6faff;
        --text:      #06152f;
        --muted:     #526173;
        --yellow:    #ffd800;
        --border:    #dbe7f4;
        --shadow:    0 18px 50px rgba(0,37,91,.16);
    }

    /* ── Breadcrumb ── */
    .breadcrumb {
        background: var(--blue-deep);
        padding: 14px 0;
        font-size: 13px;
        color: rgba(255,255,255,.6);
    }
    .breadcrumb a { color: #aad0ff; text-decoration: none; }
    .breadcrumb a:hover { color: #fff; }
    .breadcrumb span { margin: 0 8px; opacity: .4; }

    /* ── Layout ── */
    .checkout-wrap {
        max-width: 1080px; margin: 36px auto 60px;
        padding: 0 20px;
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 26px;
        align-items: start;
    }
    @media (max-width: 760px) {
        .checkout-wrap { grid-template-columns: 1fr; }
        .sidebar { order: -1; }
    }

    /* ── Sidebar ordine ── */
    .sidebar {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 14px;
        box-shadow: var(--shadow);
        overflow: hidden;
        position: sticky;
        top: 24px;
    }
    .sidebar-header {
        background: linear-gradient(110deg, #001733, #003d94);
        color: #fff;
        padding: 18px 22px;
    }
    .sidebar-header h3 { margin: 0; font-size: 13px; font-weight: 800; letter-spacing: .04em; opacity: .8; text-transform: uppercase; }
    .sidebar-header .course-title { font-size: 18px; font-weight: 900; margin-top: 6px; }
    .sidebar-body { padding: 18px 22px; }
    .order-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 8px 0; font-size: 13px;
        border-bottom: 1px solid var(--border);
    }
    .order-row:last-child { border: none; }
    .order-row .label { color: var(--muted); }
    .order-row .val   { font-weight: 700; color: var(--text); }
    .order-total {
        background: var(--light); border-radius: 10px;
        padding: 14px 18px; margin-top: 14px;
        display: flex; justify-content: space-between; align-items: center;
    }
    .order-total .label { font-size: 14px; font-weight: 800; color: var(--blue-dark); }
    .order-total .val   { font-size: 22px; font-weight: 900; color: var(--blue); }
    .sidebar-trust {
        padding: 14px 22px;
        border-top: 1px solid var(--border);
        font-size: 12px; color: var(--muted); line-height: 1.7;
    }

    /* ── Form card ── */
    .form-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(0,37,91,.07);
        overflow: hidden;
        margin-bottom: 20px;
    }
    .form-card-header {
        padding: 18px 24px;
        border-bottom: 1px solid var(--border);
        display: flex; align-items: center; gap: 12px;
    }
    .step-num {
        width: 30px; height: 30px; border-radius: 50%;
        background: var(--blue); color: #fff;
        font-size: 13px; font-weight: 900;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .form-card-header h2 { margin: 0; font-size: 15px; font-weight: 900; color: var(--blue-dark); }
    .form-body { padding: 22px 24px; }

    /* ── Tabs tipo fatturazione ── */
    .billing-tabs { display: flex; gap: 10px; margin-bottom: 20px; }
    .billing-tab {
        flex: 1; text-align: center; padding: 10px 14px;
        border: 2px solid var(--border); border-radius: 8px;
        cursor: pointer; font-size: 13px; font-weight: 700;
        color: var(--muted); background: #fff; transition: .18s;
    }
    .billing-tab.active {
        border-color: var(--blue); background: #eff6ff; color: var(--blue);
    }
    .billing-tab:hover:not(.active) { border-color: #93c5fd; }

    /* ── Form fields ── */
    .field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .field-grid.cols-1 { grid-template-columns: 1fr; }
    @media (max-width: 480px) { .field-grid { grid-template-columns: 1fr; } }
    .field-full { grid-column: 1 / -1; }

    .field { display: flex; flex-direction: column; gap: 5px; }
    .field label { font-size: 12px; font-weight: 800; color: #1e2d42; letter-spacing: .02em; }
    .field input,
    .field select {
        padding: 10px 13px;
        border: 1.5px solid var(--border);
        border-radius: 8px;
        font-size: 14px; color: var(--text);
        background: #fff; transition: border-color .15s;
    }
    .field input:focus,
    .field select:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 3px rgba(0,87,217,.08); }
    .field input.err { border-color: #ef4444; }
    .err-msg { font-size: 11px; color: #dc2626; }

    /* ── Metodo pagamento ── */
    .pay-options { display: flex; flex-direction: column; gap: 12px; }
    .pay-opt {
        display: flex; align-items: center; gap: 14px;
        padding: 14px 16px;
        border: 2px solid var(--border); border-radius: 10px;
        cursor: pointer; transition: .18s; position: relative;
    }
    .pay-opt:has(input:checked) { border-color: var(--blue); background: #eff6ff; }
    .pay-opt input[type=radio] { accent-color: var(--blue); width: 18px; height: 18px; flex-shrink: 0; }
    .pay-icon { font-size: 1.6rem; flex-shrink: 0; }
    .pay-label strong { display: block; font-size: 14px; font-weight: 800; color: var(--text); }
    .pay-label small  { font-size: 12px; color: var(--muted); }

    /* ── Privacy ── */
    .privacy-check {
        display: flex; align-items: flex-start; gap: 10px;
        font-size: 13px; color: var(--muted); margin-top: 20px; line-height: 1.5;
    }
    .privacy-check input { margin-top: 2px; accent-color: var(--blue); }
    .privacy-check a { color: var(--blue); }

    /* ── Submit ── */
    .btn-submit {
        width: 100%; padding: 14px;
        margin-top: 22px;
        background: var(--blue); color: #fff;
        border: none; border-radius: 10px;
        font-size: 15px; font-weight: 900;
        cursor: pointer; transition: .2s ease;
        display: flex; align-items: center; justify-content: center; gap: 8px;
        box-shadow: 0 10px 25px rgba(0,87,217,.3);
        letter-spacing: .01em;
    }
    .btn-submit:hover { background: #0045b3; transform: translateY(-2px); }
    .btn-submit:disabled { background: #94a3b8; cursor: not-allowed; transform: none; box-shadow: none; }
    .secure-note { text-align: center; font-size: 11px; color: #9ca3af; margin-top: 10px; }

    /* Errori form */
    .alert-errors {
        background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b;
        border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; font-size: 13px;
    }
    .alert-errors ul { margin: 6px 0 0 16px; }

    footer { background: #001126 !important; color: #fff !important; margin-top: 0 !important; }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<div class="breadcrumb">
    <div class="c">
        <a href="{{ route('checkout.catalogo') }}">Corsi</a>
        <span>/</span>
        {{ $course->name }}
    </div>
</div>

<div class="checkout-wrap">

    {{-- ── SIDEBAR RIEPILOGO ──────────────────────────────── --}}
    <div class="sidebar" style="order:2;">
        <div class="sidebar-header">
            <div class="course-title">{{ $course->name }}</div>
            @if($course->level)
                <div style="font-size:12px;margin-top:4px;opacity:.75;">{{ $course->level }}</div>
            @endif
        </div>
        <div class="sidebar-body">
            @if($course->short_description)
                <div style="font-size:13px;color:var(--muted);margin-bottom:14px;line-height:1.5;">{{ $course->short_description }}</div>
            @endif
            @if($course->hours_purchased)
                <div class="order-row"><span class="label">&#9201; Ore incluse</span><span class="val">{{ number_format((float)$course->hours_purchased, 0, ',', '.') }}</span></div>
            @endif
            @if($course->lesson_type)
                <div class="order-row"><span class="label">👤 Tipo</span><span class="val">{{ $course->lesson_type }}</span></div>
            @endif
            @if($course->enrollment_fee > 0)
                <div class="order-row"><span class="label">Quota iscrizione</span><span class="val">€ {{ number_format($course->enrollment_fee, 2, ',', '.') }}</span></div>
            @endif
            @if($course->course_price > 0)
                <div class="order-row"><span class="label">Quota corso</span><span class="val">€ {{ number_format($course->course_price, 2, ',', '.') }}</span></div>
            @endif
            <div class="order-total">
                <span class="label">Totale</span>
                <span class="val">€ {{ number_format($course->total_price, 2, ',', '.') }}</span>
            </div>
        </div>
        <div class="sidebar-trust">
            🔒 Pagamento sicuro con crittografia SSL<br>
            ✅ Contratto creato automaticamente dopo il pagamento<br>
            📞 Supporto: <a href="tel:+390657437364" style="color:var(--blue);">06 5743734</a>
        </div>
    </div>

    {{-- ── FORM CHECKOUT ───────────────────────────────────── --}}
    <div style="order:1;">

        <form method="POST" action="{{ route('checkout.store', $course) }}" id="checkoutForm">
            @csrf

            @if($errors->any())
            <div class="alert-errors">
                <strong>Correggi questi errori per procedere:</strong>
                <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
            @endif

            {{-- ── STEP 1: Dati fatturazione ─── --}}
            <div class="form-card">
                <div class="form-card-header">
                    <div class="step-num">1</div>
                    <h2>Dati di fatturazione</h2>
                </div>
                <div class="form-body">

                    <div class="billing-tabs">
                        <div class="billing-tab {{ old('billing_type', 'private') !== 'company' ? 'active' : '' }}"
                             onclick="setBillingType('private', this)">👤 Privato</div>
                        <div class="billing-tab {{ old('billing_type') === 'company' ? 'active' : '' }}"
                             onclick="setBillingType('company', this)">🏢 Azienda</div>
                    </div>
                    <input type="hidden" name="billing_type" id="billing_type" value="{{ old('billing_type', 'private') }}">

                    {{-- Privato --}}
                    <div id="fields-private" class="{{ old('billing_type') === 'company' ? 'hidden' : '' }}" style="{{ old('billing_type') === 'company' ? 'display:none' : '' }}">
                        <div class="field-grid">
                            <div class="field">
                                <label>Nome *</label>
                                <input type="text" name="billing_first_name"
                                    value="{{ old('billing_first_name', $prefill['billing_first_name'] ?? '') }}"
                                    class="{{ $errors->has('billing_first_name') ? 'err' : '' }}" autocomplete="given-name">
                                @error('billing_first_name')<div class="err-msg">{{ $message }}</div>@enderror
                            </div>
                            <div class="field">
                                <label>Cognome *</label>
                                <input type="text" name="billing_last_name"
                                    value="{{ old('billing_last_name', $prefill['billing_last_name'] ?? '') }}"
                                    class="{{ $errors->has('billing_last_name') ? 'err' : '' }}" autocomplete="family-name">
                                @error('billing_last_name')<div class="err-msg">{{ $message }}</div>@enderror
                            </div>
                            <div class="field">
                                <label>Codice fiscale</label>
                                <input type="text" name="billing_tax_code" value="{{ old('billing_tax_code') }}"
                                    placeholder="RSSMRA80A01H501U" style="text-transform:uppercase;">
                            </div>
                        </div>
                    </div>

                    {{-- Azienda --}}
                    <div id="fields-company" style="{{ old('billing_type') !== 'company' ? 'display:none' : '' }}">
                        <div class="field-grid">
                            <div class="field field-full">
                                <label>Ragione sociale *</label>
                                <input type="text" name="company_name" value="{{ old('company_name') }}"
                                    class="{{ $errors->has('company_name') ? 'err' : '' }}">
                                @error('company_name')<div class="err-msg">{{ $message }}</div>@enderror
                            </div>
                            <div class="field">
                                <label>Partita IVA</label>
                                <input type="text" name="vat_number" value="{{ old('vat_number') }}">
                            </div>
                        </div>
                    </div>

                    {{-- Campi comuni --}}
                    <div class="field-grid" style="margin-top:16px;">
                        <div class="field field-full">
                            <label>Email *</label>
                            <input type="email" name="billing_email"
                                value="{{ old('billing_email', $prefill['billing_email'] ?? '') }}"
                                class="{{ $errors->has('billing_email') ? 'err' : '' }}" autocomplete="email">
                            @error('billing_email')<div class="err-msg">{{ $message }}</div>@enderror
                        </div>
                        <div class="field">
                            <label>Telefono</label>
                            <input type="tel" name="billing_phone" value="{{ old('billing_phone') }}"
                                placeholder="+39 06 5743734" autocomplete="tel">
                        </div>
                        <div class="field field-full">
                            <label>Indirizzo</label>
                            <input type="text" name="billing_address" value="{{ old('billing_address') }}"
                                placeholder="Via Roma, 1" autocomplete="street-address">
                        </div>
                        <div class="field">
                            <label>Città</label>
                            <input type="text" name="billing_city" value="{{ old('billing_city') }}" autocomplete="address-level2">
                        </div>
                        <div class="field">
                            <label>CAP</label>
                            <input type="text" name="billing_zip" value="{{ old('billing_zip') }}"
                                placeholder="00145" maxlength="5" autocomplete="postal-code">
                        </div>
                    </div>
                    <input type="hidden" name="billing_country" value="IT">
                </div>
            </div>

            {{-- ── STEP 2: Metodo pagamento ─── --}}
            <div class="form-card">
                <div class="form-card-header">
                    <div class="step-num">2</div>
                    <h2>Scegli il metodo di pagamento</h2>
                </div>
                <div class="form-body">

                    @php
                        // Lista metodi pagamento abilitata dall'admin (Impostazioni > Metodi di pagamento).
                        // Se la variabile non e' presente (legacy / cache obsoleta) fallback a tutti e 3.
                        $enabledMethods = $enabledPaymentMethods ?? ['stripe', 'paypal', 'bonifico'];
                        // Default selezione:
                        //  - se l'utente aveva gia' scelto (old) e quel metodo e' ancora abilitato → mantiene
                        //  - altrimenti primo metodo abilitato → fallback a bonifico
                        $oldMethod     = old('payment_method');
                        $defaultMethod = ($oldMethod && in_array($oldMethod, $enabledMethods, true))
                            ? $oldMethod
                            : ($enabledMethods[0] ?? 'bonifico');
                    @endphp

                    @if (empty($enabledMethods))
                        <div class="alert alert-warning" style="padding:16px;border-radius:8px;background:#fff7e6;border:1px solid #ffd591;color:#874d00;">
                            ⚠️ Al momento non &egrave; possibile completare il pagamento online.
                            Per iscriverti al corso contatta la segreteria.
                        </div>
                    @else
                    <div class="pay-options">
                        @if (in_array('stripe', $enabledMethods, true))
                        <label class="pay-opt">
                            <input type="radio" name="payment_method" value="stripe"
                                {{ $defaultMethod === 'stripe' ? 'checked' : '' }}>
                            <span class="pay-icon">💳</span>
                            <div class="pay-label">
                                <strong>Carta di credito / debito</strong>
                                <small>Visa, Mastercard, American Express — pagamento immediato e sicuro</small>
                            </div>
                        </label>
                        @endif
                        @if (in_array('paypal', $enabledMethods, true))
                        <label class="pay-opt">
                            <input type="radio" name="payment_method" value="paypal"
                                {{ $defaultMethod === 'paypal' ? 'checked' : '' }}>
                            <span class="pay-icon" style="font-size:1.3rem;font-weight:900;color:#003087;">Pay<span style="color:#009cde;">Pal</span></span>
                            <div class="pay-label">
                                <strong>PayPal</strong>
                                <small>Paga con il tuo conto PayPal in totale sicurezza</small>
                            </div>
                        </label>
                        @endif
                        @if (in_array('bonifico', $enabledMethods, true))
                        <label class="pay-opt">
                            <input type="radio" name="payment_method" value="bonifico"
                                {{ $defaultMethod === 'bonifico' ? 'checked' : '' }}>
                            <span class="pay-icon">🏦</span>
                            <div class="pay-label">
                                <strong>Bonifico bancario</strong>
                                <small>Riceverai IBAN e causale via email — attivazione dopo conferma</small>
                            </div>
                        </label>
                        @endif
                    </div>
                    @endif

                    <div class="privacy-check">
                        <input type="checkbox" name="privacy" id="privacy" {{ old('privacy') ? 'checked' : '' }}>
                        <label for="privacy">
                            Ho letto e accetto la
                            <a href="{{ route('privacy') }}" target="_blank">Informativa sulla Privacy</a>
                            e acconsento al trattamento dei miei dati personali per finalità contrattuali.
                        </label>
                    </div>
                    @error('privacy')<div class="err-msg" style="margin-top:6px;">{{ $message }}</div>@enderror

                    <button type="submit" class="btn-submit" id="btnSubmit">
                        <span id="btnText">🔒 Procedi al pagamento</span>
                        <span id="btnSpinner" style="display:none;">⏳ Attendi…</span>
                    </button>
                    <p class="secure-note">🛡 Connessione cifrata SSL — i tuoi dati sono al sicuro</p>
                </div>
            </div>

        </form>
    </div>

</div>

<script>
function setBillingType(type, el) {
    document.getElementById('billing_type').value = type;
    document.querySelectorAll('.billing-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('fields-private').style.display = type === 'private' ? '' : 'none';
    document.getElementById('fields-company').style.display = type === 'company' ? '' : 'none';
}
document.getElementById('checkoutForm').addEventListener('submit', function() {
    const btn = document.getElementById('btnSubmit');
    btn.disabled = true;
    document.getElementById('btnText').style.display    = 'none';
    document.getElementById('btnSpinner').style.display = 'inline';
});
</script>

@endsection
