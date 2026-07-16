@extends('public.layout')

@section('title', \App\Models\PageContent::text('home', 'meta_title'))
@section('description', \App\Models\PageContent::text('home', 'meta_description'))
@section('keywords', \App\Models\PageContent::text('home', 'meta_keywords'))

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
    /* Tono chiaro: overlay bianco denso a sinistra per il testo,
       foto visibile a destra, così il logo della scuola risalta. */
    background:
        linear-gradient(95deg,
            rgba(255,255,255,.80) 0%,
            rgba(255,255,255,.72) 30%,
            rgba(255,255,255,.52) 45%,
            rgba(255,255,255,.20) 60%,
            rgba(255,255,255,.03) 100%),
        url('{{ \App\Models\PageContent::image('home', 'hero_image') }}')
            center right / cover no-repeat,
        linear-gradient(135deg, #FFFFFF 0%, #F5F8FC 50%, #EEF3FF 100%);
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
/* Logo scuola in evidenza nell'hero: pieno, nitido, con un alone di luce
   morbido dietro (glow) che lo stacca dalla foto — effetto "premium". */
.hero-logo-glow {
    position: absolute;
    top: 42%;
    left: 60%;
    transform: translate(-12%, -50%);
    width: min(520px, 40vw);
    height: min(440px, 72%);
    background: radial-gradient(ellipse closest-side,
        rgba(255,255,255,.85) 0%,
        rgba(255,255,255,.45) 45%,
        rgba(255,255,255,0) 75%);
    filter: blur(6px);
    pointer-events: none;
    z-index: 1;
}
.hero-logo-watermark {
    position: absolute;
    top: 42%;
    left: 60%;
    transform: translateY(-50%);
    height: min(360px, 60%);
    width: auto;
    filter: drop-shadow(0 18px 44px rgba(13,27,46,.38));
    pointer-events: none;
    z-index: 1;
}
@media (max-width: 1050px) {
    /* Su schermi stretti il testo occupa tutta la larghezza: il watermark
       sotto al testo disturberebbe la lettura. */
    .hero-logo-watermark, .hero-logo-glow { display: none; }
}
.hero-eyebrow {
    font-size: .68rem; font-weight: 700;
    letter-spacing: .14em; text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 22px;
}
.hero-eyebrow span { color: var(--blue); }
.hero h1 {
    font-size: clamp(2.4rem, 4.5vw, 3.6rem);
    font-weight: 800; line-height: 1.08;
    letter-spacing: -.05em; color: var(--navy);
    margin-bottom: 20px;
}
.hero h1 .accent { color: var(--blue); display: block; }
.hero h1 .h1-kicker {
    display: block;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .14em;
    text-transform: uppercase;
    color: var(--gold-d);
    margin-bottom: 14px;
    line-height: 1.4;
}
.hero-desc {
    font-size: 1rem; color: var(--muted);
    line-height: 1.75; max-width: 480px; margin-bottom: 36px;
}
.hero-actions { display: flex; gap: 14px; flex-wrap: wrap; }
.hero-phone {
    margin-top: 22px;
    font-size: .95rem; color: var(--muted);
}
.hero-phone a { color: var(--navy); }
.hero-phone a strong {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800; font-size: 1.05rem;
    letter-spacing: .02em;
}
.hero-phone a:hover strong { color: var(--blue); }
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
    background: #fff;
    color: var(--text);
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 600; font-size: .88rem;
    border-radius: 8px; border: 1.5px solid var(--border);
    letter-spacing: .03em; text-transform: uppercase;
    transition: all .2s;
}
.btn-hero-ghost:hover { background: var(--blue-l); border-color: var(--blue); color: var(--blue); }

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
    display: flex; align-items: center; gap: 22px;
    background: rgba(255,255,255,.96);
    border-radius: 14px;
    border-left: 5px solid var(--gold);
    padding: 18px 26px;
    max-width: 420px;
    backdrop-filter: blur(8px);
    box-shadow: 0 14px 44px rgba(0,0,0,.35);
    transition: transform .2s, box-shadow .2s;
}
.hero-trinity-badge:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 56px rgba(0,0,0,.45);
}
/* Logo REC Trinity: min. 65px di altezza su schermo (linee guida brand Trinity College London) */
.hero-trinity-badge img { height: 68px; width: auto; flex-shrink: 0; }
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
    padding: 40px 0;
}
.cert-strip-label {
    text-align: center; font-size: .7rem; font-weight: 700;
    letter-spacing: .14em; text-transform: uppercase;
    color: #9aadcb; margin-bottom: 28px;
}
.cert-strip .c { width: min(1400px, calc(100% - 40px)); }
.cert-logos {
    display: flex; gap: 16px;
    justify-content: center; align-items: center; flex-wrap: wrap;
}
.cert-logo-item {
    display: flex; align-items: center; justify-content: center;
    opacity: .85; transition: opacity .2s, transform .2s;
    padding: 4px 8px;
}
.cert-logo-item:hover { opacity: 1; transform: translateY(-2px); }
.cert-logo-item img {
    height: 100px; width: auto; max-width: 260px;
    object-fit: contain; display: block;
}
/* Trinity REC: logo ufficiale, richiede min. 65px di altezza su schermo e più spazio di rispetto
   attorno (linee guida brand Trinity College London) — dimensioni maggiorate rispetto agli altri loghi,
   ma proporzionate: gli altri loghi sono ora vicini per non risultare eccessivamente piccoli al confronto */
