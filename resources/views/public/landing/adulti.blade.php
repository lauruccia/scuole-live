@extends('public.layout')

@section('title', \App\Models\PageContent::text('landing-adulti', 'meta_title'))
@section('description', \App\Models\PageContent::text('landing-adulti', 'meta_description'))
@section('keywords', 'corsi di lingue per adulti Roma, corsi inglese adulti Roma, corsi di lingue individuali Roma, corsi lingue flessibili Roma, corso di lingua per lavoro Roma, corsi lingue serali Roma, scuola di lingue adulti Roma San Paolo')
@section('og-image-alt', 'Corsi di lingue per adulti a Roma — A&A Language Center')

@section('breadcrumb-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ route('home') }}" },
        { "@@type": "ListItem", "position": 2, "name": "Corsi per Adulti", "item": "{{ route('landing.adulti') }}" }
    ]
}
</script>
@endsection

@section('extra-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Course",
    "name": "Corsi di Lingue per Adulti a Roma",
    "description": "Corsi di lingue personalizzati per adulti a Roma: lavoro, università, viaggi o crescita personale. Orari flessibili, individuali o mini gruppo, in presenza o online.",
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
    "audience": {
        "@@type": "EducationalAudience",
        "educationalRole": "student",
        "audienceType": "Adulti"
    },
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
.perche-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; }
.perche-card {
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: var(--radius-lg); padding: 28px 24px;
    transition: transform .25s, box-shadow .25s, border-color .25s;
}
.perche-card:hover { transform: translateY(-4px); border-color: var(--blue); box-shadow: var(--shadow); }
.perche-icon { font-size: 1.8rem; margin-bottom: 14px; }
.perche-card h3 { font-size: 1rem; font-weight: 800; color: var(--navy); margin-bottom: 8px; }
.perche-card p { font-size: .875rem; color: var(--muted); line-height: 1.65; margin: 0; }

.lang-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
.lang-chip {
    display: flex; align-items: center; gap: 10px;
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: 50px; padding: 12px 18px;
    font-size: .875rem; font-weight: 700; color: var(--navy);
    transition: border-color .25s, transform .25s;
}
.lang-chip:hover { border-color: var(--blue); transform: translateY(-2px); }
.lang-chip .flag { font-size: 1.15rem; }

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
    .perche-grid, .cert-cards { grid-template-columns: 1fr; }
    .lang-grid { grid-template-columns: 1fr 1fr; }
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
            <span>Corsi per Adulti</span>
        </div>
        <h1>{!! \App\Models\PageContent::html('landing-adulti', 'hero_title') !!}</h1>
        <p class="subtitle">{{ \App\Models\PageContent::text('landing-adulti', 'hero_subtitle') }}</p>
    </div>
</section>

{{-- INTRO --}}
<section class="sec">
    <div class="c">
        <div class="sec-header center">
            <div class="section-label" style="justify-content:center;">Corsi personalizzati per adulti</div>
            <h2 class="sec-heading">Il momento giusto è <em>adesso</em></h2>
            <p class="sec-subtext" style="text-align:center;margin:0 auto;">{{ \App\Models\PageContent::text('landing-adulti', 'intro_text') }}</p>
        </div>
    </div>
</section>

