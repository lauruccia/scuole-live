@extends('public.layout')

@section('title', 'La Scuola di Lingue a Roma San Paolo | A&A Language Center')
@section('description', 'A&A Language Center, scuola di lingue a Roma San Paolo dal 2002. Sede ufficiale esami Trinity College London n° 8241. Docenti qualificati madrelingua e/o bilingue, corsi personalizzati per tutte le età e tutti i livelli CEFR.')
@section('keywords', 'scuola di lingue Roma San Paolo, scuola di inglese Roma San Paolo, centro esami Trinity Roma, sede Trinity College Roma 8241, scuola di lingue EUR, scuola di lingue Marconi, scuola di lingue Garbatella, A&A Language Center')

@section('breadcrumb-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ route('home') }}" },
        { "@@type": "ListItem", "position": 2, "name": "La Scuola", "item": "{{ route('la-scuola') }}" }
    ]
}
</script>
@endsection

@push('styles')
<style>
/* ── PAGINA: LA SCUOLA ─────────────────────── */

/* INTRO */
.intro-grid {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 64px; align-items: center;
}
.intro-text p { font-size: .975rem; color: var(--muted); line-height: 1.8; margin-bottom: 16px; }
.intro-text p:last-of-type { margin-bottom: 0; }
.orari-badge {
    display: inline-flex; align-items: center; gap: 10px;
    background: var(--bg); border: 1.5px solid var(--border);
    border-radius: 50px; padding: 10px 22px;
    font-size: .85rem; font-weight: 600; color: var(--text);
    margin-top: 22px; box-shadow: 0 4px 14px rgba(10,22,40,.06);
}
.orari-badge em { font-style: normal; color: var(--blue); font-weight: 700; }
.intro-photo {
    border-radius: var(--radius-lg); overflow: hidden; height: 380px;
    box-shadow: var(--shadow);
}
.intro-photo img { width: 100%; height: 100%; object-fit: cover; }

/* STATS BAND */
.stats-band { background: var(--blue); }
.stats-inner {
    display: grid; grid-template-columns: repeat(4, 1fr);
}
.stat-item {
    padding: 32px 24px; text-align: center;
    border-right: 1px solid rgba(255,255,255,.2); color: #fff;
}
.stat-item:last-child { border-right: none; }
.stat-num {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 2.5rem; font-weight: 800; line-height: 1; letter-spacing: -.04em;
}
.stat-label { font-size: .72rem; color: rgba(255,255,255,.72); font-weight: 600; text-transform: uppercase; letter-spacing: .06em; margin-top: 6px; }

/* INSEGNANTI */
.features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; }
.feature-card {
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: var(--radius-lg); padding: 30px 26px;
    transition: transform .25s, box-shadow .25s, border-color .25s;
}
.feature-card:hover {
    transform: translateY(-5px); box-shadow: var(--shadow); border-color: var(--blue);
}
.feature-icon { font-size: 2rem; margin-bottom: 14px; }
.feature-card h3 { font-size: 1rem; font-weight: 700; margin-bottom: 10px; color: var(--navy); }
.feature-card p { font-size: .875rem; line-height: 1.7; color: var(--muted); margin: 0; }

/* CERT SECTION */
.cert-grid {
    display: flex; flex-wrap: wrap; gap: 10px; margin-top: 28px;
}
.cert-tag {
    background: rgba(255,255,255,.1); border: 1.5px solid rgba(255,255,255,.25);
    border-radius: 50px; padding: 7px 18px;
    font-size: .82rem; font-weight: 600; color: #fff;
    transition: background .2s;
}
.cert-tag:hover { background: rgba(255,255,255,.2); }
.cert-tag.gold { border-color: var(--gold); color: var(--gold); background: rgba(201,164,44,.08); }

/* PILLAR CARDS */
.pillars-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 22px; }
.pillar-card {
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: var(--radius-lg); padding: 32px 28px; position: relative; overflow: hidden;
    transition: box-shadow .25s;
}
.pillar-card:hover { box-shadow: var(--shadow); }
.pillar-num {
    position: absolute; top: 14px; right: 20px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 3.5rem; font-weight: 800; color: var(--bg);
    line-height: 1; pointer-events: none; letter-spacing: -.04em;
}
.pillar-card h3 { font-size: 1.05rem; font-weight: 700; color: var(--blue); margin-bottom: 10px; position: relative; }
.pillar-card p { font-size: .875rem; line-height: 1.75; color: var(--muted); margin: 0; position: relative; }

