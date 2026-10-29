@extends('public.layout')

@section('title', (isset($configurator) ? 'Corso di lingua' : $course->name) . ' a Roma — Iscrizione | A&A Language Center')
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
        { "@@type": "ListItem", "position": 3, "name": @json((isset($configurator) ? 'Corso di lingua' : $course->name)), "item": "{{ (isset($configurator) ? route('checkout.acquista') : route('checkout.show', $course->id)) }}" }
    ]
}
</script>
@endsection

@section('extra-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Course",
    "name": @json((isset($configurator) ? 'Corso di lingua' : $course->name)),
    "description": @json(strip_tags($course->description ?? $course->short_description ?? 'Corso di ' . ($course->language_id ?? 'lingue') . ' a Roma con docenti madrelingua.')),
    "url": "{{ (isset($configurator) ? route('checkout.acquista') : route('checkout.show', $course->id)) }}",
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
        "price": "{{ number_format((float)$course->total_price, 2, '.', '') }}",
        "priceCurrency": "EUR",
        "availability": "https://schema.org/InStock",
        "url": "{{ (isset($configurator) ? route('checkout.acquista') : route('checkout.show', $course->id)) }}",
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
    .sidebar-photo img { display: block; width: 100%; aspect-ratio: 16 / 5; object-fit: cover; }
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
    .bill-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 12px 14px; margin-top: 16px; }
    .bill-grid .s1 { grid-column: span 1; } .bill-grid .s2 { grid-column: span 2; } .bill-grid .s3 { grid-column: span 3; }
    .bill-grid.is-company .f-email, .bill-grid.is-company .f-phone { grid-column: span 3; }
    .bill-grid.is-company .only-private { display: none; }
    .bill-grid:not(.is-company) .only-company { display: none; }
    @media (max-width: 640px) {
        .bill-grid .s1, .bill-grid .s2, .bill-grid .s3, .bill-grid.is-company .f-email, .bill-grid.is-company .f-phone { grid-column: 1 / -1; }
        .bill-grid .m4 { grid-column: span 4; } .bill-grid .m2 { grid-column: span 2; }
    }
    .req { color: #dc2626; font-weight: 800; }
    .req-note { font-size: 12px; color: var(--muted); margin: 14px 0 0; }
    .sidebar-photo img { max-height: 96px; }
    .choice-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
    @media (max-width: 640px) { .choice-grid { grid-template-columns: 1fr; } }
    .choice-grid select { width: 100%; text-overflow: ellipsis; }
    .choice-grid select:disabled { background: #f3f6fb; color: #94a3b8; cursor: not-allowed; }
    .choice-hint { font-size: 12px; color: var(--muted); margin-top: 12px; }

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


    /* ── Pagamento nella barra laterale ── */
    .sidebar-pay { padding: 20px 22px 22px; border-top: 1px solid var(--border); background: linear-gradient(180deg, #f8fbff, #fff); }
    .sidebar-pay .pay-title {
        display: flex; align-items: center; gap: 8px;
        font-size: 12px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase;
        color: var(--blue-dark); margin-bottom: 14px;
    }
    .sidebar-pay .pay-options { gap: 10px; }
    .sidebar-pay .pay-opt { padding: 12px 14px; gap: 12px; border-radius: 12px; background: #fff; }
    .sidebar-pay .pay-opt:hover { border-color: #93c5fd; }
    .sidebar-pay .pay-opt:has(input:checked) { border-color: var(--blue); background: #eff6ff; box-shadow: 0 6px 18px rgba(0,87,217,.12); }
    .sidebar-pay .pay-icon { font-size: 1.35rem; }
    .sidebar-pay .pay-label strong { font-size: 13px; }
    .sidebar-pay .pay-label small { font-size: 11px; line-height: 1.35; display: block; }
    .sidebar-pay .privacy-check { font-size: 12px; margin-top: 16px; }
    .sidebar-pay .btn-submit { background: linear-gradient(110deg, #0057d9, #003d94); margin-top: 16px; }
    .sidebar-pay .btn-submit:hover { background: linear-gradient(110deg, #0045b3, #002f73); }
    .sidebar-pay .btn-submit:disabled { background: #94a3b8; }

    /* ── Barra laterale compatta ── */
    .checkout-wrap { max-width: 1180px; grid-template-columns: 1fr 380px; gap: 22px; }
    .sidebar { top: 16px; }
    .sidebar-header { padding: 10px 16px; }
    .sidebar-header .course-title { font-size: 15px; margin-top: 0; }
    .sidebar-body { padding: 8px 16px 12px; }
    .sidebar .order-row { padding: 5px 0; font-size: 12.5px; }
    .sidebar .order-total { padding: 9px 14px; margin-top: 8px; }
    .sidebar .order-total .label { font-size: 13px; }
    .sidebar .order-total .val { font-size: 19px; }
    .sidebar-pay { padding: 12px 16px 14px; }
    .sidebar-pay .pay-title { margin-bottom: 8px; font-size: 11px; }
    .sidebar-pay .pay-options { gap: 6px; }
    .sidebar-pay .pay-opt { padding: 8px 12px; gap: 10px; border-width: 1.5px; }
    .sidebar-pay .pay-label small { display: none; }
    .sidebar-pay .pay-label strong { font-size: 12.5px; }
    .sidebar-pay .pay-icon { font-size: 1.1rem; }
    .sidebar-pay .privacy-check { margin-top: 10px; font-size: 11.5px; line-height: 1.4; }
    .sidebar-pay .btn-submit { margin-top: 10px; padding: 11px; font-size: 14px; }
    .sidebar-pay .secure-note { margin: 6px 0 0; font-size: 10.5px; }
    .sidebar-trust { padding: 8px 16px; font-size: 11px; line-height: 1.5; }
    .sidebar-pay .pay-label strong { white-space: nowrap; }
    .sidebar-trust { white-space: nowrap; }
    @media (max-width: 760px) { .sidebar-trust { white-space: normal; } }
    .sidebar-photo img { max-height: 70px; }

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
        {{ (isset($configurator) ? 'Corso di lingua' : $course->name) }}
    </div>
</div>

<div class="checkout-wrap">

    {{-- ── SIDEBAR RIEPILOGO ──────────────────────────────── --}}
    <div class="sidebar" style="order:2;">
        <div class="sidebar-photo" id="sb-photo">
            <img id="sb-photo-img" src="{{ isset($configurator) ? (new \App\Models\Course)->image_url : $course->image_url }}" data-default="{{ (new \App\Models\Course)->image_url }}" alt="{{ isset($configurator) ? 'Corso di lingua' : $course->name }}">
        </div>
        <div class="sidebar-header">
            <div class="course-title">{{ (isset($configurator) ? 'Corso di lingua' : $course->name) }}</div>
            @if(! isset($configurator) && $course->level)
                <div style="font-size:12px;margin-top:4px;opacity:.75;">{{ $course->level }}</div>
            @endif
        </div>
        <div class="sidebar-body">
            @if(isset($configurator))
                <div class="order-row"><span class="label">&#127760; Lingua</span><span class="val" id="sb-lang">—</span></div>
                <div class="order-row"><span class="label">&#9201; Ore incluse</span><span class="val" id="sb-hours">—</span></div>
                <div class="order-row"><span class="label">👤 Tipo</span><span class="val" id="sb-type">—</span></div>
                <div class="order-row" id="sb-enroll-row" style="display:none;"><span class="label">Quota iscrizione</span><span class="val" id="sb-enroll"></span></div>
                <div class="order-row" id="sb-price-row" style="display:none;"><span class="label">Quota corso</span><span class="val" id="sb-price"></span></div>
                <div class="order-total">
                    <span class="label">Totale</span>
                    <span class="val" id="sb-total">—</span>
                </div>
            @else
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
            @endif
        </div>
        <div class="sidebar-pay">
            <div class="pay-title"><span>🔒</span> Scegli come pagare</div>

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
                            <input form="checkoutForm" type="radio" name="payment_method" value="stripe"
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
                            <input form="checkoutForm" type="radio" name="payment_method" value="paypal"
                                {{ $defaultMethod === 'paypal' ? 'checked' : '' }}>
                            <span class="pay-icon" style="font-size:1.3rem;font-weight:900;color:#003087;">Pay<span style="color:#009cde;">Pal</span></span>
                            <div class="pay-label">
                                <strong style="display:none;">PayPal</strong>
                                <small>Paga con il tuo conto PayPal in totale sicurezza</small>
                            </div>
                        </label>
                        @endif
                        @if (in_array('bonifico', $enabledMethods, true))
                        <label class="pay-opt">
                            <input form="checkoutForm" type="radio" name="payment_method" value="bonifico"
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
                        <input form="checkoutForm" type="checkbox" name="privacy" id="privacy" {{ old('privacy') ? 'checked' : '' }}>
                        <label for="privacy">
                            Ho letto e accetto la
                            <a href="{{ route('privacy') }}" target="_blank">Informativa sulla Privacy</a>
                            e acconsento al trattamento dei miei dati personali per finalità contrattuali.
                        </label>
                    </div>
                    @error('privacy')<div class="err-msg" style="margin-top:6px;">{{ $message }}</div>@enderror

                    <button form="checkoutForm" type="submit" class="btn-submit" id="btnSubmit" disabled>
                        <span id="btnText">🔒 Procedi al pagamento</span>
                        <span id="btnSpinner" style="display:none;">⏳ Attendi…</span>
                    </button>
                    <p class="secure-note" id="fill-hint" style="color:#b45309;margin-bottom:0;">Compila tutti i campi obbligatori (*) e accetta la privacy per procedere.</p>
                    <p class="secure-note">🛡 Connessione cifrata SSL — i tuoi dati sono al sicuro</p>
        </div>
        <div class="sidebar-trust">
            🔒 Pagamento sicuro con crittografia SSL<br>
            ✅ Contratto creato automaticamente dopo il pagamento<br>
            📞 Supporto: <a href="tel:+390657437364" style="color:var(--blue);">06 5743734</a>
        </div>
    </div>

    {{-- ── FORM CHECKOUT ───────────────────────────────────── --}}
    <div style="order:1;">

        <form method="POST" action="{{ isset($configurator) ? route('checkout.acquista.store') : route('checkout.store', $course) }}" id="checkoutForm">
            @csrf

            @if($errors->any())
            <div class="alert-errors">
                <strong>Correggi questi errori per procedere:</strong>
                <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
            @endif

            {{-- ── Prodotto variabile: scelta lingua / ore / tipologia ─── --}}
            @if(isset($configurator))
            <div class="form-card">
                <div class="form-card-header">
                    <div class="step-num">&#9733;</div>
                    <h2>Scegli il tuo corso</h2>
                </div>
                <div class="form-body">
                    <input type="hidden" name="course_id" id="course_id" value="{{ $selectedCourseId }}">
                    <div class="choice-grid">
                        <div class="field">
                            <label for="sel-language">Lingua</label>
                            <select name="language" id="sel-language">
                                <option value="">Scegli la lingua</option>
                                @foreach($languages as $lang)
                                    <option value="{{ $lang }}" {{ $selectedLanguage === $lang ? 'selected' : '' }}>{{ $lang }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="sel-hours">Ore di corso</label>
                            <select id="sel-hours"></select>
                        </div>
                        <div class="field">
                            <label for="sel-type">Tipologia di lezioni</label>
                            <select id="sel-type"></select>
                        </div>
                    </div>
                    <p class="choice-hint" id="choice-hint">Scegli lingua, ore e tipo di lezione per continuare.</p>
                    <noscript><p style="color:#991b1b;font-size:13px;margin-top:10px;">Abilita JavaScript per scegliere ore e tipologia.</p></noscript>
                    @error('course_id')<div class="err-msg" style="margin-top:6px;">{{ $message }}</div>@enderror
                    @error('language')<div class="err-msg" style="margin-top:6px;">{{ $message }}</div>@enderror
                </div>
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

                    @php $isCompany = old('billing_type') === 'company'; @endphp
                    <div class="bill-grid {{ $isCompany ? 'is-company' : '' }}" id="bill-grid">

                        {{-- Solo privato --}}
                        <div class="field only-private s3">
                            <label>Nome <b class="req">*</b></label>
                            <input type="text" name="billing_first_name" data-req="private"
                                value="{{ old('billing_first_name', $prefill['billing_first_name'] ?? '') }}"
                                class="{{ $errors->has('billing_first_name') ? 'err' : '' }}" autocomplete="given-name">
                            @error('billing_first_name')<div class="err-msg">{{ $message }}</div>@enderror
                        </div>
                        <div class="field only-private s3">
                            <label>Cognome <b class="req">*</b></label>
                            <input type="text" name="billing_last_name" data-req="private"
                                value="{{ old('billing_last_name', $prefill['billing_last_name'] ?? '') }}"
                                class="{{ $errors->has('billing_last_name') ? 'err' : '' }}" autocomplete="family-name">
                            @error('billing_last_name')<div class="err-msg">{{ $message }}</div>@enderror
                        </div>

                        {{-- Solo azienda --}}
                        <div class="field only-company s3">
                            <label>Ragione sociale <b class="req">*</b></label>
                            <input type="text" name="company_name" data-req="company" value="{{ old('company_name') }}"
                                class="{{ $errors->has('company_name') ? 'err' : '' }}">
                            @error('company_name')<div class="err-msg">{{ $message }}</div>@enderror
                        </div>
                        <div class="field only-company s3">
                            <label>Partita IVA <b class="req">*</b></label>
                            <input type="text" name="vat_number" data-req="company" value="{{ old('vat_number') }}"
                                class="{{ $errors->has('vat_number') ? 'err' : '' }}">
                            @error('vat_number')<div class="err-msg">{{ $message }}</div>@enderror
                        </div>

                        {{-- Comuni --}}
                        <div class="field only-private s2">
                            <label>Codice fiscale <b class="req">*</b></label>
                            <input type="text" name="billing_tax_code" data-req="private" value="{{ old('billing_tax_code') }}"
                                placeholder="RSSMRA80A01H501U" style="text-transform:uppercase;"
                                class="{{ $errors->has('billing_tax_code') ? 'err' : '' }}">
                            @error('billing_tax_code')<div class="err-msg">{{ $message }}</div>@enderror
                        </div>
                        <div class="field f-email s2">
                            <label>Email <b class="req">*</b></label>
                            <input type="email" name="billing_email" data-req="all"
                                value="{{ old('billing_email', $prefill['billing_email'] ?? '') }}"
                                class="{{ $errors->has('billing_email') ? 'err' : '' }}" autocomplete="email">
                            @error('billing_email')<div class="err-msg">{{ $message }}</div>@enderror
                        </div>
                        <div class="field f-phone s2">
                            <label>Telefono</label>
                            <input type="tel" name="billing_phone" value="{{ old('billing_phone') }}"
                                placeholder="+39 06 5743734" autocomplete="tel">
                        </div>
                        <div class="field s3 m6">
                            <label>Indirizzo <b class="req">*</b></label>
                            <input type="text" name="billing_address" data-req="all" value="{{ old('billing_address') }}"
                                placeholder="Via Roma, 1" autocomplete="street-address"
                                class="{{ $errors->has('billing_address') ? 'err' : '' }}">
                            @error('billing_address')<div class="err-msg">{{ $message }}</div>@enderror
                        </div>
                        <div class="field s2 m4">
                            <label>Città <b class="req">*</b></label>
                            <input type="text" name="billing_city" data-req="all" value="{{ old('billing_city') }}" autocomplete="address-level2"
                                class="{{ $errors->has('billing_city') ? 'err' : '' }}">
                            @error('billing_city')<div class="err-msg">{{ $message }}</div>@enderror
                        </div>
                        <div class="field s1 m2">
                            <label>CAP <b class="req">*</b></label>
                            <input type="text" name="billing_zip" data-req="all" value="{{ old('billing_zip') }}"
                                placeholder="00145" maxlength="5" inputmode="numeric" autocomplete="postal-code"
                                class="{{ $errors->has('billing_zip') ? 'err' : '' }}">
                            @error('billing_zip')<div class="err-msg">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <p class="req-note"><b class="req">*</b> Campi obbligatori per la fatturazione</p>
                    <input type="hidden" name="billing_country" value="IT">
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
    document.getElementById('bill-grid').classList.toggle('is-company', type === 'company');
    if (window.updateSubmit) window.updateSubmit();
}

// Pulsante di pagamento attivo solo con tutti i campi obbligatori compilati, privacy accettata
// e (nella pagina "Corso di lingua") corso scelto. Il server ricontrolla tutto.
window.courseReady = {{ isset($configurator) ? 'false' : 'true' }};
window.updateSubmit = function () {
    const form = document.getElementById('checkoutForm');
    const btn  = document.getElementById('btnSubmit');
    const type = document.getElementById('billing_type').value;
    let ok = window.courseReady;
    form.querySelectorAll('[data-req]').forEach(el => {
        const r = el.dataset.req;
        if (r !== 'all' && r !== type) return;
        if (el.value.trim() === '' || (el.type === 'email' && !el.checkValidity())) ok = false;
    });
    const privacy = document.getElementById('privacy');
    if (privacy && !privacy.checked) ok = false;
    if (!document.querySelector('input[name=payment_method]:checked')) ok = false;
    btn.disabled = !ok;
    const hint = document.getElementById('fill-hint');
    if (hint) hint.style.display = ok ? 'none' : '';
};
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('checkoutForm');
    form.addEventListener('input', window.updateSubmit);
    form.addEventListener('change', window.updateSubmit);
    document.querySelectorAll('input[form=checkoutForm]').forEach(el => el.addEventListener('change', window.updateSubmit));
    window.updateSubmit();
});
@if(isset($configurator))
(function () {
    // Il prezzo qui è solo informativo: l'importo reale viene riletto dal Corso collegato lato server.
    // La lingua non cambia il prezzo: ore e tipologia decidono quale Corso si compra.
    // Ordinati per prezzo: a parità di ore e tipologia si propone il Corso più economico.
    const VARIANTS = @json($variantsJson).map(v => ({...v, key: v.type})).sort((a, b) => a.total - b.total);
    const hidden   = document.getElementById('course_id');
    const selLang  = document.getElementById('sel-language');
    const selHours = document.getElementById('sel-hours');
    const selType  = document.getElementById('sel-type');
    const btn      = document.getElementById('btnSubmit');
    const hint     = document.getElementById('choice-hint');
    const photo    = document.getElementById('sb-photo-img');
    const eur = n => '€ ' + Number(n).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    const uniq = a => a.filter((x, i) => a.indexOf(x) === i);

    // Stato: nessuna scelta preselezionata (salvo ripristino dopo un errore di validazione)
    const restored = VARIANTS.find(v => v.id === parseInt(hidden.value, 10)) || null;
    let hours   = restored ? restored.hours : null;
    let typeKey = restored ? restored.key : '';

    function fill(select, placeholder, options, selected) {
        select.innerHTML = '';
        const ph = document.createElement('option');
        ph.value = ''; ph.textContent = placeholder;
        select.appendChild(ph);
        options.forEach(o => {
            const opt = document.createElement('option');
            opt.value = o.value;
            opt.textContent = o.label;
            if (String(o.value) === String(selected)) opt.selected = true;
            select.appendChild(opt);
        });
    }

    function render() {
        const forHours = hours === null ? [] : VARIANTS.filter(v => v.hours === hours);
        if (typeKey && !forHours.some(v => v.key === typeKey)) typeKey = '';
        const current = forHours.find(v => v.key === typeKey) || null;

        fill(selHours, 'Scegli le ore',
            uniq(VARIANTS.map(v => v.hours)).sort((a, b) => a - b).map(h => {
                const v = VARIANTS.find(x => x.hours === h);
                return {value: h, label: v.hoursLabel + (v.hoursLabel === '1' ? ' ora' : ' ore')};
            }), hours === null ? '' : hours);
        fill(selType, 'Scegli il tipo di lezione',
            uniq(forHours.map(v => v.key)).map(k => ({value: k, label: k})), typeKey);
        selType.disabled = hours === null;

        hidden.value = current ? current.id : '';

        const dash = '—';
        document.getElementById('sb-lang').textContent  = selLang.value || dash;
        document.getElementById('sb-hours').textContent = current ? current.hoursLabel : dash;
        document.getElementById('sb-type').textContent  = current ? current.type : dash;
        document.getElementById('sb-total').textContent = current ? eur(current.total) : dash;

        document.getElementById('sb-enroll-row').style.display = current && current.enrollment > 0 ? '' : 'none';
        document.getElementById('sb-price-row').style.display  = current && current.price > 0 ? '' : 'none';
        if (current) {
            document.getElementById('sb-enroll').textContent = eur(current.enrollment);
            document.getElementById('sb-price').textContent  = eur(current.price);
        }

        const src = (current && current.image) ? current.image : photo.dataset.default;
        if (photo.getAttribute('src') !== src) photo.src = src;

        const ready = !!(selLang.value && current);
        window.courseReady = ready;
        hint.style.display = ready ? 'none' : '';
        if (window.updateSubmit) window.updateSubmit();
    }

    selLang.addEventListener('change', render);
    selHours.addEventListener('change', () => { hours = selHours.value === '' ? null : parseFloat(selHours.value); render(); });
    selType.addEventListener('change', () => { typeKey = selType.value; render(); });

    render();
})();
@endif
document.getElementById('checkoutForm').addEventListener('submit', function() {
    const btn = document.getElementById('btnSubmit');
    btn.disabled = true;
    document.getElementById('btnText').style.display    = 'none';
    document.getElementById('btnSpinner').style.display = 'inline';
});
</script>

@endsection