.cert-logo-item.cert-logo-trinity { padding: 6px 14px; }
.cert-logo-item.cert-logo-trinity img { height: 88px; max-width: 240px; }
/* Cambridge: il file ufficiale ha un formato molto orizzontale, che a parità di
   altezza risulta visivamente più grande degli altri loghi rispetto a Trinity */
.cert-logo-item.cert-logo-cambridge img { height: 82px; max-width: 200px; }

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

/* ── CORSI PER ETÀ ───────────────────────────────── */
.eta-section {
    background: #f5f8fc;
    padding: 80px 0;
}
.eta-header { max-width: 640px; margin-bottom: 40px; }
.eta-header .sec-heading { font-size: clamp(1.6rem, 3vw, 2.2rem); }
.eta-header .sec-subtext { margin-top: 12px; }
.eta-grid {
    display: grid;
    grid-template-columns: repeat(4,1fr);
    gap: 16px;
}
.eta-card {
    display: flex; flex-direction: column;
    background: #fff;
    border: 1px solid #e8eef8;
    border-radius: 14px;
    padding: 26px 22px;
    transition: border-color .2s, transform .2s, box-shadow .2s;
}
.eta-card:hover {
    border-color: rgba(26,86,219,.3);
    transform: translateY(-4px);
    box-shadow: 0 14px 34px rgba(13,27,46,.08);
}
.eta-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: var(--blue-l);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; margin-bottom: 16px;
}
.eta-card h3 { font-size: 1rem; font-weight: 800; color: var(--text); margin-bottom: 8px; }
.eta-card p  { font-size: .82rem; color: var(--muted); line-height: 1.65; margin: 0 0 14px; flex: 1; }
.eta-more { font-size: .82rem; font-weight: 700; color: var(--blue); }
.eta-card:hover .eta-more { text-decoration: underline; }

/* ── INSEGNANTI E METODO (approfondimento) ───────── */
.insegnanti-section {
    background: #f5f8fc;
    padding: 80px 0;
}
.insegnanti-inner {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 64px;
    align-items: start;
}
.insegnanti-left .sec-heading { font-size: clamp(1.6rem, 3vw, 2.2rem); }
.insegnanti-left .sec-subtext { margin-top: 12px; }
.insegnanti-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.insegnante-card {
    background: #fff;
    border: 1px solid #e8eef8;
    border-radius: 14px;
    padding: 24px 22px;
    transition: border-color .2s, transform .2s;
}
.insegnante-card:hover {
    border-color: rgba(26,86,219,.25);
    transform: translateY(-3px);
}
.insegnante-num {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: .68rem; font-weight: 800;
    letter-spacing: .12em; color: var(--gold-d);
    margin-bottom: 10px;
}
.insegnante-card h3 { font-size: .95rem; font-weight: 800; color: var(--text); margin-bottom: 8px; }
.insegnante-card p  { font-size: .82rem; color: var(--muted); line-height: 1.7; margin: 0; }

