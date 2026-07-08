@extends('public.layout')

@section('title', \App\Models\PageContent::text('landing-italiano-stranieri', 'meta_title'))
@section('description', \App\Models\PageContent::text('landing-italiano-stranieri', 'meta_description'))
@section('keywords', 'italiano per stranieri Roma, scuola di italiano per stranieri Roma, corsi italiano stranieri Roma, italiano L2 Roma, preparazione CILS Roma, preparazione PLIDA Roma, Italian courses Rome, learn Italian in Rome, Italian school Rome, Italian for foreigners Rome, italiano stranieri San Paolo')
@section('og-image-alt', 'Italian courses in Rome — Italiano per stranieri a Roma')

@section('breadcrumb-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ route('home') }}" },
        { "@@type": "ListItem", "position": 2, "name": "Italiano per Stranieri a Roma", "item": "{{ route('landing.italiano-stranieri') }}" }
    ]
}
</script>
@endsection

@section('extra-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Course",
    "name": "Italian Courses for Foreigners in Rome — Italiano per stranieri",
    "description": "Italian language courses in Rome for foreigners. All CEFR levels (A1–C2). CILS and PLIDA exam preparation. Native Italian teachers, individual lessons or small groups, in-person or online.",
    "provider": {
        "@@type": "EducationalOrganization",
        "name": "A&A Language Center",
        "sameAs": "{{ config('app.url') }}"
    },
    "inLanguage": "it",
    "educationalLevel": "A1–C2 (CEFR)",
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
.bilingual-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: start; }
.lang-block { background: var(--white); border: 1.5px solid var(--border); border-radius: var(--radius-lg); padding: 36px 32px; }
.lang-block .flag-label {
    display: inline-flex; align-items: center; gap: 8px;
    font-size: .72rem; font-weight: 800; letter-spacing: .12em;
    text-transform: uppercase; color: var(--blue);
    margin-bottom: 16px;
}
.lang-block h2 { font-size: 1.4rem; font-weight: 800; color: var(--navy); margin-bottom: 14px; letter-spacing: -.02em; }
.lang-block p { font-size: .92rem; color: var(--muted); line-height: 1.75; margin-bottom: 12px; }
.lang-block p:last-of-type { margin-bottom: 0; }

.cert-cards { display: grid; grid-template-columns: repeat(2, 1fr); gap: 22px; }
.cert-card {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
    color: #fff; border-radius: var(--radius-lg); padding: 32px 28px;
}
.cert-card-label {
    font-size: .68rem; font-weight: 800; letter-spacing: .12em;
    text-transform: uppercase; color: var(--gold); margin-bottom: 10px;
}
.cert-card h3 { font-size: 1.15rem; font-weight: 800; margin-bottom: 10px; }
.cert-card p { font-size: .9rem; color: rgba(255,255,255,.72); line-height: 1.7; margin: 0; }

