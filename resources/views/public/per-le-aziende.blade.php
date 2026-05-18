@extends('public.layout')

@section('title', 'Corsi di Inglese Aziendali a Roma | Formazione Linguistica B2B')
@section('description', 'Corsi di lingue aziendali personalizzati a Roma per dipendenti, manager e team. Inglese commerciale, Business English, certificazioni CEFR. Lezioni in sede o online. Clienti: MEF, Confcommercio, H10 Hotels.')
@section('keywords', 'corsi di inglese aziendali Roma, corsi di lingue aziendali Roma, formazione linguistica aziendale Roma, business English Roma, corsi inglese per dipendenti Roma, corsi inglese aziende in sede Roma, formazione linguistica B2B Roma, corso inglese commerciale Roma')

@section('breadcrumb-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ route('home') }}" },
        { "@@type": "ListItem", "position": 2, "name": "Per le Aziende", "item": "{{ route('per-le-aziende') }}" }
    ]
}
</script>
@endsection

@push('styles')
<style>
/* ── PAGINA: PER LE AZIENDE ─────────────────────── */

/* INTRO */
.intro-grid {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 64px; align-items: center;
}
.intro-text p { font-size: .975rem; color: var(--muted); line-height: 1.8; margin-bottom: 16px; }
.intro-text p:last-of-type { margin-bottom: 0; }
.intro-photo { border-radius: var(--radius-lg); overflow: hidden; height: 380px; box-shadow: var(--shadow); }
.intro-photo img { width: 100%; height: 100%; object-fit: cover; }

/* STEPS */
.steps-grid {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;
    position: relative;
}
.steps-grid::before {
    content: ''; position: absolute;
    top: 44px; left: calc(33.33% + 12px); right: calc(33.33% + 12px);
    height: 2px; background: var(--border); z-index: 0;
}
.step-card {
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: var(--radius-lg); padding: 32px 24px; text-align: center;
    position: relative; z-index: 1;
    transition: transform .25s, box-shadow .25s, border-color .25s;
}
.step-card:hover { transform: translateY(-5px); box-shadow: var(--shadow); border-color: var(--blue); }
.step-num {
    width: 56px; height: 56px; border-radius: 50%;
    background: var(--blue); color: #fff;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.1rem; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 18px;
    box-shadow: 0 8px 24px rgba(26,86,219,.3);
}
.step-card h3 { font-size: 1rem; font-weight: 700; margin-bottom: 10px; color: var(--navy); }
.step-card p { font-size: .875rem; line-height: 1.7; color: var(--muted); margin: 0; }

/* MODALITA */
.modalita-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; }
.modalita-item {
    display: flex; gap: 16px; align-items: flex-start;
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: var(--radius-lg); padding: 22px 20px;
    transition: box-shadow .25s, border-color .25s;
}
.modalita-item:hover { box-shadow: 0 8px 24px rgba(10,22,40,.08); border-color: var(--blue); }
.modalita-icon { font-size: 1.75rem; flex-shrink: 0; margin-top: 2px; }
.modalita-item h3 { font-size: .95rem; font-weight: 700; margin-bottom: 5px; color: var(--navy); }
.modalita-item p { font-size: .85rem; line-height: 1.65; color: var(--muted); margin: 0; }

/* SERVIZI AGGIUNTIVI */
.extras-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
.extra-item {
    display: flex; gap: 12px; align-items: flex-start;
    padding: 16px 16px;
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: var(--radius);
    transition: border-color .2s;
}
.extra-item:hover { border-color: var(--blue); }
.extra-item svg { flex-shrink: 0; margin-top: 2px; color: var(--blue); }
.extra-item p { font-size: .875rem; font-weight: 600; color: var(--text); margin: 0; line-height: 1.5; }