/* ── CLAIM ───────────────────────────────────────── */
.claim-band {
    background: #fff;
    padding: 84px 0;
    text-align: center;
}
.claim-eyebrow {
    font-size: .68rem; font-weight: 700;
    letter-spacing: .14em; text-transform: uppercase;
    color: var(--gold-d);
    margin-bottom: 18px;
}
.claim-band h2 {
    font-size: clamp(1.7rem, 3.6vw, 2.9rem);
    font-weight: 800; letter-spacing: -.04em;
    line-height: 1.2; color: var(--navy);
    max-width: 820px; margin: 0 auto;
}
.claim-band h2 em { color: var(--blue); font-style: normal; }

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
    background: url('{{ \App\Models\PageContent::image('home', 'cta_image') }}')
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
.cta-final .cta-final-phone {
    margin: 26px auto 0; font-size: .95rem;
    color: rgba(255,255,255,.75);
}
.cta-final-phone a { color: #fff; }
.cta-final-phone a strong {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800; font-size: 1.1rem; letter-spacing: .02em;
}
.cta-final-phone a:hover strong { color: var(--gold); }

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
    .eta-grid            { grid-template-columns: 1fr 1fr; }
    .insegnanti-inner    { grid-template-columns: 1fr; gap: 36px; }
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
    .eta-grid            { grid-template-columns: 1fr; }
    .insegnanti-grid     { grid-template-columns: 1fr; }
    .metodo-steps        { grid-template-columns: 1fr; }
    .stats-grid          { grid-template-columns: 1fr 1fr; }
}
</style>
@endpush

@section('content')

{{-- ══ HERO ══ --}}
<section class="hero" aria-label="Banner principale">
    {{-- NB: niente file_exists() qui — in produzione public_path() punta a
         scuole_app/public che NON riceve le immagini (il deploy le copia solo
         in public_html), quindi il check fallirebbe sempre. --}}
    <div class="hero-logo-glow" aria-hidden="true"></div>
    <img src="{{ asset('images/logo-hero.png') }}" alt="" aria-hidden="true" class="hero-logo-watermark" loading="eager">
    <div class="hero-left">
        <div class="hero-eyebrow">{!! \App\Models\PageContent::html('home', 'hero_eyebrow') !!}</div>
        <h1>
            <span class="h1-kicker">{{ \App\Models\PageContent::text('home', 'hero_kicker') }}</span>
            {{ \App\Models\PageContent::text('home', 'hero_title') }}
            <span class="accent">{{ \App\Models\PageContent::text('home', 'hero_title_accent') }}</span>
        </h1>
        <p class="hero-desc">{!! \App\Models\PageContent::html('home', 'hero_desc') !!}</p>
        <div class="hero-actions">
            <a href="{{ route('iscrizione') }}" class="btn-hero-primary">{{ \App\Models\PageContent::text('home', 'hero_cta_primary') }}</a>
            <a href="#corsi" class="btn-hero-ghost">{{ \App\Models\PageContent::text('home', 'hero_cta_secondary') }}</a>
        </div>
        <p class="hero-phone">📞 {!! \App\Models\PageContent::html('home', 'hero_phone') !!}</p>
    </div>

    <div class="hero-right">
        {{-- Unico badge in evidenza: Trinity College London (cliccabile).
             Le altre card sono state rimosse perché coprivano foto e logo. --}}
        <a href="{{ route('le-certificazioni') }}" class="hero-trinity-badge" title="Scopri le certificazioni Trinity College London" aria-label="Le certificazioni Trinity College London — Registered Exam Centre 8241">
            <img src="{{ asset('images/cert-trinity.png') }}" alt="Trinity College London — Registered Exam Centre 8241">
            <span class="htb-text">
                <strong>{{ \App\Models\PageContent::text('home', 'trinity_badge_title') }}</strong>
                <span>{!! \App\Models\PageContent::html('home', 'trinity_badge_sub') !!}</span>
            </span>
        </a>
    </div>
