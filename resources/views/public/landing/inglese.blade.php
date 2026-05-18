@extends('public.layout')

@section('title', 'Corsi di Inglese a Roma — Trinity, Cambridge, IELTS | A&A')
@section('description', 'Corsi di inglese a Roma San Paolo con docenti madrelingua. Preparazione esami Trinity, Cambridge, IELTS, TOEFL. Lezioni individuali, mini gruppi, online. Test di livello gratuito.')
@section('keywords', 'corsi di inglese Roma, scuola di inglese Roma, corsi inglese Roma San Paolo, preparazione Trinity Roma, esame Trinity Roma, preparazione IELTS Roma, preparazione Cambridge Roma, corsi inglese certificati Roma, corsi inglese adulti Roma, corsi inglese bambini Roma, corso inglese individuale Roma, corso inglese intensivo Roma, corso inglese serale Roma, centro esami Trinity Roma')
@section('og-image-alt', 'Corsi di inglese a Roma — A&A Language Center')

@section('breadcrumb-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ route('home') }}" },
        { "@@type": "ListItem", "position": 2, "name": "Corsi di Inglese a Roma", "item": "{{ route('landing.inglese') }}" }
    ]
}
</script>
@endsection

@section('extra-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Course",
    "name": "Corsi di Inglese a Roma",
    "description": "Corsi di inglese a Roma con docenti madrelingua qualificati. Tutti i livelli CEFR (A1–C2). Preparazione esami Trinity, Cambridge, IELTS, TOEFL. Lezioni individuali, mini gruppi, online o in presenza.",
    "provider": {
        "@@type": "EducationalOrganization",
        "name": "A&A Language Center",
        "sameAs": "{{ config('app.url') }}",
        "address": {
            "@@type": "PostalAddress",
            "streetAddress": "Viale Leonardo da Vinci, 193",
            "addressLocality": "Roma",
            "postalCode": "00145",
            "addressCountry": "IT"
        }
    },
    "inLanguage": "en",
    "educationalLevel": "A1–C2 (CEFR)",
    "availableLanguage": "it",
    "courseMode": ["onsite","online"],
    "offers": {
        "@@type": "Offer",
        "category": "language course",
        "priceCurrency": "EUR",
        "availability": "https://schema.org/InStock",
        "url": "{{ route('checkout.catalogo') }}"
    }
}
</script>
@endsection

@push('styles')
<style>
/* Sezione livelli */
.levels-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
.level-card {
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: var(--radius-lg); padding: 26px 22px;
    transition: transform .25s, box-shadow .25s, border-color .25s;
}
.level-card:hover {
    transform: translateY(-4px);
    border-color: var(--blue);
    box-shadow: var(--shadow);
}
.level-tag {
    display: inline-block;
    background: var(--blue-l); color: var(--blue);
    font-size: .72rem; font-weight: 800;
    padding: 4px 12px; border-radius: 50px;
    margin-bottom: 12px; letter-spacing: .04em;
}
.level-card h3 { font-size: 1rem; font-weight: 700; color: var(--navy); margin-bottom: 8px; }
.level-card p { font-size: .875rem; color: var(--muted); line-height: 1.6; margin: 0; }