/* CLIENTI */
.clienti-band { background: var(--navy); padding: 56px 0; }
.clienti-label {
    text-align: center; font-size: .7rem;
    text-transform: uppercase; letter-spacing: .1em;
    font-weight: 700; color: rgba(255,255,255,.4); margin-bottom: 28px;
}
.clienti-grid { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
.cliente-tag {
    background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.13);
    border-radius: 8px; padding: 9px 18px;
    font-size: .82rem; font-weight: 600; color: rgba(255,255,255,.72);
    transition: background .2s, color .2s;
}
.cliente-tag:hover { background: rgba(255,255,255,.13); color: #fff; }

/* RESPONSIVE */
@media (max-width: 900px) {
    .intro-grid    { grid-template-columns: 1fr; gap: 32px; }
    .intro-photo   { height: 260px; }
    .steps-grid    { grid-template-columns: 1fr; }
    .steps-grid::before { display: none; }
    .modalita-grid { grid-template-columns: 1fr; }
    .extras-grid   { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 640px) {
    .extras-grid { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')

{{-- PAGE HERO --}}
<section class="page-hero">
    <div class="c page-hero-inner">
        <div class="breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span class="sep">›</span>
            <span>Per le Aziende</span>
        </div>
        <h1>Corsi di Lingue <em>Aziendali</em> a Roma</h1>
        <p class="subtitle">Formazione linguistica B2B su misura per il tuo team. "We make that language your tool. Not your obstacle."</p>
    </div>
</section>

{{-- INTRO --}}
<section class="sec">
    <div class="c">
        <div class="intro-grid">
            <div class="intro-text">
                <div class="section-label">Formazione aziendale</div>
                <h2 class="sec-heading">Formiamo il vostro <em>team</em></h2>
                <p>A&amp;A Language Center opera dal 2002 con una consolidata esperienza nella <strong>formazione linguistica aziendale a Roma</strong>. Tra i nostri clienti figurano grandi aziende come <strong>MEF</strong>, <strong>Confcommercio</strong> e <strong>H10 Hotels</strong>, oltre a università, enti pubblici nazionali e locali, scuole pubbliche e private.</p>
                <p>Siamo specializzati in corsi personalizzati di <strong>Inglese, Spagnolo, Francese, Tedesco, Portoghese, Russo, Arabo</strong> e <strong>Italiano per stranieri</strong>, progettati per rispondere alle esigenze concrete del mondo professionale.</p>
                <p>I certificati rilasciati sono validi per concorsi pubblici, aggiornamento professionale e formazione del personale finanziata.</p>
            </div>
            <div class="intro-photo">
                <img src="https://images.unsplash.com/photo-1542744173-8e7e53415bb0?auto=format&fit=crop&w=900&q=85"
                     alt="Corso di inglese aziendale a Roma — formazione linguistica B2B A&A Language Center" loading="lazy" width="900" height="600">
            </div>
        </div>
    </div>
</section>

{{-- COME FUNZIONA --}}
<section class="sec sec-bg">
    <div class="c">
        <div class="sec-header">
            <div class="section-label">Il processo</div>
            <h2 class="sec-heading">Come <em>funziona</em></h2>
            <p class="sec-subtext">Un percorso strutturato in tre fasi per garantire risultati concreti e misurabili.</p>
        </div>
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-num">1</div>
                <h3>Analisi delle esigenze</h3>
                <p>Incontriamo il responsabile HR per individuare le esigenze dell'azienda, le aspettative del personale e gli obiettivi concreti da raggiungere.</p>
            </div>
            <div class="step-card">
                <div class="step-num">2</div>
                <h3>Programma su misura</h3>
                <p>Strutturiamo il programma didattico seguendo i livelli CEFR di partenza del personale e i risultati attesi. Ogni modulo è calibrato sulle reali necessità comunicative.</p>
            </div>
            <div class="step-card">
                <div class="step-num">3</div>
                <h3>Formazione e certificazione</h3>
                <p>Esame intermedio e finale per misurare i progressi. Al termine viene rilasciato un attestato con livello CEFR. Possibilità di certificazioni internazionali riconosciute.</p>
            </div>
        </div>
    </div>
</section>

{{-- MODALITA --}}
<section class="sec">
    <div class="c">
        <div class="sec-header">
            <div class="section-label">Erogazione</div>
            <h2 class="sec-heading">Modalità <em>flessibili</em></h2>
            <p class="sec-subtext">Ci adattiamo alle esigenze logistiche e organizzative della vostra azienda.</p>
        </div>
        <div class="modalita-grid">
            <div class="modalita-item">
                <div class="modalita-icon">🏫</div>
                <div>
                    <h3>In sede presso A&A</h3>
                    <p>I dipendenti seguono le lezioni nella nostra sede di Roma San Paolo, in un ambiente dedicato e attrezzato per una formazione immersiva.</p>
                </div>
            </div>
            <div class="modalita-item">
                <div class="modalita-icon">🏢</div>
                <div>
                    <h3>Presso la vostra sede</h3>
                    <p>I nostri docenti si recano direttamente nella vostra azienda, riducendo gli spostamenti del personale e ottimizzando i tempi.</p>
                </div>
            </div>
            <div class="modalita-item">
                <div class="modalita-icon">💻</div>
                <div>
                    <h3>Videoconferenza in diretta</h3>
                    <p>Corsi online tramite Zoom, Teams o la piattaforma preferita dall'azienda. Stessa qualità didattica, massima flessibilità geografica.</p>
                </div>
            </div>
            <div class="modalita-item">
                <div class="modalita-icon">👥</div>
                <div>
                    <h3>Individuali o di gruppo</h3>
                    <p>Corsi one-to-one per figure dirigenziali o piccoli gruppi omogenei per livello. Ogni formato è ottimizzato per il massimo apprendimento.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- SERVIZI AGGIUNTIVI --}}
<section class="sec sec-bg">
    <div class="c">
        <div class="sec-header">
            <div class="section-label">Plus</div>
            <h2 class="sec-heading">Servizi <em>aggiuntivi</em></h2>
        </div>
        <div class="extras-grid">
            <div class="extra-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/></svg>
                <p>Valutazione del livello linguistico del personale</p>
            </div>
            <div class="extra-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20v-1a8 8 0 0 1 16 0v1"/></svg>
                <p>Consulenza per colloqui di assunzione in lingua straniera</p>
            </div>
            <div class="extra-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><polyline points="14,2 14,8 20,8"/></svg>
                <p>Servizi di traduzione documenti aziendali</p>
            </div>
            <div class="extra-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <p>Assistenza interpreti per eventi e convegni</p>
            </div>
            <div class="extra-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><path d="M2 10h20"/></svg>
                <p>Attestato di fine corso con livello CEFR riconosciuto</p>
            </div>
            <div class="extra-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 12 2 2 4-4"/><path d="M21 12c0-4.97-4.03-9-9-9S3 7.03 3 12s4.03 9 9 9 9-4.03 9-9Z"/></svg>
                <p>Preparazione per certificazioni internazionali</p>
            </div>
        </div>
    </div>
</section>

{{-- CLIENTI --}}
<div class="clienti-band">
    <div class="c">
        <p class="clienti-label">Alcuni dei nostri clienti aziendali</p>
        <div class="clienti-grid">
            <span class="cliente-tag">wpd Italia</span>
            <span class="cliente-tag">CIOFS-FP</span>
            <span class="cliente-tag">Promo.Ter Confcommercio Roma</span>
            <span class="cliente-tag">Idea Congress</span>
            <span class="cliente-tag">MEF — Ministero dell'Economia</span>
            <span class="cliente-tag">ISCR</span>
            <span class="cliente-tag">FORMACAMERA</span>
            <span class="cliente-tag">H10 Hotels</span>
            <span class="cliente-tag">ESC 2</span>
            <span class="cliente-tag">Easy Parking</span>
            <span class="cliente-tag">ECA Italia</span>
        </div>
    </div>
</div>

{{-- CTA --}}
<section class="cta-band">
    <div class="c cta-band-inner">
        <div class="label">Formiamo il vostro team</div>
        <h2>Richiedete un preventivo gratuito</h2>
        <p>Contattateci per ricevere un preventivo personalizzato. Risponderemo entro 24 ore lavorative.</p>
        <div class="cta-actions">
            <a href="{{ route('iscrizione') }}" class="btn-gold">✦ Richiedi Preventivo Gratuito</a>
            <a href="mailto:info@aealanguagecenter.it" class="btn-outline-white">✉ Scrivi via email</a>
        </div>
    </div>
</section>

@endsection