/* RESPONSIVE */
@media (max-width: 900px) {
    .intro-grid { grid-template-columns: 1fr; gap: 32px; }
    .intro-photo { height: 260px; }
    .stats-inner { grid-template-columns: repeat(2, 1fr); }
    .stat-item:nth-child(2) { border-right: none; }
    .stat-item:nth-child(3) { border-top: 1px solid rgba(255,255,255,.2); }
    .features-grid { grid-template-columns: 1fr 1fr; }
    .pillars-grid { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .features-grid { grid-template-columns: 1fr; }
    .stats-inner { grid-template-columns: repeat(2, 1fr); }
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
            <span>La Scuola</span>
        </div>
        <h1>La <em>Scuola di Lingue</em> a Roma San Paolo</h1>
        <p class="subtitle">Open Your Mind To The World — A&amp;A Language Center, dal 2002 a Roma. Sede ufficiale esami Trinity College London n° 8241.</p>
    </div>
</section>

{{-- INTRO --}}
<section class="sec">
    <div class="c">
        <div class="intro-grid">
            <div class="intro-text">
                <div class="section-label">Chi siamo</div>
                <h2 class="sec-heading">Una scuola con <em>una storia</em></h2>
                <p>Hai mai pensato di imparare una lingua straniera ma non hai mai trovato il corso giusto? A&amp;A Language Center è la risposta. Dal 2002 offriamo corsi di <strong>Inglese, Spagnolo, Francese, Tedesco, Portoghese, Russo, Arabo</strong> e <strong>Italiano per Stranieri</strong>, costruiti su misura per ogni studente.</p>
                <p>La nostra <strong>scuola di lingue a Roma</strong> si trova nel vivace quartiere <strong>San Paolo</strong>, polo universitario di Roma Tre, a pochi passi dalle fermate metro <strong>San Paolo</strong> e <strong>Marconi</strong>, ben collegata anche con i quartieri <strong>EUR</strong>, <strong>Garbatella</strong> e <strong>Ostiense</strong>. Un ambiente accogliente, moderno e stimolante dove imparare diventa un piacere.</p>
                <div class="orari-badge">
                    🕐 <em>Lun–Ven</em> 10:00–19:00 &nbsp;·&nbsp; <em>Sab</em> 9:00–13:00
                </div>
            </div>
            <div class="intro-photo">
                <img src="https://images.unsplash.com/photo-1524178232363-1fb2b075b655?auto=format&fit=crop&w=900&q=85"
                     alt="Aula di una scuola di lingue a Roma San Paolo — A&A Language Center" loading="lazy" width="900" height="600">
            </div>
        </div>
    </div>
</section>

{{-- STATS --}}
<div class="stats-band">
    <div class="c">
        <div class="stats-inner">
            <div class="stat-item"><div class="stat-num">15</div><div class="stat-label">Docenti qualificati</div></div>
            <div class="stat-item"><div class="stat-num">250+</div><div class="stat-label">Studenti all'anno</div></div>
            <div class="stat-item"><div class="stat-num">49</div><div class="stat-label">Corsi disponibili</div></div>
            <div class="stat-item"><div class="stat-num">20+</div><div class="stat-label">Anni di esperienza</div></div>
        </div>
    </div>
</div>

{{-- INSEGNANTI --}}
<section class="sec sec-bg">
    <div class="c">
        <div class="sec-header">
            <div class="section-label">Il team</div>
            <h2 class="sec-heading">Gli <em>Insegnanti</em></h2>
            <p class="sec-subtext">Staff internazionale di madrelingua e bilingue, selezionati con rigore e costantemente aggiornati. Tutti certificati e con esperienza pluriennale nell'insegnamento.</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🎯</div>
                <h3>Lezioni personalizzate</h3>
                <p>Ogni percorso è costruito sulle esigenze reali dello studente: obiettivi, tempi, livello di partenza e stile di apprendimento. Nessun corso uguale all'altro.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🌍</div>
                <h3>Docenti qualificati madrelingua e/o bilingue</h3>
                <p>Tutti i nostri insegnanti provengono da paesi di lingua madre o sono bilingue certificati, con aggiornamenti annuali tenuti dal Trinity College London.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3>Metodo A&A</h3>
                <p>Veloce, flessibile e funzionale. Puoi scegliere dove frequentare: a scuola, a casa, in ufficio o anche al telefono. L'importante è che tu raggiunga i tuoi obiettivi.</p>
            </div>
        </div>
    </div>
</section>

{{-- CERTIFICAZIONI --}}
<section class="sec sec-dark">
    <div class="c">
        <div class="sec-header">
            <div class="section-label white">Certificazioni</div>
            <h2 class="sec-heading white">Sede ufficiale <span style="color:var(--gold)">Trinity College London</span></h2>
            <p class="sec-subtext white">Siamo <strong style="color:#fff;">Sede d'esame n° 8241</strong>. Organizziamo sessioni GESE e ISE durante tutto l'anno e prepariamo i nostri studenti per le principali certificazioni internazionali. <a href="{{ route('le-certificazioni') }}" style="color:var(--gold);font-weight:700;text-decoration:underline;">Scopri le certificazioni →</a></p>
        </div>
        <div class="cert-grid">
            <a href="{{ route('le-certificazioni') }}" class="cert-tag gold" title="Scopri le certificazioni Trinity College London">★ Trinity College London — Sede n° 8241</a>
            <span class="cert-tag">IELTS</span>
            <span class="cert-tag">TOEFL</span>
            <span class="cert-tag">Cambridge</span>
            <span class="cert-tag">Alliance Française (DELF/DALF)</span>
            <span class="cert-tag">Instituto Cervantes (DELE)</span>
            <span class="cert-tag">CILS / CELI</span>
            <span class="cert-tag">Goethe Institut</span>
            <span class="cert-tag">TRKI–TORFL</span>
            <span class="cert-tag">CAPLE</span>
            <span class="cert-tag">PLIDA</span>
        </div>
    </div>
</section>

{{-- I 4 PILLAR --}}
<section class="sec">
    <div class="c">
        <div class="sec-header">
            <div class="section-label">Perché sceglierci</div>
            <h2 class="sec-heading">I 4 pilastri di <em>A&A</em></h2>
        </div>
        <div class="pillars-grid">
            <div class="pillar-card">
                <div class="pillar-num">01</div>
                <h3>Esperienza</h3>
                <p>A&amp;A Language Center opera dal 2002. In vent'anni abbiamo formato migliaia di studenti di ogni età e livello, costruendo un'esperienza didattica solida, collaudata e in continua evoluzione.</p>
            </div>
            <div class="pillar-card">
                <div class="pillar-num">02</div>
                <h3>Eccellenza</h3>
                <p>Sede d'esami GESE e ISE n° 8241 del Trinity College London. Prepariamo per IELTS, TOEFL, Cambridge, DELE, CILS, DELF/DALF, Zertifikat Deutsch, TRKI–TORFL e molte altre certificazioni.</p>
            </div>
            <div class="pillar-card">
                <div class="pillar-num">03</div>
                <h3>Docenti di Qualità</h3>
                <p>I nostri docenti provengono da ogni parte del mondo, portando un prezioso arricchimento culturale. Tutti certificati e con esperienza pluriennale, garantiscono un apprendimento autentico ed efficace.</p>
            </div>
            <div class="pillar-card">
                <div class="pillar-num">04</div>
                <h3>Corsi Personalizzati</h3>
                <p>Ogni studente riceve un programma didattico costruito su misura, con il monte ore più adatto ai propri obiettivi, tempi e stile di apprendimento. Nessun corso uguale all'altro.</p>
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="cta-band">
    <div class="c cta-band-inner">
        <div class="label">Inizia ora</div>
        <h2>Pronto a iniziare il tuo percorso linguistico?</h2>
        <p>Prenota il tuo test di livello gratuito. Nessun impegno, solo il primo passo verso una nuova lingua.</p>
        <div class="cta-actions">
            <a href="{{ route('iscrizione') }}" class="btn-gold">✦ Prenota il Test Gratuito</a>
            <a href="{{ route('contattaci') }}" class="btn-outline-white">Contattaci →</a>
        </div>
    </div>
</section>

@endsection
