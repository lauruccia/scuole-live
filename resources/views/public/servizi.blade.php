@extends('public.layout')

@section('title', 'Servizi — Corsi di Lingue Online e in Presenza a Roma')
@section('description', 'Corsi di lingue online e in presenza a Roma, inglese al telefono, test di livello gratuito, preparazione certificazioni Trinity, Cambridge, IELTS. Corsi di formazione per docenti.')
@section('keywords', 'corsi di lingue online Roma, corsi di lingue in presenza Roma, inglese al telefono Roma, test livello inglese gratuito Roma, corsi formazione docenti lingue Roma, corso inglese individuale Roma, corso inglese mini gruppo Roma, preparazione certificazioni lingue Roma')

@section('breadcrumb-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ route('home') }}" },
        { "@@type": "ListItem", "position": 2, "name": "Servizi", "item": "{{ route('servizi') }}" }
    ]
}
</script>
@endsection

@push('styles')
<style>
/* ── PAGINA: SERVIZI ─────────────────────── */

/* SERVICES GRID */
.services-grid {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px;
}
.service-card {
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: var(--radius-lg); padding: 32px 26px;
    transition: transform .25s, box-shadow .25s, border-color .25s;
    position: relative; overflow: hidden;
}
.service-card::after {
    content: ''; position: absolute;
    top: 0; left: 0; right: 0; height: 3px;
    background: var(--blue); border-radius: var(--radius-lg) var(--radius-lg) 0 0;
    transform: scaleX(0); transition: transform .3s; transform-origin: left;
}
.service-card:hover { transform: translateY(-6px); box-shadow: var(--shadow); border-color: transparent; }
.service-card:hover::after { transform: scaleX(1); }
.service-icon {
    width: 52px; height: 52px; border-radius: var(--radius);
    background: var(--blue-l);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.6rem; margin-bottom: 18px;
}
.service-card h3 { font-size: 1rem; font-weight: 700; margin-bottom: 10px; color: var(--navy); }
.service-card p { font-size: .875rem; line-height: 1.7; color: var(--muted); margin: 0 0 14px; }
.service-tag {
    display: inline-flex; align-items: center;
    font-size: .7rem; font-weight: 700; color: var(--blue);
    background: var(--blue-l); border-radius: 50px; padding: 3px 12px;
    text-transform: uppercase; letter-spacing: .04em;
}

/* TARGET GRID */
.target-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
.target-card {
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: var(--radius-lg); padding: 28px 20px; text-align: center;
    transition: transform .25s, box-shadow .25s;
}
.target-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(10,22,40,.1); }
.target-icon { font-size: 2rem; margin-bottom: 12px; }
.target-card h3 { font-size: .95rem; font-weight: 700; margin-bottom: 8px; }
.target-card p { font-size: .82rem; color: var(--muted); line-height: 1.55; margin: 0; }

/* ORARI BAND */
.orari-band { background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%); color: #fff; padding: 72px 0; }
.orari-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 64px; align-items: center; }
.orari-title { font-size: 2rem; font-weight: 800; margin-bottom: 12px; letter-spacing: -.04em; }
.orari-subtitle { font-size: .975rem; color: rgba(255,255,255,.72); line-height: 1.7; margin-bottom: 28px; }
.orari-table { width: 100%; border-collapse: collapse; }
.orari-table tr { border-bottom: 1px solid rgba(255,255,255,.1); }
.orari-table tr:last-child { border-bottom: none; }
.orari-table td { padding: 13px 0; font-size: .9rem; }
.orari-table td:first-child { color: rgba(255,255,255,.7); }
.orari-table td:last-child { font-weight: 700; text-align: right; color: var(--gold); }
.orari-note {
    margin-top: 22px; padding: 16px 20px;
    background: rgba(255,255,255,.07); border-radius: var(--radius);
    border-left: 3px solid var(--gold);
    font-size: .875rem; color: rgba(255,255,255,.8); line-height: 1.65;
}
.orari-visual { border-radius: var(--radius-lg); overflow: hidden; height: 320px; }
.orari-visual img { width: 100%; height: 100%; object-fit: cover; opacity: .85; }