/* Certificazioni */
.cert-cards { display: grid; grid-template-columns: repeat(2, 1fr); gap: 22px; }
.cert-card {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
    color: #fff; border-radius: var(--radius-lg); padding: 32px 28px;
    position: relative; overflow: hidden;
}
.cert-card.gold { background: linear-gradient(135deg, #2a1f00 0%, #4a3700 100%); border: 1px solid var(--gold); }
.cert-card-label {
    font-size: .68rem; font-weight: 800; letter-spacing: .12em;
    text-transform: uppercase; color: var(--gold); margin-bottom: 10px;
}
.cert-card h3 { font-size: 1.15rem; font-weight: 800; margin-bottom: 10px; }
.cert-card p { font-size: .9rem; color: rgba(255,255,255,.72); line-height: 1.7; margin: 0; }

/* Modalità */
.modalita-list { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
.modalita-c {
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: var(--radius-lg); padding: 22px 18px; text-align: center;
    transition: border-color .25s, transform .25s;
}
.modalita-c:hover { border-color: var(--blue); transform: translateY(-3px); }
.modalita-c-icon { font-size: 1.8rem; margin-bottom: 12px; }
.modalita-c h4 { font-size: .92rem; font-weight: 700; margin-bottom: 6px; color: var(--navy); }
.modalita-c p { font-size: .8rem; color: var(--muted); line-height: 1.55; margin: 0; }

@media (max-width: 900px) {
    .levels-grid, .cert-cards { grid-template-columns: 1fr; }
    .modalita-list { grid-template-columns: 1fr 1fr; }
}
</style>
@endpush

@section('content')

{{-- HERO --}}
<section class="page-hero">
    <div class="c page-hero-inner">
        <div class="breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span class="sep">›</span>
            <span>Corsi di Inglese a Roma</span>
        </div>
        <h1>Corsi di <em>Inglese</em> a Roma</h1>
        <p class="subtitle">Trinity, Cambridge, IELTS, TOEFL. Lezioni con docenti madrelingua. Tutti i livelli CEFR — dall'A1 al C2. Sede ufficiale esami Trinity College London n° 8241.</p>
    </div>
</section>

{{-- INTRO --}}
<section class="sec">
    <div class="c">
        <div class="sec-header center">
            <div class="section-label" style="justify-content:center;">Scuola di inglese a Roma</div>
            <h2 class="sec-heading">La via più rapida per <em>imparare l'inglese</em></h2>
            <p class="sec-subtext" style="text-align:center;margin:0 auto;">A&amp;A Language Center è una <strong>scuola di inglese a Roma San Paolo</strong> attiva dal 2002. Insegniamo l'inglese a oltre 250 studenti l'anno: bambini, ragazzi, adulti, professionisti e aziende. Con noi puoi prepararti agli esami Trinity, Cambridge, IELTS, TOEFL e ottenere certificazioni riconosciute in tutto il mondo.</p>
        </div>
    </div>
</section>

{{-- LIVELLI --}}
<section class="sec sec-bg">
    <div class="c">
        <div class="sec-header center">
            <div class="section-label" style="justify-content:center;">I livelli</div>
            <h2 class="sec-heading">Dall'<em>A1</em> al <em>C2</em></h2>
            <p class="sec-subtext" style="text-align:center;margin:0 auto;">Tutti i nostri corsi di inglese seguono il framework europeo CEFR. Inizi con il test di livello gratuito e prosegui con il percorso costruito sul tuo profilo.</p>
        </div>
        <div class="levels-grid">
            <div class="level-card">
                <span class="level-tag">A1 — Beginner</span>
                <h3>Inglese base</h3>
                <p>Per chi parte da zero o ha basi minime. Apprendi vocaboli essenziali, frasi semplici e l'uso del present simple.</p>
            </div>
            <div class="level-card">
                <span class="level-tag">A2 — Elementary</span>
                <h3>Inglese elementare</h3>
                <p>Ti esprimi su argomenti quotidiani, leggi testi brevi, comprendi conversazioni semplici. Ideale per viaggi.</p>
            </div>
            <div class="level-card">
                <span class="level-tag">B1 — Intermediate</span>
                <h3>Inglese intermedio</h3>
                <p>Sostieni conversazioni autonome, scrivi email, viaggi senza problemi. Livello richiesto in molti contesti universitari.</p>
            </div>
            <div class="level-card">
                <span class="level-tag">B2 — Upper Intermediate</span>
                <h3>Inglese avanzato</h3>
                <p>Lavori in inglese, partecipi a riunioni, leggi articoli complessi. Richiesto per IELTS 5.5–6.5 e First Cambridge.</p>
            </div>
            <div class="level-card">
                <span class="level-tag">C1 — Advanced</span>
                <h3>Inglese fluente</h3>
                <p>Padronanza professionale. Studi all'estero, fai presentazioni, negozi in inglese. Target per CAE Cambridge.</p>
            </div>
            <div class="level-card">
                <span class="level-tag">C2 — Proficiency</span>
                <h3>Inglese madrelingua</h3>
                <p>Livello quasi nativo. Comprendi qualunque sfumatura, ti esprimi con precisione assoluta. Target per CPE Cambridge.</p>
            </div>
        </div>
    </div>
</section>

{{-- CERTIFICAZIONI --}}
<section class="sec">
    <div class="c">
        <div class="sec-header">
            <div class="section-label">Certificazioni</div>
            <h2 class="sec-heading">Preparazione esami <em>ufficiali</em></h2>
            <p class="sec-subtext">A&amp;A Language Center è <strong>Sede Ufficiale Esami Trinity College London n° 8241</strong> e prepara per tutte le principali certificazioni internazionali di inglese.</p>
        </div>
        <div class="cert-cards">
            <div class="cert-card gold">
                <div class="cert-card-label">★ Trinity College London</div>
                <h3>GESE & ISE — Sede Esami n° 8241</h3>
                <p>Trinity College London è un ente certificatore britannico riconosciuto dal MIUR. Sosteniamo gli esami direttamente nella nostra sede di Roma San Paolo, con sessioni in tutto l'anno. Validi per crediti scolastici, universitari e per concorsi pubblici.</p>
            </div>
            <div class="cert-card">
                <div class="cert-card-label">Cambridge English</div>
                <h3>KET, PET, FCE, CAE, CPE</h3>
                <p>Preparazione completa agli esami Cambridge Assessment English. Corsi mirati con docenti formati sulla metodologia ufficiale Cambridge, simulazioni d'esame e libri ufficiali.</p>
            </div>
            <div class="cert-card">
                <div class="cert-card-label">IELTS Academic & General</div>
                <h3>Preparazione IELTS a Roma</h3>
                <p>Il certificato IELTS è richiesto per università straniere, immigrazione e lavoro internazionale. I nostri corsi intensivi ti preparano in tempi brevi al punteggio target (5.5–8.0).</p>
            </div>
            <div class="cert-card">
                <div class="cert-card-label">TOEFL iBT</div>
                <h3>Preparazione TOEFL</h3>
                <p>Per chi vuole studiare in università americane. Lavoriamo sulle 4 skill (Reading, Listening, Speaking, Writing) con metodo strutturato e test simulati settimanali.</p>
            </div>
        </div>
    </div>
</section>

{{-- MODALITA --}}
<section class="sec sec-bg">
    <div class="c">
        <div class="sec-header center">
            <div class="section-label" style="justify-content:center;">Modalità</div>
            <h2 class="sec-heading">Scegli <em>come</em> imparare</h2>
        </div>
        <div class="modalita-list">
            <div class="modalita-c">
                <div class="modalita-c-icon">👤</div>
                <h4>Individuale</h4>
                <p>Lezioni 1-a-1 con docente madrelingua. Massima personalizzazione e velocità.</p>
            </div>
            <div class="modalita-c">
                <div class="modalita-c-icon">👥</div>
                <h4>Mini Gruppo</h4>
                <p>Gruppi 3–6 studenti dello stesso livello. Più conversazione, meno costo.</p>
            </div>
            <div class="modalita-c">
                <div class="modalita-c-icon">💻</div>
                <h4>Online</h4>
                <p>Lezioni in videoconferenza con la stessa qualità di una lezione in presenza.</p>
            </div>
            <div class="modalita-c">
                <div class="modalita-c-icon">📞</div>
                <h4>Al telefono</h4>
                <p>30 minuti al giorno per migliorare lo speaking. Ideale per professionisti.</p>
            </div>
        </div>
    </div>
</section>

{{-- FAQ --}}
<x-seo-faq
    title="Domande frequenti sui corsi di inglese a Roma"
    subtitle="Le risposte ai dubbi più comuni di chi sta valutando di iscriversi a un corso di inglese a Roma."
    :items="[
        ['q' => 'Quanto costa un corso di inglese a Roma in A&A Language Center?', 'a' => '<p>I prezzi variano in base alla modalità (individuale, mini gruppo, online), al numero di ore e al livello target. Una lezione individuale parte da circa €30/ora, un mini gruppo da €15/ora. Puoi consultare il <a href="' . route('checkout.catalogo') . '">catalogo corsi</a> per i pacchetti completi con prezzo trasparente.</p>'],
        ['q' => 'Quanto dura un corso di inglese per ottenere una certificazione?', 'a' => '<p>Dipende dal tuo livello di partenza e dal target. Mediamente per passare da un livello CEFR al successivo servono 80–120 ore di studio. Per un Cambridge B2 First partendo da B1 si calcolano 4–6 mesi di corso a frequenza bisettimanale. Il test di livello gratuito è il punto di partenza per costruire il tuo piano.</p>'],
        ['q' => 'Posso usare la Carta del Docente per un corso di inglese?', 'a' => '<p>Sì. A&amp;A Language Center è ente accreditato MIUR. I docenti di ruolo possono usare la Carta del Docente per pagare integralmente i corsi di inglese, sia per la propria formazione personale sia per la preparazione di certificazioni linguistiche valide ai fini concorsuali.</p>'],
        ['q' => 'Sostenete gli esami Trinity direttamente nella vostra sede?', 'a' => '<p>Sì. Siamo <strong>Sede d\'Esame ufficiale Trinity College London n° 8241</strong>. Organizziamo sessioni GESE (Graded Examinations in Spoken English) e ISE (Integrated Skills in English) durante tutto l\'anno. Gli esami si sostengono direttamente nella nostra sede di Viale Leonardo da Vinci 193 a Roma.</p>'],
        ['q' => 'Dove si trova la scuola e con quali mezzi pubblici si raggiunge?', 'a' => '<p>Siamo in <strong>Viale Leonardo da Vinci 193, 00145 Roma</strong>, nel quartiere San Paolo. Siamo a pochi passi dalle fermate metro <strong>San Paolo</strong> (linea B) e <strong>Marconi</strong>, ben collegati anche con i quartieri EUR, Garbatella e Ostiense.</p>'],
        ['q' => 'Offrite corsi di inglese intensivi?', 'a' => '<p>Sì. Oltre ai corsi standard a frequenza bisettimanale, organizziamo corsi intensivi (anche tutti i giorni) e ultra-intensivi per chi ha esigenze rapide: preparazione last-minute IELTS, colloqui di lavoro, trasferimenti all\'estero.</p>'],
        ['q' => 'Il test di livello è davvero gratuito?', 'a' => '<p>Sì, completamente gratuito e senza impegno. Il nostro Entrance Test si compone di una parte scritta (grammatica, lettura, comprensione) e una parte orale (5–10 minuti con un docente madrelingua). Al termine ricevi una valutazione CEFR dettagliata e una proposta di corso. Puoi <a href="' . route('iscrizione') . '">prenotarlo qui</a>.</p>'],
        ['q' => 'Fate corsi di inglese per bambini e ragazzi?', 'a' => '<p>Sì. Abbiamo corsi dedicati a bambini (5–10 anni) e ragazzi (11–17 anni), con metodologia ludica per i più piccoli e preparazione esami Trinity/Cambridge YLE per i ragazzi. I corsi sono in piccoli gruppi omogenei per età e livello.</p>'],
    ]"
/>

{{-- CTA --}}
<section class="cta-band">
    <div class="c cta-band-inner">
        <div class="label">Prossimo step</div>
        <h2>Inizia il tuo corso di inglese a Roma</h2>
        <p>Prenota un test di livello gratuito. In 30 minuti scopri il tuo livello CEFR e il percorso più adatto a te.</p>
        <div class="cta-actions">
            <a href="{{ route('iscrizione') }}" class="btn-gold">Prenota il test gratuito →</a>
            <a href="{{ route('checkout.catalogo') }}" class="btn-outline-white">Vedi tutti i corsi</a>
        </div>
    </div>
</section>

@endsection
