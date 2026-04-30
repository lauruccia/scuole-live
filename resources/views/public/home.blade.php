@extends('public.layout')

@section('title', 'A&A Language Center — Scuola di Lingue Roma')
@section('description', 'Scuola di lingue a Roma, quartiere San Paolo. Corsi di inglese, spagnolo, arabo, francese, tedesco e italiano per stranieri con insegnanti madrelingua qualificati da oltre 20 anni.')

@push('styles')
<style>
    /* ── RESET LAYOUT ── */
    *, *::before, *::after { box-sizing: border-box; }
    body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #fff; color: #06152f; }
    a { text-decoration: none; color: inherit; }

    /* ── VARIABILI ── */
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

    /* ── OVERRIDE LAYOUT NAV ── */
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
    .nav-links .btn-primary:hover { background: #0051c4 !important; transform: translateY(-2px) !important; }

    /* ── CONTAINER ── */
    .c { width: min(1120px, calc(100% - 40px)); margin: 0 auto; }

    /* ── HERO ── */
    .hero {
        position: relative;
        min-height: 500px;
        overflow: hidden;
        background:
            linear-gradient(90deg, rgba(255,255,255,1) 0%, rgba(255,255,255,.92) 36%, rgba(255,255,255,.15) 64%),
            url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1600&q=85') center right / cover;
    }
    .hero::after {
        content: "";
        position: absolute;
        inset: 0;
        background: url('https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=1400&q=80') center / cover;
        opacity: .16;
        mix-blend-mode: multiply;
        pointer-events: none;
    }
    .hero-content {
        position: relative;
        z-index: 2;
        padding: 70px 0 105px;
        max-width: 540px;
    }
    .hero h1 {
        margin: 0;
        font-size: 58px;
        line-height: 1.04;
        letter-spacing: -2px;
        color: #020b18;
        font-weight: 900;
    }
    .hero h1 span { display: block; color: #0065e8; }
    .hero p { font-size: 17px; line-height: 1.65; color: #17213a; margin: 22px 0 28px; }
    .hero-actions { display: flex; gap: 18px; flex-wrap: wrap; }

    .hbtn {
        display: inline-flex;
        align-items: center;
        min-height: 46px;
        padding: 0 30px;
        border-radius: 7px;
        font-size: 13px;
        font-weight: 900;
        border: 2px solid var(--blue);
        transition: .22s ease;
        cursor: pointer;
    }
    .hbtn-primary { background: var(--blue); color: #fff; box-shadow: 0 12px 25px rgba(0,87,217,.25); }
    .hbtn-outline  { background: #fff; color: var(--blue); }
    .hbtn:hover    { transform: translateY(-2px); }

    /* ── TRUST CARD ── */
    .trust-card {
        margin-top: -58px;
        position: relative;
        z-index: 5;
    }
    .trust-inner {
        background: #fff;
        border-radius: 18px;
        box-shadow: var(--shadow);
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        padding: 22px 10px;
    }
    .trust-item {
        text-align: center;
        padding: 14px 22px;
        border-right: 1px solid var(--border);
    }
    .trust-item:last-child { border-right: none; }
    .trust-icon { width: 42px; height: 42px; margin: 0 auto 12px; color: var(--blue); }
    .trust-item h3 { margin: 0; font-size: 17px; line-height: 1.15; }
    .trust-item p  { margin: 12px 0 0; color: #3e4b5e; font-size: 12px; line-height: 1.55; }

    /* ── SEZIONE CORSI ── */
    .sec { padding: 58px 0; }
    .sec-title { text-align: center; margin-bottom: 28px; }
    .sec-title h2 { font-size: 30px; margin: 0; letter-spacing: -.8px; }
    .sec-title::after {
        content: "";
        width: 28px; height: 3px;
        background: var(--blue);
        display: block;
        margin: 11px auto 0;
        border-radius: 10px;
    }

    .course-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 14px;
    }
    .course-card {
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 16px 12px 20px;
        min-height: 210px;
        background: #fff;
        text-align: center;
        transition: .22s ease;
    }
    .course-card:hover { transform: translateY(-5px); box-shadow: 0 18px 35px rgba(0,38,90,.12); }
    .course-image {
        position: relative;
        width: 86px; height: 86px;
        margin: 0 auto 12px;
        border-radius: 50%;
        background-size: cover;
        background-position: center;
        box-shadow: 0 5px 14px rgba(0,0,0,.18);
    }
    .flag {
        position: absolute;
        width: 38px; height: 38px;
        right: -12px; top: 2px;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 6px 12px rgba(0,0,0,.18);
        display: grid;
        place-items: center;
        font-size: 22px;
        background: #fff;
    }
    .course-card h3 { margin: 0; font-size: 15px; }
    .course-card p  { font-size: 11px; line-height: 1.6; color: #2d3a4d; min-height: 44px; }
    .course-card a  { color: var(--blue); font-size: 12px; font-weight: 900; }

    /* ── METODO ── */
    .method-band {
        display: grid;
        grid-template-columns: 1.05fr .95fr;
        min-height: 290px;
        background: linear-gradient(110deg, #001733 0%, #003d94 55%, transparent 55%);
        color: #fff;
        overflow: hidden;
    }
    .method-text { padding: 55px 0 45px; }
    .method-text-inner {
        width: min(540px, calc(100% - 40px));
        margin-left: max(20px, calc((100vw - 1120px) / 2));
    }
    .small-label { font-size: 13px; font-weight: 800; margin-bottom: 10px; }
    .method-band h2 { font-size: 31px; line-height: 1.12; margin: 0 0 16px; letter-spacing: -.7px; }
    .method-band p  { color: #e5eefb; font-size: 13px; line-height: 1.65; }
    .method-points {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-top: 28px;
    }
    .method-point { display: flex; gap: 12px; align-items: flex-start; }
    .mini-icon {
        width: 44px; height: 44px;
        flex: 0 0 44px;
        border-radius: 12px;
        background: #fff;
        color: var(--blue);
        display: grid;
        place-items: center;
        font-size: 22px;
    }
    .method-point strong { display: block; font-size: 12px; }
    .method-point span  { display: block; font-size: 11px; color: #d6e4f7; line-height: 1.4; margin-top: 3px; }
    .method-photo {
        background: url('https://images.unsplash.com/photo-1588702547923-7093a6c3ba33?auto=format&fit=crop&w=1200&q=85') center / cover;
    }

    /* ── PER CHI ── */
    .audience-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0;
        margin-top: 28px;
    }
    .audience-item {
        display: flex;
        gap: 18px;
        align-items: center;
        padding: 12px 26px;
        border-right: 1px solid var(--border);
    }
    .audience-item:last-child { border-right: none; }
    .audience-icon { width: 58px; height: 58px; flex: 0 0 58px; color: var(--blue); }
    .audience-item h3 { margin: 0 0 6px; font-size: 17px; }
    .audience-item p  { margin: 0; font-size: 12px; line-height: 1.45; color: #2d3a4d; }

    /* ── RECENSIONI ── */
    .reviews-bg { background: #f3f8ff; }
    .review-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 22px;
    }
    .review {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 22px;
        box-shadow: 0 8px 25px rgba(0,37,91,.06);
        display: flex;
        flex-direction: column;
    }
    .review-header { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
    .review-avatar {
        width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
        background: var(--blue); color: #fff;
        display: grid; place-items: center;
        font-size: 15px; font-weight: 700;
    }
    .review-meta strong { display: block; font-size: 13px; }
    .review-meta span   { font-size: 11px; color: #526173; }
    .g-stars { color: #fbbc04; font-size: 14px; letter-spacing: 1px; margin-bottom: 8px; }
    .review blockquote { margin: 0; font-size: 13px; line-height: 1.65; color: #1e2a3d; flex: 1; }
    .google-badge {
        margin-top: 14px;
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
        color: #526173;
    }
    .google-badge svg { width: 14px; height: 14px; }

    /* ── CERT STRIP ── */
    .cert-strip {
        background: var(--light);
        border-top: 1px solid var(--border);
        border-bottom: 1px solid var(--border);
        padding: 1.5rem;
        text-align: center;
    }
    .cert-strip p { font-size: .82rem; color: var(--muted); margin-bottom: .75rem; text-transform: uppercase; letter-spacing: .05em; font-weight: 700; }
    .cert-badges  { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
    .cert-badge {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 8px;
        padding: .4rem 1rem;
        font-size: .8rem;
        font-weight: 600;
        color: var(--text);
    }

    /* ── FINAL CTA ── */
    .final-cta {
        background:
            linear-gradient(90deg, rgba(0,25,66,.96), rgba(0,70,170,.92)),
            url('https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=1500&q=80') center / cover;
        color: #fff;
        padding: 28px 0;
    }
    .final-inner {
        display: grid;
        grid-template-columns: 260px 1fr 220px;
        gap: 34px;
        align-items: center;
    }
    .final-logo img { height: 82px; }
    .final-cta small { font-weight: 800; }
    .final-cta h2 { margin: 8px 0; font-size: 31px; }
    .final-cta .sub { margin: 0; color: #dce8f9; }
    .btn-yellow {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 46px;
        padding: 0 30px;
        border-radius: 7px;
        font-size: 13px;
        font-weight: 900;
        background: var(--yellow);
        color: #001126;
        border: 2px solid var(--yellow);
        box-shadow: 0 14px 30px rgba(255,216,0,.24);
        transition: .22s;
    }
    .btn-yellow:hover { transform: translateY(-2px); }

    /* ── FOOTER OVERRIDE ── */
    footer {
        background: #001126 !important;
        color: #fff !important;
        border-top: 1px solid rgba(255,255,255,.12);
        padding: 18px 0 !important;
        font-size: 13px !important;
        margin-top: 0 !important;
    }
    .footer-inner {
        display: grid;
        grid-template-columns: 1.3fr .7fr 1fr;
        gap: 20px;
        align-items: center;
    }
    .footer-item { display: flex; gap: 10px; align-items: center; color: #dbe9ff; }
    .social { display: flex; gap: 14px; justify-content: flex-end; }
    .social span {
        width: 26px; height: 26px;
        border-radius: 50%;
        border: 1px solid rgba(255,255,255,.45);
        display: grid;
        place-items: center;
        font-size: 11px;
    }

    /* ── RESPONSIVE ── */
    @media (max-width: 1050px) {
        .trust-inner { grid-template-columns: repeat(2, 1fr); }
        .trust-item  { border-bottom: 1px solid var(--border); }
        .course-grid { grid-template-columns: repeat(3, 1fr); }
        .method-band { grid-template-columns: 1fr; background: linear-gradient(135deg, #001733, #0045a7); }
        .method-photo { min-height: 260px; }
        .method-text-inner { margin: 0 auto; }
        .audience-grid { grid-template-columns: repeat(2, 1fr); }
        .review-grid { grid-template-columns: 1fr; }
        .final-inner { grid-template-columns: 1fr; text-align: center; }
        .footer-inner { grid-template-columns: 1fr; text-align: center; }
        .footer-item, .social { justify-content: center; }
    }
    @media (max-width: 640px) {
        .hero h1    { font-size: 42px; }
        .hero-content { padding: 55px 0 90px; }
        .trust-inner { grid-template-columns: 1fr; }
        .trust-item  { border-right: none; }
        .course-grid { grid-template-columns: repeat(2, 1fr); }
        .method-points { grid-template-columns: 1fr; }
        .audience-grid { grid-template-columns: 1fr; }
        .audience-item { border-right: none; border-bottom: 1px solid var(--border); }
    }
</style>
@endpush

@section('content')

{{-- ══ HERO ══ --}}
<section class="hero">
    <div class="c">
        <div class="hero-content">
            <h1>
                Parla il mondo.
                <span>Cambia il tuo futuro.</span>
            </h1>
            <p>Corsi di lingue personalizzati per ogni età ed esigenza.<br>Docenti qualificati, metodo efficace e risultati concreti.</p>
            <div class="hero-actions">
                <a href="{{ route('iscrizione') }}" class="hbtn hbtn-primary">PRENOTA IL TEST GRATUITO</a>
                <a href="#corsi" class="hbtn hbtn-outline">SCOPRI I CORSI</a>
            </div>
        </div>
    </div>
</section>

{{-- ══ TRUST CARD ══ --}}
<div class="trust-card">
    <div class="c">
        <div class="trust-inner">
            <div class="trust-item">
                <svg class="trust-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M21 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <h3>20+ Anni<br>di esperienza</h3>
                <p>Da oltre 20 anni formiamo studenti e professionisti.</p>
            </div>
            <div class="trust-item">
                <svg class="trust-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/></svg>
                <h3>Docenti<br>qualificati</h3>
                <p>Madrelingua e bilingue selezionati e certificati.</p>
            </div>
            <div class="trust-item">
                <svg class="trust-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                <h3>Metodo<br>personalizzato</h3>
                <p>Percorsi su misura in base ai tuoi obiettivi.</p>
            </div>
            <div class="trust-item">
                <svg class="trust-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 4h16v12H5.17L4 17.17V4Z"/><path d="m9 9 2 2 4-4"/></svg>
                <h3>Certificazioni<br>riconosciute</h3>
                <p>Preparazione esami e certificazioni internazionali.</p>
            </div>
            <div class="trust-item">
                <svg class="trust-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                <h3>Sede a Roma<br>San Paolo</h3>
                <p>A pochi passi dalla metro e dall'Università Roma Tre.</p>
            </div>
        </div>
    </div>
</div>

{{-- ══ CORSI ══ --}}
<section class="sec" id="corsi">
    <div class="c">
        <div class="sec-title"><h2>I nostri corsi</h2></div>
        <div class="course-grid">
            {{-- Solo le lingue presenti nel backend (LanguageOptions::all()) --}}
            <article class="course-card">
                <div class="course-image" style="background-image:url('https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?auto=format&fit=crop&w=500&q=80')">
                    <span class="flag">🇬🇧</span>
                </div>
                <h3>Inglese</h3>
                <p>Corsi per tutti i livelli, conversazione, business, preparazione esami Cambridge e IELTS.</p>
            </article>
            <article class="course-card">
                <div class="course-image" style="background-image:url('https://images.unsplash.com/photo-1583422409516-2895a77efded?auto=format&fit=crop&w=500&q=80')">
                    <span class="flag">🇪🇸</span>
                </div>
                <h3>Spagnolo</h3>
                <p>Percorsi comunicativi e culturali per studio, lavoro e viaggi con docenti madrelingua.</p>
            </article>
            <article class="course-card">
                <div class="course-image" style="background-image:url('https://images.unsplash.com/photo-1466442929976-97f336a657be?auto=format&fit=crop&w=500&q=80')">
                    <span class="flag">🇦🇪</span>
                </div>
                <h3>Arabo</h3>
                <p>Arabo moderno standard con approccio comunicativo e culturale per ogni livello.</p>
            </article>
            <article class="course-card">
                <div class="course-image" style="background-image:url('https://images.unsplash.com/photo-1502602898657-3e91760cbb34?auto=format&fit=crop&w=500&q=80')">
                    <span class="flag">🇫🇷</span>
                </div>
                <h3>Francese</h3>
                <p>Corsi dinamici per migliorare ascolto, parlato e scrittura. Preparazione DELF/DALF.</p>
            </article>
            <article class="course-card">
                <div class="course-image" style="background-image:url('https://images.unsplash.com/photo-1560969184-10fe8719e047?auto=format&fit=crop&w=500&q=80')">
                    <span class="flag">🇩🇪</span>
                </div>
                <h3>Tedesco</h3>
                <p>Metodo graduale ed efficace per parlare con sicurezza. Certificazioni Goethe Institut.</p>
            </article>
            <article class="course-card">
                <div class="course-image" style="background-image:url('https://images.unsplash.com/photo-1523731407965-2430cd12f5e4?auto=format&fit=crop&w=500&q=80')">
                    <span class="flag">🇮🇹</span>
                </div>
                <h3>Italiano per stranieri</h3>
                <p>Corsi PLIDA e di integrazione per stranieri residenti in Italia. Certificazioni riconosciute.</p>
            </article>
        </div>
    </div>
</section>

{{-- ══ CERTIFICAZIONI ══ --}}
<div class="cert-strip" id="certificazioni">
    <p>Certificazioni internazionali</p>
    <div class="cert-badges">
        <div class="cert-badge">🏛 Trinity College London</div>
        <div class="cert-badge">📜 Cambridge</div>
        <div class="cert-badge">🎓 IELTS</div>
        <div class="cert-badge">📋 DELF / DALF</div>
        <div class="cert-badge">🇩🇪 Goethe Institut</div>
        <div class="cert-badge">🇮🇹 PLIDA</div>
    </div>
</div>

{{-- ══ METODO ══ --}}
<section style="padding:0;" id="chi-siamo">
    <div class="method-band">
        <div class="method-text">
            <div class="method-text-inner">
                <div class="small-label">Il nostro metodo</div>
                <h2>Prima capiamo il tuo livello.<br>Poi costruiamo il percorso.</h2>
                <p>Ogni studente è unico. Per questo iniziamo sempre con un test di ingresso e costruiamo un percorso su misura.</p>
                <div class="method-points">
                    <div class="method-point">
                        <div class="mini-icon">✎</div>
                        <div>
                            <strong>Test di ingresso</strong>
                            <span>Valutiamo il tuo livello scritto e orale.</span>
                        </div>
                    </div>
                    <div class="method-point">
                        <div class="mini-icon">⌘</div>
                        <div>
                            <strong>Percorso su misura</strong>
                            <span>Costruiamo insieme il tuo obiettivo.</span>
                        </div>
                    </div>
                    <div class="method-point">
                        <div class="mini-icon">↗</div>
                        <div>
                            <strong>Risultati concreti</strong>
                            <span>Monitoriamo i progressi e miglioriamo insieme.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="method-photo"></div>
    </div>
</section>

{{-- ══ PER CHI ══ --}}
<section class="sec" id="per-chi" style="padding:34px 0 42px;">
    <div class="c">
        <div class="sec-title"><h2>Per chi sono i nostri corsi</h2></div>
        <div class="audience-grid">
            <div class="audience-item">
                <svg class="audience-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="7" r="4"/><path d="M5 22v-3a7 7 0 0 1 14 0v3"/><path d="M8 14l-2 4"/><path d="M16 14l2 4"/></svg>
                <div>
                    <h3>Bambini</h3>
                    <p>Imparare giocando, sviluppando curiosità e sicurezza nella lingua.</p>
                </div>
            </div>
            <div class="audience-item">
                <svg class="audience-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 22V8a6 6 0 0 1 12 0v14"/><path d="M8 22v-8h8v8"/><path d="M9 10h6"/></svg>
                <div>
                    <h3>Ragazzi</h3>
                    <p>Supporto scolastico, certificazioni e metodo di studio efficace.</p>
                </div>
            </div>
            <div class="audience-item">
                <svg class="audience-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="5"/><path d="M4 22a8 8 0 0 1 16 0"/></svg>
                <div>
                    <h3>Adulti</h3>
                    <p>Lavoro, viaggi, crescita personale e nuove opportunità.</p>
                </div>
            </div>
            <div class="audience-item">
                <svg class="audience-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M3 13h18"/></svg>
                <div>
                    <h3>Aziende</h3>
                    <p>Formazione linguistica per team e professionisti, in sede o online.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ RECENSIONI ══ --}}
<section class="sec reviews-bg">
    <div class="c">
        <div class="sec-title"><h2>Cosa dicono di noi</h2></div>
        <p style="text-align:center;font-size:13px;color:var(--muted);margin-top:-18px;margin-bottom:28px;">Recensioni verificate su Google Maps</p>
        <div class="review-grid">

            <article class="review">
                <div class="review-header">
                    <div class="review-avatar">S</div>
                    <div class="review-meta">
                        <strong>Sabrina Volpini</strong>
                        <span>5 mesi fa</span>
                    </div>
                </div>
                <div class="g-stars">★★★★★</div>
                <blockquote>"Ottima esperienza con il A&A Language Center di via Leonardo Da Vinci per l'apprendimento della lingua inglese. È un luogo dove si incontrano professionalità, competenza e passione per l'insegnamento."</blockquote>
                <div class="google-badge">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                    Recensione Google
                </div>
            </article>

            <article class="review">
                <div class="review-header">
                    <div class="review-avatar" style="background:#1a7340;">S</div>
                    <div class="review-meta">
                        <strong>Stefania Maffi</strong>
                        <span>un anno fa</span>
                    </div>
                </div>
                <div class="g-stars">★★★★★</div>
                <blockquote>"A&A Language Center si è rivelato un'ottima occasione per parlare e migliorare il mio inglese poco fluent! La teacher è sempre stata disponibile e pronta a fornire materiale, spunti di approfondimento e modi di dire easy and effective!"</blockquote>
                <div class="google-badge">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                    Recensione Google
                </div>
            </article>

            <article class="review">
                <div class="review-header">
                    <div class="review-avatar" style="background:#7c3aed;">A</div>
                    <div class="review-meta">
                        <strong>Andrea Menozzi</strong>
                        <span>3 anni fa</span>
                    </div>
                </div>
                <div class="g-stars">★★★★★</div>
                <blockquote>"La scuola A&A si distingue per l'ottima preparazione degli insegnanti e la possibilità di organizzare in modo personalizzato e flessibile le lezioni. Inoltre il personale è sempre gentile e disponibile. Assolutamente consigliata."</blockquote>
                <div class="google-badge">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                    Recensione Google
                </div>
            </article>

        </div>
    </div>
</section>

{{-- ══ FINAL CTA ══ --}}
<section class="final-cta" id="contatti">
    <div class="c final-inner">
        <div class="final-logo">
            @if(file_exists(public_path('images/logo-scuola.png')))
                <img src="{{ asset('images/logo-scuola.png') }}" alt="A&A Language Center">
            @else
                <span style="font-size:1.8rem;font-weight:900;color:#fff;">A&A</span>
            @endif
        </div>
        <div>
            <small>Il momento giusto è adesso.</small>
            <h2>Prenota il tuo test gratuito!</h2>
            <p class="sub">Scopri il percorso più adatto a te.</p>
        </div>
        <a href="{{ route('iscrizione') }}" class="btn-yellow">PRENOTA ORA →</a>
    </div>
</section>

{{-- ══ FOOTER DATI ══ --}}
<div style="background:#001126;color:#fff;border-top:1px solid rgba(255,255,255,.12);padding:18px 0;font-size:13px;">
    <div class="c footer-inner">
        <div class="footer-item">📍 Viale Leonardo da Vinci, 193 – 00145 Roma</div>
        <div class="footer-item">📞 <a href="tel:+390657437364" style="color:#dbe9ff;">06 5743734</a></div>
        <div class="footer-item">✉️ <a href="mailto:info@aealanguagecenter.it" style="color:#dbe9ff;">info@aealanguagecenter.it</a></div>
    </div>
</div>

@endsection
