@extends('public.layout')

@section('title', 'Scuola di Lingue a Roma | A&A Language Center San Paolo')
@section('description', 'Scuola di lingue a Roma San Paolo dal 2002. Corsi di inglese, spagnolo, francese, tedesco, arabo, italiano per stranieri con docenti madrelingua. Sede esami Trinity College London. Test di livello gratuito.')
@section('keywords', 'scuola di lingue Roma, scuola di lingue Roma San Paolo, corsi di lingue Roma, corsi di inglese Roma, docenti madrelingua Roma, Trinity College Roma, certificazioni internazionali lingue Roma, A&A Language Center')

@section('breadcrumb-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ route('home') }}" }
    ]
}
</script>
@endsection

@push('styles')
<style>
/* ════════════════════════════════════════════════════════
   HOME — DESIGN PREMIUM
   Colori layout: --navy #071428 | --blue #1A56DB | --gold #C9A42C
════════════════════════════════════════════════════════ */

/* Override nav CTA colore blu */
.btn-nav-cta {
    background: var(--blue) !important;
    color: #fff !important;
    box-shadow: 0 4px 16px rgba(26,86,219,.35);
}
.btn-nav-cta:hover {
    background: var(--blue-h) !important;
    box-shadow: 0 8px 24px rgba(26,86,219,.45) !important;
}