@media (max-width: 900px) {
    .bilingual-grid, .cert-cards { grid-template-columns: 1fr; }
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
            <span>Italiano per Stranieri</span>
        </div>
        <h1>Italiano per <em>Stranieri</em> a Roma <span style="display:block;font-size:1.3rem;color:rgba(255,255,255,.65);margin-top:8px;">Italian Courses in Rome</span></h1>
        <p class="subtitle">Impara l'italiano nel cuore di Roma — Learn Italian in the heart of Rome. Corsi per stranieri di tutti i livelli, preparazione esami CILS e PLIDA.</p>
    </div>
</section>

{{-- BILINGUE INTRO --}}
<section class="sec">
    <div class="c">
        <div class="bilingual-grid">
            <div class="lang-block">
                <span class="flag-label">🇮🇹 Italiano</span>
                <h2>Studia l'italiano a Roma</h2>
                <p>A&amp;A Language Center è una <strong>scuola di italiano per stranieri a Roma</strong> attiva dal 2002. Insegniamo italiano a studenti provenienti da tutto il mondo, in piccoli gruppi internazionali o lezioni individuali.</p>
                <p>I nostri corsi seguono il <strong>framework europeo CEFR</strong> (A1–C2) e preparano agli esami ufficiali <strong>CILS</strong> (Università per Stranieri di Siena) e <strong>PLIDA</strong> (Società Dante Alighieri), riconosciuti dallo Stato italiano per cittadinanza, studio, lavoro e residenza.</p>
                <p>Ci troviamo nel quartiere <strong>San Paolo</strong>, polo universitario di Roma Tre — un ambiente vivace, autentico e perfettamente collegato al centro storico.</p>
            </div>
            <div class="lang-block">
                <span class="flag-label">🇬🇧 English</span>
                <h2>Learn Italian in Rome</h2>
                <p>A&amp;A Language Center is an <strong>Italian language school for foreigners in Rome</strong>, founded in 2002. We teach Italian to students from all over the world, in small international groups or one-to-one lessons.</p>
                <p>Our courses follow the <strong>CEFR European framework</strong> (A1–C2) and prepare students for the official <strong>CILS</strong> exams (University for Foreigners of Siena) and <strong>PLIDA</strong> exams (Società Dante Alighieri), both recognized by the Italian government for citizenship, study, work and residence permits.</p>
                <p>We are located in the <strong>San Paolo</strong> district, the university hub of Roma Tre — a lively, authentic area perfectly connected to the historic center of Rome.</p>
            </div>
        </div>
    </div>
</section>

{{-- CERTIFICAZIONI --}}
<section class="sec sec-bg">
    <div class="c">
        <div class="sec-header center">
            <div class="section-label" style="justify-content:center;">Certificazioni · Certifications</div>
            <h2 class="sec-heading">Preparazione esami <em>ufficiali</em> di italiano</h2>
            <p class="sec-subtext" style="text-align:center;margin:0 auto;">Le certificazioni CILS e PLIDA sono richieste per la cittadinanza italiana, l'iscrizione a università italiane e molti permessi di soggiorno.</p>
        </div>
        <div class="cert-cards">
            <div class="cert-card">
                <div class="cert-card-label">CILS — Università per Stranieri di Siena</div>
                <h3>Preparazione CILS A1 / A2 / B1 / B2 / C1 / C2</h3>
                <p>Il <strong>CILS</strong> è la certificazione ufficiale dello Stato italiano. Il livello B1 è richiesto per la <strong>cittadinanza italiana</strong>. Prepariamo agli esami in tutte le sessioni dell'anno con docenti certificati.</p>
            </div>
            <div class="cert-card">
                <div class="cert-card-label">PLIDA — Società Dante Alighieri</div>
                <h3>Preparazione PLIDA A1 a C2</h3>
                <p>Il <strong>PLIDA</strong> è riconosciuto dal Ministero degli Affari Esteri, dal MIUR e dal Ministero del Lavoro. Adatto per chi vuole certificare l'italiano per lavoro, studio o curriculum.</p>
            </div>
        </div>
    </div>
</section>

{{-- TARGETS --}}
<section class="sec">
    <div class="c">
        <div class="sec-header center">
            <div class="section-label" style="justify-content:center;">Per chi · For whom</div>
            <h2 class="sec-heading">A chi sono rivolti i corsi</h2>
        </div>
        <div class="bilingual-grid">
            <div class="lang-block">
                <h2 style="font-size:1.1rem;">Italiano per:</h2>
                <p>• Studenti universitari (Erasmus, dottorandi, ricercatori)<br>
                   • Lavoratori espatriati a Roma<br>
                   • Famiglie di diplomatici e funzionari internazionali<br>
                   • Persone in attesa di cittadinanza italiana<br>
                   • Au pair e ragazze alla pari<br>
                   • Turisti che vogliono vivere Roma come locali</p>
            </div>
            <div class="lang-block">
                <h2 style="font-size:1.1rem;">Italian for:</h2>
                <p>• University students (Erasmus, PhD, researchers)<br>
                   • Expat workers living in Rome<br>
                   • Diplomatic and international staff families<br>
                   • People applying for Italian citizenship<br>
                   • Au pairs<br>
                   • Tourists who want to experience Rome like a local</p>
            </div>
        </div>
    </div>
</section>

{{-- FAQ --}}
{{-- @cache-bust-faq 2026-05-18 --}}
@php
    $faqItems = \App\Models\PageContent::items('landing-italiano-stranieri', 'faq_items');
@endphp
<x-seo-faq
    :title="\App\Models\PageContent::text('landing-italiano-stranieri', 'faq_title')"
    :items="$faqItems"
/>

{{-- CTA --}}
<section class="cta-band">
    <div class="c cta-band-inner">
        <div class="label">{{ \App\Models\PageContent::text('landing-italiano-stranieri', 'cta_label') }}</div>
        <h2>{{ \App\Models\PageContent::text('landing-italiano-stranieri', 'cta_title') }}</h2>
        <p>{!! \App\Models\PageContent::html('landing-italiano-stranieri', 'cta_text') !!}</p>
        <div class="cta-actions">
            <a href="{{ route('iscrizione') }}" class="btn-gold">Free Placement Test →</a>
            <a href="{{ route('checkout.catalogo') }}" class="btn-outline-white">See all courses</a>
        </div>
    </div>
</section>

@endsection