{{-- PERCHÉ --}}
<section class="sec sec-bg">
    <div class="c">
        <div class="sec-header center">
            <div class="section-label" style="justify-content:center;">Perché A&amp;A</div>
            <h2 class="sec-heading">Pensato per la vita da <em>adulti</em></h2>
        </div>
        <div class="perche-grid">
            <div class="perche-card">
                <div class="perche-icon">🎯</div>
                <h3>{{ \App\Models\PageContent::text('landing-adulti', 'perche1_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('landing-adulti', 'perche1_text') }}</p>
            </div>
            <div class="perche-card">
                <div class="perche-icon">🗓️</div>
                <h3>{{ \App\Models\PageContent::text('landing-adulti', 'perche2_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('landing-adulti', 'perche2_text') }}</p>
            </div>
            <div class="perche-card">
                <div class="perche-icon">🏅</div>
                <h3>{{ \App\Models\PageContent::text('landing-adulti', 'perche3_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('landing-adulti', 'perche3_text') }}</p>
            </div>
        </div>
    </div>
</section>

{{-- LINGUE --}}
<section class="sec">
    <div class="c">
        <div class="sec-header center">
            <div class="section-label" style="justify-content:center;">Le lingue</div>
            <h2 class="sec-heading">Scegli la <em>lingua</em> giusta</h2>
        </div>
        <div class="lang-grid">
            <a href="{{ route('checkout.catalogo') }}" class="lang-chip"><span class="flag">🇬🇧</span> Inglese</a>
            <a href="{{ route('checkout.catalogo') }}" class="lang-chip"><span class="flag">🇫🇷</span> Francese</a>
            <a href="{{ route('checkout.catalogo') }}" class="lang-chip"><span class="flag">🇪🇸</span> Spagnolo</a>
            <a href="{{ route('checkout.catalogo') }}" class="lang-chip"><span class="flag">🇩🇪</span> Tedesco</a>
            <a href="{{ route('checkout.catalogo') }}" class="lang-chip"><span class="flag">🇵🇹</span> Portoghese</a>
            <a href="{{ route('checkout.catalogo') }}" class="lang-chip"><span class="flag">🇷🇺</span> Russo</a>
            <a href="{{ route('checkout.catalogo') }}" class="lang-chip"><span class="flag">🇸🇦</span> Arabo</a>
            <a href="{{ route('checkout.catalogo') }}" class="lang-chip"><span class="flag">🇮🇹</span> Italiano per stranieri</a>
        </div>
    </div>
</section>

{{-- CERTIFICAZIONI --}}
<section class="sec sec-bg">
    <div class="c">
        <div class="sec-header">
            <div class="section-label">Certificazioni</div>
            <h2 class="sec-heading">Preparazione esami <em>ufficiali</em></h2>
            <p class="sec-subtext">A&amp;A Language Center è <strong>Sede Ufficiale Esami Trinity College London n° 8241</strong> e prepara alle principali certificazioni internazionali per adulti.</p>
        </div>
        <div class="cert-cards">
            <div class="cert-card gold">
                <div class="cert-card-label">★ Trinity College London</div>
                <h3>GESE & ISE — Sede Esami n° 8241</h3>
                <p>Ente certificatore britannico riconosciuto dal Ministero. Sosteniamo gli esami direttamente nella nostra sede di Roma San Paolo, con sessioni durante tutto l'anno.</p>
            </div>
            <div class="cert-card">
                <div class="cert-card-label">Cambridge, IELTS, TOEFL</div>
                <h3>Certificazioni di inglese</h3>
                <p>Preparazione completa a Cambridge (KET–CPE), IELTS Academic e General, TOEFL iBT, con simulazioni d'esame e materiali ufficiali.</p>
            </div>
            <div class="cert-card">
                <div class="cert-card-label">DELF/DALF · DELE · Goethe</div>
                <h3>Francese, Spagnolo, Tedesco</h3>
                <p>Preparazione alle certificazioni ufficiali di francese, spagnolo e tedesco, riconosciute a livello internazionale per lavoro e università.</p>
            </div>
            <div class="cert-card">
                <div class="cert-card-label">CILS</div>
                <h3>Italiano per stranieri</h3>
                <p>Preparazione CILS, incluso il livello B1 richiesto per la domanda di cittadinanza italiana.</p>
            </div>
        </div>
    </div>
</section>

{{-- MODALITA --}}
<section class="sec">
    <div class="c">
        <div class="sec-header center">
            <div class="section-label" style="justify-content:center;">Modalità</div>
            <h2 class="sec-heading">Scegli <em>come</em> studiare</h2>
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
                <p>Gruppi 3-6 studenti dello stesso livello. Più conversazione, meno costo.</p>
            </div>
            <div class="modalita-c">
                <div class="modalita-c-icon">💻</div>
                <h4>Online</h4>
                <p>Lezioni in videoconferenza con la stessa qualità di una lezione in presenza.</p>
            </div>
            <div class="modalita-c">
                <div class="modalita-c-icon">📞</div>
                <h4>Al telefono</h4>
                <p>30 minuti al giorno per migliorare lo speaking. Ideale per professionisti con poco tempo.</p>
            </div>
        </div>
    </div>
</section>

{{-- FAQ --}}
@php
    $faqItems = \App\Models\PageContent::items('landing-adulti', 'faq_items');
@endphp
<x-seo-faq
    :title="\App\Models\PageContent::text('landing-adulti', 'faq_title')"
    :subtitle="\App\Models\PageContent::text('landing-adulti', 'faq_subtitle')"
    :items="$faqItems"
/>

{{-- CTA --}}
<section class="cta-band">
    <div class="c cta-band-inner">
        <div class="label">{{ \App\Models\PageContent::text('landing-adulti', 'cta_label') }}</div>
        <h2>{{ \App\Models\PageContent::text('landing-adulti', 'cta_title') }}</h2>
        <p>{{ \App\Models\PageContent::text('landing-adulti', 'cta_text') }}</p>
        <div class="cta-actions">
            <a href="{{ route('iscrizione') }}" class="btn-gold">Prenota il test gratuito →</a>
            <a href="{{ route('checkout.catalogo') }}" class="btn-outline-white">Vedi tutti i corsi</a>
        </div>
    </div>
</section>

@endsection