/* ── HERO ─────────────────────────────────────────── */
.hero {
    /* Stile "discreto":
       - Overlay scuro DENSO a sinistra (0–45%) per leggibilità del testo
       - Sfumatura rapida a metà
       - Foto PIENAMENTE VISIBILE a destra (60–100%, overlay quasi nullo)
       - Fondo navy come fallback in caso l'immagine non risponda
    */
    background:
        linear-gradient(95deg,
            rgba(7,20,40,.97) 0%,
            rgba(7,20,40,.94) 30%,
            rgba(7,20,40,.78) 45%,
            rgba(7,20,40,.30) 60%,
            rgba(7,20,40,.05) 100%),
        url('https://images.unsplash.com/photo-1543269865-cbf427effbad?auto=format&fit=crop&w=1920&q=85')
            center right / cover no-repeat,
        linear-gradient(135deg, #071428 0%, #0d1f3c 50%, #112350 100%);
    min-height: 620px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    align-items: center;
    overflow: hidden;
    position: relative;
}
.hero::before {
    /* Texture leggera a griglia SOLO sul lato sinistro (sotto al testo) */
    content: '';
    position: absolute; inset: 0 50% 0 0;
    background-image:
        linear-gradient(rgba(26,86,219,.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(26,86,219,.05) 1px, transparent 1px);
    background-size: 56px 56px;
    pointer-events: none;
    z-index: 1;
}
.hero-left {
    position: relative; z-index: 2;
    padding: 90px 56px 90px max(24px, calc((100vw - 1140px) / 2));
}
/* Logo scuola come watermark sfumato, fuso con la foto di sfondo dell'hero.
   mix-blend-mode: screen lo fa "emergere" dallo scuro; la mask radiale
   dissolve i bordi così non sembra un secondo logo appiccicato. */
.hero-logo-watermark {
    position: absolute;
    top: 50%;
    left: 58%;
    transform: translateY(-50%);
    height: min(440px, 75%);
    width: auto;
    opacity: .55;
    filter: drop-shadow(0 8px 30px rgba(0,0,0,.55));
    -webkit-mask-image: radial-gradient(closest-side, #000 62%, transparent 100%);
    mask-image: radial-gradient(closest-side, #000 62%, transparent 100%);
    pointer-events: none;
    z-index: 1;
}
@media (max-width: 1050px) {
    /* Su schermi stretti il testo occupa tutta la larghezza: il watermark
       sotto al testo disturberebbe la lettura. */
    .hero-logo-watermark { display: none; }
}
.hero-eyebrow {
    font-size: .68rem; font-weight: 700;
    letter-spacing: .14em; text-transform: uppercase;
    color: rgba(255,255,255,.45);
    margin-bottom: 22px;
}
.hero-eyebrow span { color: var(--blue); }
.hero h1 {
    font-size: clamp(2.4rem, 4.5vw, 3.6rem);
    font-weight: 800; line-height: 1.08;
    letter-spacing: -.05em; color: #fff;
    margin-bottom: 20px;
}
.hero h1 .accent { color: var(--blue); display: block; }
.hero h1 .h1-kicker {
    display: block;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .14em;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 14px;
    line-height: 1.4;
}
.hero-desc {
    font-size: 1rem; color: rgba(255,255,255,.62);
    line-height: 1.75; max-width: 480px; margin-bottom: 36px;
}
.hero-actions { display: flex; gap: 14px; flex-wrap: wrap; }
.btn-hero-primary {
    display: inline-flex; align-items: center; gap: 8px;
    padding: .85rem 1.8rem;
    background: var(--blue); color: #fff;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 700; font-size: .88rem;
    border-radius: 8px; letter-spacing: .03em;
    text-transform: uppercase;
    transition: all .2s;
}
.btn-hero-primary:hover { background: var(--blue-h); transform: translateY(-2px); }
.btn-hero-ghost {
    display: inline-flex; align-items: center; gap: 8px;
    padding: .85rem 1.8rem;
    background: rgba(255,255,255,.06);
    color: rgba(255,255,255,.85);
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 600; font-size: .88rem;
    border-radius: 8px; border: 1.5px solid rgba(255,255,255,.18);
    letter-spacing: .03em; text-transform: uppercase;
    transition: all .2s;
}
.btn-hero-ghost:hover { background: rgba(255,255,255,.10); border-color: rgba(255,255,255,.4); }

/* Hero right — solo contenitore per le floating cards
   (l'immagine di sfondo è applicata a .hero a tutta larghezza) */
.hero-right {
    position: relative; height: 600px;
    z-index: 2;
}
/* Badge Trinity in evidenza — unico elemento sopra la foto */
.hero-trinity-badge {
    position: absolute;
    right: 5%;
    bottom: 10%;
    display: flex; align-items: center; gap: 16px;
    background: rgba(255,255,255,.96);
    border-radius: 14px;
    border-left: 5px solid var(--gold);
    padding: 18px 24px;
    max-width: 400px;
    backdrop-filter: blur(8px);
    box-shadow: 0 14px 44px rgba(0,0,0,.35);
    transition: transform .2s, box-shadow .2s;
}
.hero-trinity-badge:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 56px rgba(0,0,0,.45);
}
.hero-trinity-badge img { height: 52px; width: auto; flex-shrink: 0; }
.htb-text strong {
    display: block; font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: .95rem; font-weight: 800; color: #0d1b2e; line-height: 1.25;
}
.htb-text > span { display: block; font-size: .76rem; color: var(--gold-d); font-weight: 600; margin-top: 3px; }

/* ── CERT STRIP ───────────────────────────────────── */
.cert-strip {
    background: #fff;
    border-top: 1px solid #e8eef8;
    border-bottom: 1px solid #e8eef8;
    padding: 28px 0;
}
.cert-strip-label {
    text-align: center; font-size: .65rem; font-weight: 700;
    letter-spacing: .14em; text-transform: uppercase;
    color: #9aadcb; margin-bottom: 20px;
}
.cert-logos {
    display: flex; gap: 24px;
    justify-content: center; align-items: center; flex-wrap: wrap;
}
.cert-logo-item {
    display: flex; align-items: center; justify-content: center;
    opacity: .72; transition: opacity .2s, transform .2s;
    padding: 4px 8px;
}
.cert-logo-item:hover { opacity: 1; transform: translateY(-2px); }
.cert-logo-item img {
    height: 40px; width: auto; max-width: 130px;
    object-fit: contain; display: block;
}

/* ── WHY / FEATURES ──────────────────────────────── */
.why-section {
    background: #f5f8fc;
    padding: 80px 0;
}
.why-grid {
    display: grid;
    grid-template-columns: 1fr 1.6fr;
    gap: 64px;
    align-items: start;
}
.why-left .sec-heading { font-size: clamp(1.6rem, 3vw, 2.1rem); }
.why-underline {
    width: 40px; height: 3px;
    background: var(--blue); border-radius: 2px;
    margin: 14px 0 20px;
}
.why-left p { font-size: .95rem; color: var(--muted); line-height: 1.75; }
.why-cta { margin-top: 28px; }

.features-grid {
    display: grid;
    grid-template-columns: repeat(3,1fr);
    gap: 14px;
}
.feature-card {
    background: #fff;
    border: 1px solid #e8eef8;
    border-radius: 14px;
    padding: 22px 18px;
    transition: border-color .2s, transform .2s;
}
.feature-card:hover {
    border-color: rgba(26,86,219,.25);
    transform: translateY(-3px);
}
.feature-icon {
    width: 44px; height: 44px; border-radius: 12px;
    background: var(--blue-l);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.25rem; margin-bottom: 14px;
}
.feature-card h4 { font-size: .88rem; font-weight: 700; color: var(--text); margin-bottom: 6px; }
.feature-card p  { font-size: .78rem; color: var(--muted); line-height: 1.6; margin: 0; }

/* ── CORSI SECTION (dark) ────────────────────────── */
.corsi-section {
    background: linear-gradient(135deg, #071428 0%, #0d1f3c 100%);
    padding: 80px 0;
    overflow: hidden;
}
.corsi-inner {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 48px;
    align-items: start;
}
.corsi-left { padding-top: 8px; }
.corsi-left .sec-heading { color: #fff; font-size: clamp(1.6rem, 3vw, 2.2rem); }
.corsi-left .sec-heading em { color: var(--blue); font-style: normal; }
.corsi-left .sec-subtext { color: rgba(255,255,255,.52); margin: 14px 0 28px; }
.btn-corsi-all {
    display: inline-flex; align-items: center; gap: 8px;
    padding: .7rem 1.4rem;
    border: 1.5px solid rgba(255,255,255,.25);
    color: rgba(255,255,255,.8);
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 600; font-size: .82rem;
    border-radius: 8px; letter-spacing: .04em;
    transition: all .2s;
}
.btn-corsi-all:hover { border-color: var(--blue); color: var(--blue); }

/* Scroller */
.corsi-scroll {
    display: flex; gap: 14px;
    overflow-x: auto; padding-bottom: 8px;
    scrollbar-width: thin; scrollbar-color: rgba(26,86,219,.3) transparent;
}
.corsi-scroll::-webkit-scrollbar { height: 4px; }
.corsi-scroll::-webkit-scrollbar-track { background: transparent; }
.corsi-scroll::-webkit-scrollbar-thumb { background: rgba(26,86,219,.4); border-radius: 4px; }

.corso-card {
    flex: 0 0 185px;
    border-radius: 14px; overflow: hidden;
    border: 1px solid rgba(255,255,255,.08);
    background: #111c30;
    cursor: pointer;
    transition: border-color .2s, transform .2s;
    display: flex; flex-direction: column;
}
.corso-card:hover { border-color: rgba(26,86,219,.5); transform: translateY(-4px); }

.corso-img {
    height: 220px;
    background-size: cover; background-position: center;
    position: relative;
}
.corso-img::after {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(to top,
        rgba(7,20,40,.95) 0%,
        rgba(7,20,40,.4) 50%,
        transparent 100%);
}
.corso-flag {
    position: absolute; top: 12px; left: 12px; z-index: 2;
    font-size: 1.6rem;
}
.corso-body { padding: 14px 14px 16px; flex: 1; }
.corso-lang { font-size: 1rem; font-weight: 800; color: #fff; margin-bottom: 2px; }
.corso-level { font-size: .68rem; color: rgba(255,255,255,.45); margin-bottom: 8px; }
.corso-cert { font-size: .68rem; color: var(--blue); font-weight: 600; line-height: 1.4; }
.corso-arrow {
    width: 28px; height: 28px; border-radius: 50%;
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    margin-top: 10px; font-size: .75rem; color: rgba(255,255,255,.5);
    transition: all .2s;
}
.corso-card:hover .corso-arrow { background: var(--blue); border-color: var(--blue); color: #fff; }

/* ── METODO ──────────────────────────────────────── */
.metodo-section {
    background: #fff;
    padding: 80px 0;
}
.metodo-inner {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 64px;
    align-items: start;
}
.metodo-left .sec-heading { font-size: clamp(1.6rem, 3vw, 2.2rem); }
.metodo-left .sec-subtext { margin-top: 12px; }
.metodo-steps {
    display: grid;
    grid-template-columns: repeat(5,1fr);
    gap: 0;
    padding-top: 16px;
    position: relative;
}
/* Linea tratteggiata tra i cerchi */
.metodo-steps::before {
    content: '';
    position: absolute;
    top: 36px; left: 10%; right: 10%;
    height: 2px;
    background: repeating-linear-gradient(
        90deg,
        #dde4ee 0, #dde4ee 8px,
        transparent 8px, transparent 16px
    );
}
.metodo-step {
    display: flex; flex-direction: column; align-items: center;
    text-align: center; position: relative; z-index: 1;
}
.metodo-circle {
    width: 72px; height: 72px; border-radius: 50%;
    background: var(--blue);
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    margin-bottom: 16px; flex-shrink: 0;
    box-shadow: 0 8px 24px rgba(26,86,219,.25);
}
.metodo-circle-num {
    font-size: .6rem; font-weight: 700; color: rgba(255,255,255,.7);
    letter-spacing: .08em; line-height: 1;
}
.metodo-circle-icon { font-size: 1.3rem; line-height: 1; }
.metodo-step h4 { font-size: .82rem; font-weight: 700; color: var(--text); margin-bottom: 6px; }
.metodo-step p  { font-size: .72rem; color: var(--muted); line-height: 1.55; }

/* ── STATS ───────────────────────────────────────── */
.stats-section {
    background: linear-gradient(135deg, #071428 0%, #0d2045 50%, #071428 100%);
    padding: 72px 0;
    position: relative; overflow: hidden;
}
/* Effetto stelle/sparkle */
.stats-section::before {
    content: '✦ ✦ ✦ ✦ ✦ ✦ ✦ ✦ ✦ ✦ ✦ ✦';
    position: absolute;
    top: 50%; left: 50%; transform: translate(-50%,-50%);
    font-size: 1.5rem; color: rgba(26,86,219,.12);
    white-space: nowrap; pointer-events: none;
    letter-spacing: 4rem;
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4,1fr);
    gap: 0;
    position: relative; z-index: 1;
}
.stat-box {
    text-align: center; padding: 0 24px;
    border-right: 1px solid rgba(255,255,255,.08);
}
.stat-box:last-child { border-right: none; }
.stat-box-icon {
    font-size: 2rem; margin-bottom: 12px; display: block;
    filter: drop-shadow(0 0 12px rgba(26,86,219,.4));
}
.stat-box-num {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 2.8rem; font-weight: 800;
    color: #fff; letter-spacing: -.05em; line-height: 1;
    margin-bottom: 8px;
}
.stat-box-num sup { font-size: 1.2rem; vertical-align: super; }
.stat-box-label {
    font-size: .75rem; color: rgba(255,255,255,.45);
    font-weight: 600; letter-spacing: .08em;
    text-transform: uppercase;
}

/* ── CTA FINAL ───────────────────────────────────── */
.cta-final {
    position: relative; overflow: hidden;
    padding: 100px 0; text-align: center; color: #fff;
}
.cta-final-bg {
    position: absolute; inset: 0;
    background: url('https://images.unsplash.com/photo-1531572753322-ad063cecc140?auto=format&fit=crop&w=1600&q=70')
        center / cover no-repeat;
}
.cta-final-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to bottom,
        rgba(7,20,40,.82) 0%,
        rgba(7,20,40,.90) 100%);
}
.cta-final-inner { position: relative; z-index: 2; }
.cta-final h2 {
    font-size: clamp(1.8rem, 3.5vw, 2.8rem);
    font-weight: 800; letter-spacing: -.04em;
    margin-bottom: 14px; line-height: 1.15;
}
.cta-final p {
    font-size: 1rem; color: rgba(255,255,255,.65);
    max-width: 440px; margin: 0 auto 36px; line-height: 1.7;
}

/* ── NEWS ED EVENTI ──────────────────────────────── */
.home-news-section { padding: 84px 0; background: var(--bg); }
.home-news-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-top: 40px; }
@media (max-width: 900px) { .home-news-grid { grid-template-columns: 1fr; } }
.home-news-card {
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: var(--radius-lg); overflow: hidden;
    display: flex; flex-direction: column;
    transition: transform .25s, box-shadow .25s;
}
.home-news-card:hover { transform: translateY(-5px); box-shadow: var(--shadow); }
.home-news-img { aspect-ratio: 16 / 9; background: var(--blue-l); overflow: hidden; display: block; }
.home-news-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
.home-news-body { padding: 20px 22px 24px; display: flex; flex-direction: column; gap: 8px; flex: 1; }
.home-news-badge {
    align-self: flex-start; font-size: .66rem; font-weight: 700;
    letter-spacing: .06em; text-transform: uppercase;
    padding: 3px 10px; border-radius: 999px;
    background: var(--blue-l); color: var(--blue);
}
.home-news-badge.evento { background: var(--gold-l); color: var(--gold-d); }
.home-news-card h3 { font-size: 1.05rem; font-weight: 700; line-height: 1.35; }
.home-news-card h3 a:hover { color: var(--blue); }
.home-news-date { font-size: .76rem; color: var(--muted); }
.home-news-excerpt { font-size: .87rem; color: var(--muted); flex: 1; }
.home-news-more { font-size: .84rem; font-weight: 600; color: var(--blue); }
.home-news-all { text-align: center; margin-top: 36px; }

/* ── RESPONSIVE ──────────────────────────────────── */
@media (max-width: 1050px) {
    .hero                { grid-template-columns: 1fr; min-height: auto; }
    .hero-right          { display: none; }
    .hero-left           { padding: 72px 24px; }
    .why-grid            { grid-template-columns: 1fr; gap: 36px; }
    .corsi-inner         { grid-template-columns: 1fr; }
    .corsi-left          { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: center; }
    .metodo-inner        { grid-template-columns: 1fr; }
    .metodo-steps        { grid-template-columns: 1fr 1fr; gap: 24px; }
    .metodo-steps::before { display: none; }
    .stats-grid          { grid-template-columns: 1fr 1fr; gap: 40px 0; }
    .stat-box:nth-child(2) { border-right: none; }
    .stat-box:nth-child(3) { border-top: 1px solid rgba(255,255,255,.08); }
}
@media (max-width: 640px) {
    .hero-left           { padding: 56px 20px; }
    .hero h1             { font-size: 2.2rem; }
    .features-grid       { grid-template-columns: 1fr 1fr; }
    .corsi-left          { grid-template-columns: 1fr; }
    .metodo-steps        { grid-template-columns: 1fr; }
    .stats-grid          { grid-template-columns: 1fr 1fr; }
}
</style>
@endpush

@section('content')

{{-- ══ HERO ══ --}}
<section class="hero" aria-label="Banner principale">
    @if(file_exists(public_path('images/logo-scuola.png')))
        <img src="{{ asset('images/logo-scuola.png') }}" alt="" aria-hidden="true" class="hero-logo-watermark" loading="eager">
    @endif
    <div class="hero-left">
        <div class="hero-eyebrow">
            <span>20+ ANNI DI ESPERIENZA</span> &nbsp;·&nbsp;
            DOCENTI MADRELINGUA &nbsp;·&nbsp;
            CERTIFICAZIONI INTERNAZIONALI
        </div>
        <h1>
            <span class="h1-kicker">Scuola di Lingue a Roma San Paolo dal 2002</span>
            Parla al mondo.
            <span class="accent">Cambia il tuo futuro.</span>
        </h1>
        <p class="hero-desc">Corsi di <strong>inglese</strong>, <strong>spagnolo</strong>, <strong>francese</strong>, <strong>tedesco</strong>, <strong>arabo</strong> e <strong>italiano per stranieri</strong> con docenti madrelingua. Preparazione certificazioni Trinity, Cambridge, IELTS, DELE, DELF e Goethe. Test di livello gratuito.</p>
        <div class="hero-actions">
            <a href="{{ route('iscrizione') }}" class="btn-hero-primary">PRENOTA IL TEST GRATUITO →</a>
            <a href="#corsi" class="btn-hero-ghost">SCOPRI I CORSI →</a>
        </div>
    </div>

    <div class="hero-right">
        {{-- Unico badge in evidenza: Trinity College London (cliccabile).
             Le altre card sono state rimosse perché coprivano foto e logo. --}}
        <a href="{{ route('le-certificazioni') }}" class="hero-trinity-badge" title="Scopri le certificazioni Trinity College London" aria-label="Le certificazioni Trinity College London — Sede esami n° 8241">
            <img src="{{ asset('images/cert-trinity.svg') }}" alt="Trinity College London">
            <span class="htb-text">
                <strong>Sede Esami Ufficiale n° 8241</strong>
                <span>GESE &amp; ISE — Scopri le certificazioni →</span>
            </span>
        </a>
    </div>
</section>

{{-- ══ CERT STRIP ══ --}}
<div class="cert-strip" aria-label="Enti certificatori ufficiali">
    <p class="cert-strip-label">Sedi esami ufficiali — Certificazioni internazionali riconosciute</p>
    <div class="c">
        <div class="cert-logos">
            <a href="{{ route('le-certificazioni') }}" class="cert-logo-item" title="Le certificazioni Trinity College London — Sede esami n° 8241" aria-label="Scopri le certificazioni Trinity College London">
                <img src="{{ asset('images/cert-trinity.svg') }}" alt="Trinity College London" loading="lazy">
            </a>
            <div class="cert-logo-item">
                <img src="{{ asset('images/cert-cambridge.svg') }}" alt="Cambridge Assessment English" loading="lazy">
            </div>
            <div class="cert-logo-item">
                <img src="{{ asset('images/cert-ielts.svg') }}" alt="IELTS" loading="lazy">
            </div>
            <div class="cert-logo-item">
                <img src="{{ asset('images/cert-delf.svg') }}" alt="DELF DALF – France Éducation International" loading="lazy">
            </div>
            <div class="cert-logo-item">
                <img src="{{ asset('images/cert-dele.svg') }}" alt="DELE – Instituto Cervantes" loading="lazy">
            </div>
            <div class="cert-logo-item">
                <img src="{{ asset('images/cert-plida.svg') }}" alt="PLIDA – Dante Alighieri" loading="lazy">
            </div>
        </div>
    </div>
</div>

{{-- ══ WHY / PERCHÉ SCEGLIERE A&A ══ --}}
<section class="why-section" aria-labelledby="why-title">
    <div class="c">
        <div class="why-grid">
            <div class="why-left">
                <div class="section-label">Perché scegliere A&A</div>
                <h2 class="sec-heading" id="why-title">Un metodo.<br>Un'esperienza.<br>Risultati concreti.</h2>
                <div class="why-underline"></div>
                <p><strong>A&amp;A Language Center</strong> è una <strong>scuola di lingue a Roma</strong>, nel quartiere San Paolo, con oltre 20 anni di esperienza nell'insegnamento delle lingue straniere. Metodi innovativi, docenti madrelingua qualificati e un approccio completamente personalizzato sul tuo livello CEFR e sui tuoi obiettivi — sia che tu cerchi corsi di inglese, italiano per stranieri o lingue per il lavoro.</p>
                <div class="why-cta">
                    <a href="{{ route('iscrizione') }}" class="btn-primary">Prenota il test gratuito →</a>
                </div>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🌍</div>
                    <h4>Insegnanti internazionali</h4>
                    <p>Madrelingua qualificati provenienti da tutto il mondo con esperienza didattica certificata.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🎯</div>
                    <h4>Percorsi personalizzati</h4>
                    <p>Corsi costruiti sui tuoi obiettivi e sul tuo livello CEFR, valutato con test gratuito.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🏆</div>
                    <h4>Certificazioni riconosciute</h4>
                    <p>Preparazione ufficiale per Trinity, IELTS, Cambridge e tutte le principali certificazioni.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💻</div>
                    <h4>Online e in presenza</h4>
                    <p>Scegli la modalità che preferisci. Sempre con qualità top e docenti dedicati.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">👥</div>
                    <h4>Mini gruppi</h4>
                    <p>Classi a numero ridotto per la tua attenzione vera e un apprendimento efficace.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🚀</div>
                    <h4>Career focused</h4>
                    <p>Lingue per il lavoro, l'università e la tua crescita professionale nel mercato globale.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ CORSI ══ --}}
<section class="corsi-section" id="corsi" aria-labelledby="corsi-title">
    <div class="c">
        <div class="corsi-inner">
            <div class="corsi-left">
                <div class="section-label white">I nostri corsi</div>
                <h2 class="sec-heading" id="corsi-title">Scegli la lingua.<br>Apri le porte<br>al <em>mondo</em>.</h2>
                <p class="sec-subtext">Corsi per ogni livello e ogni età. Programmi allineati al framework CEFR con certificazioni riconosciute a livello internazionale.</p>
                <a href="{{ route('checkout.catalogo') }}" class="btn-corsi-all">TUTTI I CORSI →</a>
            </div>

            @php
            $langMeta = [
                'Inglese'                => ['flag'=>'🇬🇧','cert'=>'Cambridge · IELTS · Trinity','img'=>'https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?auto=format&fit=crop&w=400&q=80'],
                'Spagnolo'               => ['flag'=>'🇪🇸','cert'=>'DELE · Instituto Cervantes','img'=>'https://images.unsplash.com/photo-1583422409516-2895a77efded?auto=format&fit=crop&w=400&q=80'],
                'Francese'               => ['flag'=>'🇫🇷','cert'=>'DELF / DALF','img'=>'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?auto=format&fit=crop&w=400&q=80'],
                'Tedesco'                => ['flag'=>'🇩🇪','cert'=>'Goethe Institut','img'=>'https://images.unsplash.com/photo-1560969184-10fe8719e047?auto=format&fit=crop&w=400&q=80'],
                'Arabo'                  => ['flag'=>'🇸🇦','cert'=>'Certificazioni','img'=>'https://images.unsplash.com/photo-1466442929976-97f336a657be?auto=format&fit=crop&w=400&q=80'],
                'Russo'                  => ['flag'=>'🇷🇺','cert'=>'Certificazioni','img'=>'https://images.unsplash.com/photo-1513326738677-b964603b136d?auto=format&fit=crop&w=400&q=80'],
                'Italiano per stranieri' => ['flag'=>'🇮🇹','cert'=>'PLIDA · CILS','img'=>'https://images.unsplash.com/photo-1523731407965-2430cd12f5e4?auto=format&fit=crop&w=400&q=80'],
                'Portoghese'             => ['flag'=>'🇵🇹','cert'=>'CELP','img'=>'https://images.unsplash.com/photo-1555881400-74d7acaacd8b?auto=format&fit=crop&w=400&q=80'],
                'Cinese'                 => ['flag'=>'🇨🇳','cert'=>'HSK','img'=>'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?auto=format&fit=crop&w=400&q=80'],
            ];
            @endphp

            <div class="corsi-scroll" role="list">
                @forelse($coursesByLanguage as $lang => $group)
                    @php $m = $langMeta[$lang] ?? ['flag'=>'🌐','cert'=>'','img'=>'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?auto=format&fit=crop&w=400&q=80']; @endphp
                    <a href="{{ route('checkout.catalogo') }}" class="corso-card" role="listitem">
                        <div class="corso-img" style="background-image:url('{{ $m['img'] }}')">
                            <span class="corso-flag">{{ $m['flag'] }}</span>
                        </div>
                        <div class="corso-body">
                            <div class="corso-lang">{{ strtoupper($lang) }}</div>
                            <div class="corso-level">Tutti i livelli</div>
                            @if($m['cert'])
                                <div class="corso-cert">Certificazioni<br>{{ $m['cert'] }}</div>
                            @endif
                            <div class="corso-arrow">→</div>
                        </div>
                    </a>
                @empty
                    @foreach(\App\Support\LanguageOptions::all() as $lang => $label)
                        @php $m = $langMeta[$lang] ?? ['flag'=>'🌐','cert'=>'','img'=>'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?auto=format&fit=crop&w=400&q=80']; @endphp
                        <a href="{{ route('checkout.catalogo') }}" class="corso-card" role="listitem">
                            <div class="corso-img" style="background-image:url('{{ $m['img'] }}')">
                                <span class="corso-flag">{{ $m['flag'] }}</span>
                            </div>
                            <div class="corso-body">
                                <div class="corso-lang">{{ strtoupper($label) }}</div>
                                <div class="corso-level">Tutti i livelli</div>
                                @if($m['cert'])
                                    <div class="corso-cert">Certificazioni<br>{{ $m['cert'] }}</div>
                                @endif
                                <div class="corso-arrow">→</div>
                            </div>
                        </a>
                    @endforeach
                @endforelse
            </div>
        </div>
    </div>
</section>

{{-- ══ METODO ══ --}}
<section class="metodo-section" id="metodo" aria-labelledby="metodo-title">
    <div class="c">
        <div class="metodo-inner">
            <div class="metodo-left">
                <div class="section-label">Il nostro metodo</div>
                <h2 class="sec-heading" id="metodo-title">Un percorso su misura,<br>passo dopo passo.</h2>
                <p class="sec-subtext">Ogni studente è unico. Iniziamo dal tuo livello reale e costruiamo insieme il percorso più efficace verso i tuoi obiettivi.</p>
                <div style="margin-top:24px;">
                    <a href="{{ route('iscrizione') }}" class="btn-primary">Inizia ora →</a>
                </div>
            </div>
            <div class="metodo-steps">
                <div class="metodo-step">
                    <div class="metodo-circle">
                        <span class="metodo-circle-num">01</span>
                        <span class="metodo-circle-icon">📋</span>
                    </div>
                    <h4>Test iniziale</h4>
                    <p>Valutiamo il tuo livello e i tuoi obiettivi.</p>
                </div>
                <div class="metodo-step">
                    <div class="metodo-circle">
                        <span class="metodo-circle-num">02</span>
                        <span class="metodo-circle-icon">👤</span>
                    </div>
                    <h4>Piano personalizzato</h4>
                    <p>Costruiamo il percorso perfetto per te.</p>
                </div>
                <div class="metodo-step">
                    <div class="metodo-circle">
                        <span class="metodo-circle-num">03</span>
                        <span class="metodo-circle-icon">💬</span>
                    </div>
                    <h4>Speaking immersion</h4>
                    <p>Parla, ascolta, vivi la lingua ogni giorno.</p>
                </div>
                <div class="metodo-step">
                    <div class="metodo-circle">
                        <span class="metodo-circle-num">04</span>
                        <span class="metodo-circle-icon">🏅</span>
                    </div>
                    <h4>Preparazione certificazioni</h4>
                    <p>Ti prepariamo e ti accompagniamo all'esame.</p>
                </div>
                <div class="metodo-step">
                    <div class="metodo-circle">
                        <span class="metodo-circle-num">05</span>
                        <span class="metodo-circle-icon">🚩</span>
                    </div>
                    <h4>Obiettivi raggiunti</h4>
                    <p>Nuove competenze, nuove opportunità, nuovo futuro.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ STATS ══ --}}
<section class="stats-section" aria-label="Numeri chiave">
    <div class="c">
        <div class="stats-grid">
            <div class="stat-box">
                <span class="stat-box-icon">🏆</span>
                <div class="stat-box-num">20<sup>+</sup></div>
                <div class="stat-box-label">Anni di esperienza</div>
            </div>
            <div class="stat-box">
                <span class="stat-box-icon">🎓</span>
                <div class="stat-box-num">250<sup>+</sup></div>
                <div class="stat-box-label">Studenti formati</div>
            </div>
            <div class="stat-box">
                <span class="stat-box-icon">👍</span>
                <div class="stat-box-num">98<sup>%</sup></div>
                <div class="stat-box-label">Studenti soddisfatti</div>
            </div>
            <div class="stat-box">
                <span class="stat-box-icon">🌐</span>
                <div class="stat-box-num">6</div>
                <div class="stat-box-label">Certificazioni internazionali</div>
            </div>
        </div>
    </div>
</section>

{{-- ══ NEWS ED EVENTI ══ --}}
@if(isset($latestNews) && $latestNews->isNotEmpty())
<section class="home-news-section" aria-labelledby="news-title">
    <div class="c">
        <div class="sec-header center">
            <div class="section-label" style="justify-content:center;">Dalla scuola</div>
            <h2 class="sec-heading" id="news-title">News ed <em>Eventi</em></h2>
        </div>
        <div class="home-news-grid">
            @foreach($latestNews as $post)
                <article class="home-news-card">
                    @if($post->cover_image)
                        <a href="{{ route('news.show', $post->slug) }}" class="home-news-img" aria-hidden="true" tabindex="-1">
                            <img src="{{ asset('storage/' . $post->cover_image) }}" alt="{{ $post->title }}" loading="lazy">
                        </a>
                    @endif
                    <div class="home-news-body">
                        <span class="home-news-badge {{ $post->type }}">{{ $post->type_label }}</span>
                        <h3><a href="{{ route('news.show', $post->slug) }}">{{ $post->title }}</a></h3>
                        <span class="home-news-date">
                            {{ optional($post->published_at)->translatedFormat('d F Y') }}
                            @if($post->type === 'evento' && $post->event_date)
                                · {{ $post->event_date->translatedFormat('d F Y') }}
                            @endif
                        </span>
                        <p class="home-news-excerpt">{{ \Illuminate\Support\Str::limit($post->excerpt ?: strip_tags($post->body), 110) }}</p>
                        <a class="home-news-more" href="{{ route('news.show', $post->slug) }}">Leggi tutto →</a>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="home-news-all">
            <a href="{{ route('news.index') }}" class="btn-hero-primary">TUTTE LE NEWS →</a>
        </div>
    </div>
</section>
@endif

{{-- ══ FAQ ══ --}}
{{-- @cache-bust-faq 2026-05-18 --}}
@php
    $faqItems = [
        ['q' => 'Che corsi di lingue offrite a Roma?', 'a' => '<p>Offriamo corsi di <strong>inglese, spagnolo, francese, tedesco, arabo, russo, portoghese, cinese</strong> e <strong>italiano per stranieri</strong>. Tutti i livelli CEFR (A1–C2), con docenti madrelingua qualificati. Vedi il <a href="' . route('checkout.catalogo') . '">catalogo completo</a>.</p>'],
        ['q' => 'Dove si trova la scuola?', 'a' => '<p>Siamo in <strong>Viale Leonardo da Vinci 193, 00145 Roma</strong>, nel quartiere San Paolo. A pochi passi dalle fermate metro San Paolo e Marconi (linea B), ben collegati con EUR, Garbatella, Ostiense e Testaccio.</p>'],
        ['q' => 'Il test di livello è davvero gratuito?', 'a' => '<p>Sì, completamente gratuito e senza impegno. Comprende una parte scritta e una orale con un docente madrelingua. Al termine ricevi una valutazione CEFR dettagliata. <a href="' . route('iscrizione') . '">Prenotalo qui</a>.</p>'],
        ['q' => 'Posso seguire i corsi online?', 'a' => '<p>Sì. Tutti i nostri corsi sono disponibili anche in modalità online (videoconferenza live) con la stessa qualità delle lezioni in presenza. Offriamo inoltre il servizio esclusivo <strong>Inglese al Telefono</strong>: 30 minuti al giorno per migliorare lo speaking.</p>'],
        ['q' => 'Siete sede d\'esame Trinity College London?', 'a' => '<p>Sì. A&amp;A Language Center è <strong>Sede d\'Esame Ufficiale Trinity College London n° 8241</strong>. Organizziamo sessioni GESE e ISE durante tutto l\'anno direttamente nella nostra sede.</p>'],
        ['q' => 'Avete corsi per aziende?', 'a' => '<p>Sì. Dal 2002 facciamo formazione linguistica B2B per aziende, enti pubblici, hotel e studi professionali. Tra i clienti: MEF, Confcommercio, H10 Hotels. Vedi <a href="' . route('landing.aziendali') . '">Corsi Aziendali</a>.</p>'],
    ];
@endphp
<x-seo-faq
    title="Domande frequenti su A&A Language Center"
    subtitle="Le risposte alle domande più comuni su corsi, prezzi, certificazioni e modalità."
    :items="$faqItems"
/>

{{-- ══ CTA FINALE ══ --}}
<section class="cta-final" aria-labelledby="cta-title">
    <div class="cta-final-bg"></div>
    <div class="cta-final-overlay"></div>
    <div class="c cta-final-inner">
        <h2 id="cta-title">Entri per imparare una lingua.<br>Esci con nuove opportunità.</h2>
        <p>Il tuo futuro parla più lingue. Inizia oggi il tuo percorso con A&A Language Center.</p>
        <a href="{{ route('iscrizione') }}" class="btn-hero-primary">PRENOTA IL TUO TEST GRATUITO →</a>
    </div>
</section>

@endsection
