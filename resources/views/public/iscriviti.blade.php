@extends('public.layout')

@section('title', 'Iscriviti — ' . config('app.name'))
@section('description', 'Compila il modulo per richiedere informazioni sui nostri corsi. Ti ricontattiamo entro 24 ore.')

@push('styles')
<style>
    :root {
        --blue:      #0057d9;
        --blue-dark: #001b3f;
        --blue-deep: #001126;
        --yellow:    #ffd800;
        --border-f:  #dbe7f4;
    }

    /* ── OVERRIDE NAV ── */
    nav {
        background: linear-gradient(90deg, #001126, #061b3f) !important;
        border-bottom: none !important;
        height: 92px !important;
        padding: 0 max(20px, calc((100vw - 1120px) / 2)) !important;
        box-shadow: 0 8px 30px rgba(0,0,0,.18) !important;
    }
    .nav-brand { color: #fff !important; }
    .nav-brand img { height: 74px !important; }
    .nav-links a { color: rgba(255,255,255,.9) !important; font-size: 14px !important; font-weight: 700 !important; }
    .nav-links a:hover { color: #49a1ff !important; }
    .nav-links .btn-primary {
        background: #0069f2 !important;
        color: #fff !important;
        font-size: 14px !important;
        font-weight: 800 !important;
        padding: 18px 28px !important;
        border-radius: 7px !important;
        box-shadow: 0 10px 25px rgba(0,105,242,.3) !important;
    }

    /* ── HERO ── */
    .page-hero {
        background: linear-gradient(110deg, #001126 0%, #003580 55%, #0057d9 100%);
        color: #fff;
        padding: 4rem 1.5rem 3.5rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .page-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background: url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1600&q=60') center / cover;
        opacity: .08;
    }
    .page-hero-inner { position: relative; }
    .page-hero h1 { font-size: clamp(1.75rem, 4vw, 2.6rem); font-weight: 900; margin-bottom: .75rem; letter-spacing: -.5px; }
    .page-hero p   { color: #ccdcf7; font-size: 1.05rem; max-width: 500px; margin: 0 auto; }

    /* ── FORM SECTION ── */
    .form-section {
        padding: 3.5rem 1.5rem 4rem;
        background: #f6faff;
    }
    .form-wrap {
        max-width: 700px;
        margin: 0 auto;
        background: #fff;
        border-radius: 18px;
        border: 1.5px solid var(--border-f);
        padding: 2.5rem;
        box-shadow: 0 18px 50px rgba(0,37,91,.12);
    }
    .form-title { font-size: 1.25rem; font-weight: 800; margin-bottom: .3rem; color: #001b3f; }
    .form-sub { color: #526173; font-size: .875rem; margin-bottom: 2rem; }

    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; }
    .form-group { display: flex; flex-direction: column; gap: .3rem; }
    .form-group.full { grid-column: 1 / -1; }

    label { font-size: .83rem; font-weight: 700; color: #001b3f; }
    label .req { color: var(--blue); margin-left: 2px; }

    input[type=text],
    input[type=email],
    input[type=tel],
    select,
    textarea {
        width: 100%;
        border: 1.5px solid var(--border-f);
        border-radius: 8px;
        padding: .65rem .9rem;
        font-size: .9rem;
        font-family: inherit;
        color: #001b3f;
        background: #f6faff;
        transition: border-color .2s, box-shadow .2s;
        outline: none;
    }
    input:focus, select:focus, textarea:focus {
        border-color: var(--blue);
        box-shadow: 0 0 0 3px rgba(0,87,217,.12);
        background: #fff;
    }
    textarea { resize: vertical; min-height: 100px; }
    select {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%230057d9' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right .7rem center;
        background-size: 1.2rem;
        padding-right: 2.5rem;
    }

    .privacy-group { display: flex; gap: .75rem; align-items: flex-start; }
    .privacy-group input[type=checkbox] { width: 18px; height: 18px; margin-top: 3px; flex-shrink: 0; accent-color: var(--blue); }
    .privacy-text { font-size: .82rem; color: #526173; font-weight: 400; }
    .privacy-text a { color: var(--blue); }

    .error-msg { color: #dc2626; font-size: .78rem; margin-top: .15rem; }

    .submit-btn {
        width: 100%;
        padding: .9rem;
        background: var(--blue);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 800;
        cursor: pointer;
        transition: background .2s, transform .1s, box-shadow .2s;
        font-family: inherit;
        margin-top: .75rem;
        letter-spacing: .02em;
        box-shadow: 0 10px 25px rgba(0,87,217,.25);
    }
    .submit-btn:hover { background: #0046b8; transform: translateY(-1px); box-shadow: 0 14px 30px rgba(0,87,217,.35); }
    .submit-btn:active { transform: scale(.99); }

    .trust-badges {
        display: flex;
        justify-content: center;
        gap: 2rem;
        flex-wrap: wrap;
        margin-top: 2rem;
        color: #526173;
        font-size: .82rem;
    }
    .trust-badge { display: flex; align-items: center; gap: .4rem; }

    /* ── FOOTER OVERRIDE ── */
    footer {
        background: #001126 !important;
        color: #a8b4c8 !important;
        padding: 18px 2rem !important;
        margin-top: 0 !important;
    }

    @media (max-width: 520px) {
        .form-grid  { grid-template-columns: 1fr; }
        .form-wrap  { padding: 1.75rem 1.25rem; }
    }
</style>
@endpush

@section('content')

<div class="page-hero">
    <div class="page-hero-inner">
        <h1>Prenota il tuo test gratuito</h1>
        <p>Compila il modulo — ti ricontattiamo entro 24 ore per trovare il corso più adatto a te.</p>
    </div>
</div>

<section class="form-section">
    <div class="form-wrap">
        <div class="form-title">Modulo di contatto</div>
        <div class="form-sub">Tutti i campi con <span style="color:var(--blue)">*</span> sono obbligatori.</div>

        @if ($errors->any())
            <div style="background:#eff6ff;border:1px solid #93c5fd;border-radius:8px;padding:1rem;margin-bottom:1.5rem;font-size:.875rem;color:#1d4ed8;">
                <strong>Correggi i seguenti errori:</strong>
                <ul style="margin-top:.4rem;padding-left:1.2rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('iscrizione.store') }}" method="POST" novalidate>
            @csrf

            <div class="form-grid">

                <div class="form-group">
                    <label for="first_name">Nome <span class="req">*</span></label>
                    <input type="text" id="first_name" name="first_name"
                           value="{{ old('first_name') }}" placeholder="Mario" required>
                    @error('first_name') <span class="error-msg">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="last_name">Cognome <span class="req">*</span></label>
                    <input type="text" id="last_name" name="last_name"
                           value="{{ old('last_name') }}" placeholder="Rossi" required>
                    @error('last_name') <span class="error-msg">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="email">Email <span class="req">*</span></label>
                    <input type="email" id="email" name="email"
                           value="{{ old('email') }}" placeholder="mario@email.it" required>
                    @error('email') <span class="error-msg">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="phone">Telefono</label>
                    <input type="tel" id="phone" name="phone"
                           value="{{ old('phone') }}" placeholder="+39 333 0000000">
                    @error('phone') <span class="error-msg">{{ $message }}</span> @enderror
                </div>

                <div class="form-group full">
                    <label for="course_interest">Corso di interesse</label>
                    <select id="course_interest" name="course_interest">
                        <option value="">— Seleziona una lingua —</option>
                        @foreach(\App\Support\LanguageOptions::all() as $key => $label)
                            <option value="{{ $key }}" @selected(old('course_interest') === $key)>{{ $label }}</option>
                        @endforeach
                        <option value="Altro" @selected(old('course_interest') === 'Altro')>Altro</option>
                    </select>
                    @error('course_interest') <span class="error-msg">{{ $message }}</span> @enderror
                </div>

                <div class="form-group full">
                    <label for="message">Messaggio / Domande</label>
                    <textarea id="message" name="message"
                              placeholder="Raccontaci il tuo livello attuale, i tuoi obiettivi, preferenze di orario…">{{ old('message') }}</textarea>
                    @error('message') <span class="error-msg">{{ $message }}</span> @enderror
                </div>

                <div class="form-group full">
                    <div class="privacy-group">
                        <input type="checkbox" id="privacy" name="privacy" value="1"
                               @checked(old('privacy'))>
                        <label for="privacy" class="privacy-text">
                            Ho letto e accetto la <a href="{{ route('privacy') }}" target="_blank">Privacy Policy</a>.
                            I miei dati saranno trattati esclusivamente per rispondere alla mia richiesta.
                            <span class="req">*</span>
                        </label>
                    </div>
                    @error('privacy') <span class="error-msg">{{ $message }}</span> @enderror
                </div>

            </div>

            <button type="submit" class="submit-btn">Invia richiesta →</button>
        </form>
    </div>

    <div class="trust-badges">
        <div class="trust-badge">🔒 Dati protetti (GDPR)</div>
        <div class="trust-badge">⚡ Risposta entro 24h</div>
        <div class="trust-badge">✅ Nessun obbligo</div>
    </div>
</section>

@endsection
