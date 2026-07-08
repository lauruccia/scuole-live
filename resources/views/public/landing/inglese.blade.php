@extends('public.layout')

@section('title', \App\Models\PageContent::text('landing-inglese', 'meta_title'))
@section('description', \App\Models\PageContent::text('landing-inglese', 'meta_description'))
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
    "description": "Corsi di inglese a Roma con docenti qualificati madrelingua e/o bilingue. Tutti i livelli CEFR (A1–C2). Preparazione esami Trinity, Cambridge, IELTS, TOEFL. Lezioni individuali, mini gruppi, online o in presenza.",
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
        <h1>{!! \App\Models\PageContent::html('landing-inglese', 'hero_title') !!}</h1>
        <p class="subtitle">{{ \App\Models\PageContent::text('landing-inglese', 'hero_subtitle') }}</p>
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
                <p>Lezioni 1-a-1 con docente qualificato madrelingua o bilingue. Massima personalizzazione e velocità.</p>
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
{{-- @cache-bust-faq 2026-05-18 --}}
@php
    $faqItems = \App\Models\PageContent::items('landing-inglese', 'faq_items');
@endphp
<x-seo-faq
    :title="\App\Models\PageContent::text('landing-inglese', 'faq_title')"
    :subtitle="\App\Models\PageContent::text('landing-inglese', 'faq_subtitle')"
    :items="$faqItems"
/>

{{-- CTA --}}
<section class="cta-band">
    <div class="c cta-band-inner">
        <div class="label">{{ \App\Models\PageContent::text('landing-inglese', 'cta_label') }}</div>
        <h2>{{ \App\Models\PageContent::text('landing-inglese', 'cta_title') }}</h2>
        <p>{{ \App\Models\PageContent::text('landing-inglese', 'cta_text') }}</p>
        <div class="cta-actions">
            <a href="{{ route('iscrizione') }}" class="btn-gold">Prenota il test gratuito →</a>
            <a href="{{ route('checkout.catalogo') }}" class="btn-outline-white">Vedi tutti i corsi</a>
        </div>
    </div>
</section>

@endsection