</section>

{{-- ══ CERT STRIP ══ --}}
<div class="cert-strip" aria-label="Certificazioni internazionali">
    <p class="cert-strip-label">{{ \App\Models\PageContent::text('home', 'cert_strip_label') }}</p>
    <div class="c">
        <div class="cert-logos">
            <a href="{{ route('le-certificazioni') }}" class="cert-logo-item cert-logo-trinity" title="Le certificazioni Trinity College London — Registered Exam Centre 8241" aria-label="Scopri le certificazioni Trinity College London">
                <img src="{{ asset('images/cert-trinity.png') }}" alt="Trinity College London — Registered Exam Centre 8241" loading="lazy">
            </a>
            <div class="cert-logo-item">
                <img src="{{ asset('images/cert-toefl.png') }}" alt="TOEFL" loading="lazy">
            </div>
            <div class="cert-logo-item">
                <img src="{{ asset('images/cert-delf-v2.png') }}" alt="DELF DALF – France Éducation International" loading="lazy">
            </div>
            <div class="cert-logo-item">
                <img src="{{ asset('images/cert-ielts-v2.png') }}" alt="IELTS" loading="lazy">
            </div>
            <div class="cert-logo-item">
                <img src="{{ asset('images/cert-goethe.png') }}" alt="Goethe-Institut" loading="lazy">
            </div>
            <div class="cert-logo-item">
                <img src="{{ asset('images/cert-dele.png') }}" alt="DELE – Instituto Cervantes" loading="lazy">
            </div>
            <div class="cert-logo-item cert-logo-cambridge">
                <img src="{{ asset('images/cert-cambridge-v2.png') }}" alt="Cambridge Assessment English" loading="lazy">
            </div>
            <div class="cert-logo-item">
                <img src="{{ asset('images/cert-cils.png') }}" alt="CILS – Università per Stranieri di Siena" loading="lazy">
            </div>
        </div>
    </div>
</div>

