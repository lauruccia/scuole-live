@extends('public.layout')

@section('title', \App\Models\PageContent::text('iscriviti', 'meta_title'))
@section('description', \App\Models\PageContent::text('iscriviti', 'meta_description'))
@section('keywords', \App\Models\PageContent::text('iscriviti', 'meta_keywords'))

@section('breadcrumb-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ route('home') }}" },
        { "@@type": "ListItem", "position": 2, "name": "Iscriviti", "item": "{{ route('iscrizione') }}" }
    ]
}
</script>
@endsection

@push('styles')
<style>
/* ── PAGINA: ISCRIVITI / FORM ─────────────────────── */

.form-page-hero {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 55%, var(--navy-light) 100%);
    color: #fff; padding: 60px 0 52px; text-align: center;
    position: relative; overflow: hidden;
}
.form-page-hero::before {
    content: '';
    position: absolute; inset: 0;
    background: url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1600&q=40') center/cover;
    opacity: .06;
}
.form-page-hero-inner { position: relative; z-index: 1; }
.form-page-hero h1 { font-size: clamp(1.9rem, 4vw, 2.6rem); font-weight: 800; letter-spacing: -.04em; margin-bottom: 10px; }
.form-page-hero p { font-size: 1rem; color: rgba(255,255,255,.72); max-width: 480px; margin: 0 auto; }

/* FORM SECTION */
.form-section { padding: 64px 0; background: var(--bg); }
.form-wrap {
    max-width: 720px; margin: 0 auto;
    background: var(--white); border-radius: var(--radius-lg);
    border: 1.5px solid var(--border); padding: 48px 44px;
    box-shadow: var(--shadow);
}

.form-header { margin-bottom: 28px; }
.form-header h2 { font-size: 1.35rem; font-weight: 800; margin-bottom: 4px; color: var(--navy); }
.form-header p  { font-size: .875rem; color: var(--muted); }
.form-header .req-note { color: var(--blue); }

/* GRID */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-group { display: flex; flex-direction: column; gap: 5px; }
.form-group.full { grid-column: 1 / -1; }

label { font-size: .8rem; font-weight: 700; color: var(--navy); }
label .req { color: var(--blue); margin-left: 2px; }

input[type=text],
input[type=email],
input[type=tel],
select,
textarea {
    width: 100%;
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    padding: .65rem .9rem;
    font-size: .9rem; font-family: inherit;
    color: var(--text); background: var(--bg);
    transition: border-color .2s, box-shadow .2s;
    outline: none;
}
input:focus, select:focus, textarea:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px rgba(26,86,219,.1);
    background: var(--white);
}
input::placeholder, textarea::placeholder { color: #aab4c2; }
textarea { resize: vertical; min-height: 110px; }
select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%231A56DB' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right .7rem center; background-size: 1.2rem;
    padding-right: 2.5rem; cursor: pointer;
}

/* Privacy */
.privacy-group { display: flex; gap: .75rem; align-items: flex-start; }
.privacy-group input[type=checkbox] { width: 18px; height: 18px; margin-top: 2px; flex-shrink: 0; accent-color: var(--blue); }
.privacy-text { font-size: .82rem; color: var(--muted); font-weight: 400; }
.privacy-text a { color: var(--blue); }

/* Error */
.error-msg { color: #dc2626; font-size: .78rem; margin-top: 2px; }

/* Error box */
.error-box {
    background: #FEF2F2; border: 1.5px solid #FECACA;
    border-radius: var(--radius); padding: 14px 18px; margin-bottom: 22px;
    font-size: .875rem; color: #B91C1C;
}
.error-box strong { display: block; margin-bottom: 6px; }
.error-box ul { padding-left: 1.2rem; margin: 0; }

/* Submit */
.submit-btn {
    width: 100%; padding: .875rem;
    background: var(--blue); color: #fff; border: none;
    border-radius: var(--radius);
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1rem; font-weight: 700; cursor: pointer;
    transition: all .2s; margin-top: .5rem;
    box-shadow: 0 8px 24px rgba(26,86,219,.25);
}
.submit-btn:hover { background: var(--blue-h); transform: translateY(-2px); box-shadow: 0 12px 32px rgba(26,86,219,.35); }
.submit-btn:active { transform: scale(.99); }

/* Trust badges */
.trust-badges {
    display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap;
    margin-top: 28px; color: var(--muted); font-size: .82rem;
}
.trust-badge { display: flex; align-items: center; gap: .4rem; }

@media (max-width: 580px) {
    .form-grid { grid-template-columns: 1fr; }
    .form-wrap  { padding: 28px 20px; }
}
</style>
@endpush

@section('content')

<div class="form-page-hero">
    <div class="form-page-hero-inner">
        <h1>{{ \App\Models\PageContent::text('iscriviti', 'hero_title') }}</h1>
        <p>{{ \App\Models\PageContent::text('iscriviti', 'hero_text') }}</p>
    </div>
</div>

<section class="form-section">
    <div class="form-wrap">
        <div class="form-header">
            <h2>{{ \App\Models\PageContent::text('iscriviti', 'form_title') }}</h2>
            <p>Tutti i campi con <span class="req-note">*</span> sono obbligatori.</p>
        </div>

        @if ($errors->any())
            <div class="error-box">
                <strong>Correggi i seguenti errori:</strong>
                <ul>
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
                        <option value="Altro" @selected(old('course_interest') === 'Altro')>Altro / Non so ancora</option>
                    </select>
                    @error('course_interest') <span class="error-msg">{{ $message }}</span> @enderror
                </div>

                <div class="form-group full">
                    <label for="message">Messaggio / Domande</label>
                    <textarea id="message" name="message"
                              placeholder="Raccontaci il tuo livello attuale, i tuoi obiettivi, preferenze di orario o qualsiasi domanda…">{{ old('message') }}</textarea>
                    @error('message') <span class="error-msg">{{ $message }}</span> @enderror
                </div>

                <div class="form-group full">
                    <div class="privacy-group">
                        <input type="checkbox" id="privacy" name="privacy" value="1" @checked(old('privacy'))>
                        <label for="privacy" class="privacy-text">
                            Ho letto e accetto la <a href="{{ route('privacy') }}" target="_blank">Privacy Policy</a>.
                            I miei dati saranno trattati esclusivamente per rispondere alla mia richiesta.
                            <span class="req">*</span>
                        </label>
                    </div>
                    @error('privacy') <span class="error-msg">{{ $message }}</span> @enderror
                </div>

            </div>
            <button type="submit" class="submit-btn">✦ Invia richiesta →</button>
        </form>
    </div>

    <div class="trust-badges">
        <div class="trust-badge">🔒 Dati protetti (GDPR)</div>
        <div class="trust-badge">⚡ Risposta entro 24h</div>
        <div class="trust-badge">✅ Nessun obbligo</div>
        <div class="trust-badge">🆓 Test gratuito</div>
    </div>
</section>

@endsection