/* RESPONSIVE */
@media (max-width: 900px) {
    .services-grid { grid-template-columns: 1fr 1fr; }
    .target-grid   { grid-template-columns: 1fr 1fr; }
    .orari-grid    { grid-template-columns: 1fr; gap: 32px; }
    .orari-visual  { height: 220px; }
}
@media (max-width: 640px) {
    .services-grid { grid-template-columns: 1fr; }
    .target-grid   { grid-template-columns: 1fr; }
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
            <span>Servizi</span>
        </div>
        <h1>I Nostri <em>Servizi</em> — Corsi di Lingue a Roma</h1>
        <p class="subtitle">Corsi online e in presenza, inglese al telefono, test di livello gratuito, preparazione certificazioni internazionali e formazione docenti.</p>
    </div>
</section>

{{-- SERVIZI --}}
<section class="sec">
    <div class="c">
        <div class="sec-header">
            <div class="section-label">Cosa offriamo</div>
            <h2 class="sec-heading">Sei soluzioni per <em>ogni esigenza</em></h2>
            <p class="sec-subtext">Servizi pensati per adattarsi ai tuoi ritmi, ai tuoi obiettivi e al tuo stile di vita.</p>
        </div>
        <div class="services-grid">
            <div class="service-card">
                <div class="service-icon">💻</div>
                <h3>Corsi Online</h3>
                <p>Lezioni individuali comode e flessibili tramite Skype, FaceTime o Microsoft Teams con i nostri docenti qualificati. Stessa qualità delle lezioni in presenza, senza spostarti da casa o dall'ufficio.</p>
                <span class="service-tag">Skype · FaceTime · Teams</span>
            </div>
            <div class="service-card">
                <div class="service-icon">🏫</div>
                <h3>Corsi in Presenza</h3>
                <p>Lezioni nella nostra sede di Roma San Paolo, in un ambiente confortevole e stimolante. Ideale per chi desidera un'immersione linguistica completa e il confronto diretto con i compagni.</p>
                <span class="service-tag">Roma San Paolo</span>
            </div>
            <div class="service-card">
                <div class="service-icon">📞</div>
                <h3>Inglese al Telefono</h3>
                <p>Lezioni di conversazione telefonica per chi ha pochissimo tempo. Pratico, veloce e sorprendentemente efficace: bastano 30 minuti al giorno per migliorare sensibilmente.</p>
                <span class="service-tag">Flessibile · Ovunque</span>
            </div>
            <div class="service-card">
                <div class="service-icon">📝</div>
                <h3>Test di Livello Gratuito</h3>
                <p>Prima di iniziare qualsiasi corso, offriamo un Entrance Test scritto e orale completamente gratuito per determinare il tuo livello di partenza secondo il framework CEFR (A1–C2).</p>
                <span class="service-tag">Gratuito · Senza impegno</span>
            </div>
            <div class="service-card">
                <div class="service-icon">🏆</div>
                <h3>Preparazione Certificazioni</h3>
                <p>Corsi intensivi mirati alla preparazione degli esami per le principali certificazioni internazionali: Trinity, Cambridge, IELTS, TOEFL, DELF/DALF, Goethe Institut, PLIDA, DELE e altre.</p>
                <span class="service-tag">Trinity · Cambridge · IELTS · DELF</span>
            </div>
            <div class="service-card">
                <div class="service-icon">🎓</div>
                <h3>Corsi per Docenti</h3>
                <p>A&amp;A Language Center è accreditata MIUR come ente di formazione. Offriamo corsi di lingue dedicati ai docenti, per la formazione personale e la preparazione di certificazioni linguistiche valide ai fini concorsuali.</p>
                <span class="service-tag">Formazione docenti · MIUR</span>
            </div>
        </div>
    </div>
</section>

{{-- PER CHI --}}
<section class="sec sec-bg">
    <div class="c">
        <div class="sec-header center">
            <div class="section-label" style="justify-content:center;">Target</div>
            <h2 class="sec-heading">Per tutte le età e <em>tutti i livelli</em></h2>
        </div>
        <div class="target-grid">
            <div class="target-card">
                <div class="target-icon">🧒</div>
                <h3>Bambini e Ragazzi</h3>
                <p>Approccio ludico e coinvolgente per costruire le basi della lingua sin dall'infanzia.</p>
            </div>
            <div class="target-card">
                <div class="target-icon">🎓</div>
                <h3>Studenti</h3>
                <p>Preparazione per esami scolastici, universitari e certificazioni internazionali.</p>
            </div>
            <div class="target-card">
                <div class="target-icon">💼</div>
                <h3>Professionisti</h3>
                <p>Business English, presentazioni, negoziazioni e comunicazione formale in lingua straniera.</p>
            </div>
            <div class="target-card">
                <div class="target-icon">🌍</div>
                <h3>Stranieri in Italia</h3>
                <p>Corsi di Italiano per stranieri con percorsi di integrazione e certificazioni PLIDA.</p>
            </div>
        </div>
    </div>
</section>

{{-- ORARI --}}
<section class="orari-band">
    <div class="c">
        <div class="orari-grid">
            <div>
                <div class="section-label white">Modalità e orari</div>
                <h2 class="orari-title">Flessibilità totale</h2>
                <p class="orari-subtitle">Siamo aperti quasi tutti i giorni per garantirti la massima flessibilità. Ogni lezione ha una durata minima di 55 minuti per un apprendimento efficace e completo.</p>
                <table class="orari-table">
                    <tr><td>Lunedì – Venerdì</td><td>10:00 – 19:00</td></tr>
                    <tr><td>Sabato</td><td>9:00 – 13:00</td></tr>
                    <tr><td>Domenica</td><td>Chiuso</td></tr>
                </table>
                <div class="orari-note">
                    💡 <strong>Orario No-Stop</strong> dal lunedì al venerdì: nessuna pausa pranzo. Prenota la tua lezione nell'orario più comodo, anche in pausa dal lavoro.
                </div>
            </div>
            <div class="orari-visual">
                <img src="https://images.unsplash.com/photo-1580582932707-520aed937b7b?auto=format&fit=crop&w=800&q=85"
                     alt="Ambiente di apprendimento A&A Language Center" loading="lazy">
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="cta-band">
    <div class="c cta-band-inner">
        <div class="label">Inizia oggi</div>
        <h2>Prenota il tuo test di livello gratuito</h2>
        <p>Scopri quale servizio è più adatto alle tue esigenze. Zero impegno, massima chiarezza.</p>
        <div class="cta-actions">
            <a href="{{ route('iscrizione') }}" class="btn-gold">✦ Prenota ora — è gratis</a>
            <a href="{{ route('contattaci') }}" class="btn-outline-white">Contattaci →</a>
        </div>
    </div>
</section>

@endsection