{{-- ══ WHY / PERCHÉ SCEGLIERE A&A ══ --}}
<section class="why-section" aria-labelledby="why-title">
    <div class="c">
        <div class="why-grid">
            <div class="why-left">
                <div class="section-label">{{ \App\Models\PageContent::text('home', 'why_label') }}</div>
                <h2 class="sec-heading" id="why-title">{!! \App\Models\PageContent::html('home', 'why_title') !!}</h2>
                <div class="why-underline"></div>
                <p>{!! \App\Models\PageContent::html('home', 'why_text') !!}</p>
                <p style="margin-top:14px;">{!! \App\Models\PageContent::html('home', 'why_text2') !!}</p>
                <div class="why-cta">
                    <a href="{{ route('iscrizione') }}" class="btn-primary">{{ \App\Models\PageContent::text('home', 'why_cta') }}</a>
                </div>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🌍</div>
                    <h4>{{ \App\Models\PageContent::text('home', 'feature1_title') }}</h4>
                    <p>{{ \App\Models\PageContent::text('home', 'feature1_text') }}</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🎯</div>
                    <h4>{{ \App\Models\PageContent::text('home', 'feature2_title') }}</h4>
                    <p>{{ \App\Models\PageContent::text('home', 'feature2_text') }}</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🏆</div>
                    <h4>{{ \App\Models\PageContent::text('home', 'feature3_title') }}</h4>
                    <p>{{ \App\Models\PageContent::text('home', 'feature3_text') }}</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💻</div>
                    <h4>{{ \App\Models\PageContent::text('home', 'feature4_title') }}</h4>
                    <p>{{ \App\Models\PageContent::text('home', 'feature4_text') }}</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">👥</div>
                    <h4>{{ \App\Models\PageContent::text('home', 'feature5_title') }}</h4>
                    <p>{{ \App\Models\PageContent::text('home', 'feature5_text') }}</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🚀</div>
                    <h4>{{ \App\Models\PageContent::text('home', 'feature6_title') }}</h4>
                    <p>{{ \App\Models\PageContent::text('home', 'feature6_text') }}</p>
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
                <div class="section-label white">{{ \App\Models\PageContent::text('home', 'corsi_label') }}</div>
                <h2 class="sec-heading" id="corsi-title">{!! \App\Models\PageContent::html('home', 'corsi_title') !!}</h2>
                <p class="sec-subtext">{{ \App\Models\PageContent::text('home', 'corsi_subtext') }}</p>
                <a href="{{ route('checkout.catalogo') }}" class="btn-corsi-all">{{ \App\Models\PageContent::text('home', 'corsi_cta') }}</a>
            </div>

            @php
            $langMeta = [
                'Inglese'                => ['flag'=>'🇬🇧','cert'=>'Cambridge · IELTS · Trinity','img'=>'https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?auto=format&fit=crop&w=400&q=80'],
                'Spagnolo'               => ['flag'=>'🇪🇸','cert'=>'DELE · Instituto Cervantes','img'=>'https://images.unsplash.com/photo-1583422409516-2895a77efded?auto=format&fit=crop&w=400&q=80'],
                'Francese'               => ['flag'=>'🇫🇷','cert'=>'DELF / DALF','img'=>'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?auto=format&fit=crop&w=400&q=80'],
                'Tedesco'                => ['flag'=>'🇩🇪','cert'=>'Goethe Institut','img'=>'https://images.unsplash.com/photo-1560969184-10fe8719e047?auto=format&fit=crop&w=400&q=80'],
                'Arabo'                  => ['flag'=>'🇸🇦','cert'=>'Certificazioni','img'=>'https://images.unsplash.com/photo-1466442929976-97f336a657be?auto=format&fit=crop&w=400&q=80'],
                'Russo'                  => ['flag'=>'🇷🇺','cert'=>'Certificazioni','img'=>'https://images.unsplash.com/photo-1513326738677-b964603b136d?auto=format&fit=crop&w=400&q=80'],
                'Italiano per stranieri' => ['flag'=>'🇮🇹','cert'=>'CILS','img'=>'https://images.unsplash.com/photo-1523731407965-2430cd12f5e4?auto=format&fit=crop&w=400&q=80'],
                'Portoghese'             => ['flag'=>'🇵🇹','cert'=>'CELP','img'=>'https://images.unsplash.com/photo-1555881400-74d7acaacd8b?auto=format&fit=crop&w=400&q=80'],
                'Cinese'                 => ['flag'=>'🇨🇳','cert'=>'HSK','img'=>'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?auto=format&fit=crop&w=400&q=80'],
            ];
            @endphp

            @php
                // Lingue con corsi attivi a catalogo + tutte le lingue insegnate
                // (LanguageOptions): così ogni lingua della scuola — es. Russo e
                // Portoghese — resta visibile in home anche senza un corso
                // pubblico attivo in quel momento.
                $homeLangs = collect($coursesByLanguage->keys())
                    ->merge(array_keys(\App\Support\LanguageOptions::all()))
                    ->unique()
                    ->values();
            @endphp
            <div class="corsi-scroll" role="list">
                @foreach($homeLangs as $lang)
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
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ══ CORSI PER ETÀ ══ --}}
<section class="eta-section" aria-labelledby="eta-title">
    <div class="c">
        <div class="eta-header">
            <div class="section-label">{{ \App\Models\PageContent::text('home', 'eta_label') }}</div>
            <h2 class="sec-heading" id="eta-title">{!! \App\Models\PageContent::html('home', 'eta_title') !!}</h2>
            <p class="sec-subtext">{{ \App\Models\PageContent::text('home', 'eta_subtext') }}</p>
        </div>
        <div class="eta-grid">
            <a href="{{ route('checkout.catalogo') }}" class="eta-card">
                <div class="eta-icon">🧒</div>
                <h3>{{ \App\Models\PageContent::text('home', 'eta1_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('home', 'eta1_text') }}</p>
                <span class="eta-more">Scopri i corsi →</span>
            </a>
            <a href="{{ route('landing.ragazzi') }}" class="eta-card">
                <div class="eta-icon">🎒</div>
                <h3>{{ \App\Models\PageContent::text('home', 'eta2_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('home', 'eta2_text') }}</p>
                <span class="eta-more">Scopri i corsi →</span>
            </a>
            <a href="{{ route('landing.adulti') }}" class="eta-card">
                <div class="eta-icon">💼</div>
                <h3>{{ \App\Models\PageContent::text('home', 'eta3_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('home', 'eta3_text') }}</p>
                <span class="eta-more">Scopri i corsi →</span>
            </a>
            <a href="{{ route('per-le-aziende') }}" class="eta-card">
                <div class="eta-icon">🏢</div>
                <h3>{{ \App\Models\PageContent::text('home', 'eta4_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('home', 'eta4_text') }}</p>
                <span class="eta-more">Scopri i corsi →</span>
            </a>
        </div>
    </div>
</section>

{{-- ══ METODO ══ --}}
<section class="metodo-section" id="metodo" aria-labelledby="metodo-title">
    <div class="c">
        <div class="metodo-inner">
            <div class="metodo-left">
                <div class="section-label">{{ \App\Models\PageContent::text('home', 'metodo_label') }}</div>
                <h2 class="sec-heading" id="metodo-title">{!! \App\Models\PageContent::html('home', 'metodo_title') !!}</h2>
                <p class="sec-subtext">{{ \App\Models\PageContent::text('home', 'metodo_subtext') }}</p>
                <div style="margin-top:24px;">
                    <a href="{{ route('iscrizione') }}" class="btn-primary">{{ \App\Models\PageContent::text('home', 'metodo_cta') }}</a>
                </div>
            </div>
            <div class="metodo-steps">
                <div class="metodo-step">
                    <div class="metodo-circle">
                        <span class="metodo-circle-num">01</span>
                        <span class="metodo-circle-icon">📋</span>
                    </div>
                    <h4>{{ \App\Models\PageContent::text('home', 'step1_title') }}</h4>
                    <p>{{ \App\Models\PageContent::text('home', 'step1_text') }}</p>
                </div>
                <div class="metodo-step">
                    <div class="metodo-circle">
                        <span class="metodo-circle-num">02</span>
                        <span class="metodo-circle-icon">👤</span>
                    </div>
                    <h4>{{ \App\Models\PageContent::text('home', 'step2_title') }}</h4>
                    <p>{{ \App\Models\PageContent::text('home', 'step2_text') }}</p>
                </div>
                <div class="metodo-step">
                    <div class="metodo-circle">
                        <span class="metodo-circle-num">03</span>
                        <span class="metodo-circle-icon">💬</span>
                    </div>
                    <h4>{{ \App\Models\PageContent::text('home', 'step3_title') }}</h4>
                    <p>{{ \App\Models\PageContent::text('home', 'step3_text') }}</p>
                </div>
                <div class="metodo-step">
                    <div class="metodo-circle">
                        <span class="metodo-circle-num">04</span>
                        <span class="metodo-circle-icon">🏅</span>
                    </div>
                    <h4>{{ \App\Models\PageContent::text('home', 'step4_title') }}</h4>
                    <p>{{ \App\Models\PageContent::text('home', 'step4_text') }}</p>
                </div>
                <div class="metodo-step">
                    <div class="metodo-circle">
                        <span class="metodo-circle-num">05</span>
                        <span class="metodo-circle-icon">🚩</span>
                    </div>
                    <h4>{{ \App\Models\PageContent::text('home', 'step5_title') }}</h4>
                    <p>{{ \App\Models\PageContent::text('home', 'step5_text') }}</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ INSEGNANTI E METODO (approfondimento) ══ --}}
<section class="insegnanti-section" aria-labelledby="insegnanti-title">
    <div class="c">
        <div class="insegnanti-inner">
            <div class="insegnanti-left">
                <div class="section-label">{{ \App\Models\PageContent::text('home', 'insegnanti_label') }}</div>
                <h2 class="sec-heading" id="insegnanti-title">{!! \App\Models\PageContent::html('home', 'insegnanti_title') !!}</h2>
                <p class="sec-subtext">{{ \App\Models\PageContent::text('home', 'insegnanti_subtext') }}</p>
            </div>
            <div class="insegnanti-grid">
                <div class="insegnante-card">
                    <div class="insegnante-num">01</div>
                    <h3>{{ \App\Models\PageContent::text('home', 'insegnanti1_title') }}</h3>
                    <p>{{ \App\Models\PageContent::text('home', 'insegnanti1_text') }}</p>
                </div>
                <div class="insegnante-card">
                    <div class="insegnante-num">02</div>
                    <h3>{{ \App\Models\PageContent::text('home', 'insegnanti2_title') }}</h3>
                    <p>{{ \App\Models\PageContent::text('home', 'insegnanti2_text') }}</p>
                </div>
                <div class="insegnante-card">
                    <div class="insegnante-num">03</div>
                    <h3>{{ \App\Models\PageContent::text('home', 'insegnanti3_title') }}</h3>
                    <p>{{ \App\Models\PageContent::text('home', 'insegnanti3_text') }}</p>
                </div>
                <div class="insegnante-card">
                    <div class="insegnante-num">04</div>
                    <h3>{{ \App\Models\PageContent::text('home', 'insegnanti4_title') }}</h3>
                    <p>{{ \App\Models\PageContent::text('home', 'insegnanti4_text') }}</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ CLAIM ══ --}}
<section class="claim-band" aria-label="Il motto della scuola">
    <div class="c">
        <p class="claim-eyebrow">{{ \App\Models\PageContent::text('home', 'claim_eyebrow') }}</p>
        <h2>{!! \App\Models\PageContent::html('home', 'claim_text') !!}</h2>
    </div>
</section>

{{-- ══ STATS ══ --}}
<section class="stats-section" aria-label="Numeri chiave">
    <div class="c">
        <div class="stats-grid">
            <div class="stat-box">
                <span class="stat-box-icon">🏆</span>
                <div class="stat-box-num">{!! \App\Models\PageContent::html('home', 'stat1_num') !!}</div>
                <div class="stat-box-label">{{ \App\Models\PageContent::text('home', 'stat1_label') }}</div>
            </div>
            <div class="stat-box">
                <span class="stat-box-icon">🎓</span>
                <div class="stat-box-num">{!! \App\Models\PageContent::html('home', 'stat2_num') !!}</div>
                <div class="stat-box-label">{{ \App\Models\PageContent::text('home', 'stat2_label') }}</div>
            </div>
            <div class="stat-box">
                <span class="stat-box-icon">👍</span>
                <div class="stat-box-num">{!! \App\Models\PageContent::html('home', 'stat3_num') !!}</div>
                <div class="stat-box-label">{{ \App\Models\PageContent::text('home', 'stat3_label') }}</div>
            </div>
            <div class="stat-box">
                <span class="stat-box-icon">🌐</span>
                <div class="stat-box-num">{!! \App\Models\PageContent::html('home', 'stat4_num') !!}</div>
                <div class="stat-box-label">{{ \App\Models\PageContent::text('home', 'stat4_label') }}</div>
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
{{-- @cache-bust-faq 2026-07-08 --}}
@php
    $faqItems = \App\Models\PageContent::items('home', 'faq_items');
@endphp
<x-seo-faq
    :title="\App\Models\PageContent::text('home', 'faq_title')"
    :subtitle="\App\Models\PageContent::text('home', 'faq_subtitle')"
    :items="$faqItems"
/>

{{-- ══ CTA FINALE ══ --}}
<section class="cta-final" aria-labelledby="cta-title">
    <div class="cta-final-bg"></div>
    <div class="cta-final-overlay"></div>
    <div class="c cta-final-inner">
        <h2 id="cta-title">{!! \App\Models\PageContent::html('home', 'cta_title') !!}</h2>
        <p>{{ \App\Models\PageContent::text('home', 'cta_text') }}</p>
        <a href="{{ route('iscrizione') }}" class="btn-hero-primary">{{ \App\Models\PageContent::text('home', 'cta_button') }}</a>
        <p class="cta-final-phone">📞 {!! \App\Models\PageContent::html('home', 'cta_phone') !!}</p>
    </div>
</section>

@endsection
