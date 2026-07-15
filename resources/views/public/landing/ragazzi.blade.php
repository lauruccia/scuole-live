@extends('public.layout')

@section('title', \App\Models\PageContent::text('landing-ragazzi', 'meta_title'))
@section('description', \App\Models\PageContent::text('landing-ragazzi', 'meta_description'))
@section('keywords', 'corsi di lingue per ragazzi Roma, corsi inglese ragazzi Roma, recupero debiti formativi lingue Roma, corsi di recupero estivi lingue, preparazione Trinity ragazzi Roma, preparazione Cambridge ragazzi, corsi lingue medie superiori Roma, scuola di lingue per adolescenti Roma')
@section('og-image-alt', 'Corsi di lingue per ragazzi a Roma — A&A Language Center')

@section('breadcrumb-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ route('home') }}" },
        { "@@type": "ListItem", "position": 2, "name": "Corsi per Ragazzi", "item": "{{ route('landing.ragazzi') }}" }
    ]
}
</script>
@endsection

@section('extra-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Course",
    "name": "Corsi di Lingue per Ragazzi a Roma",
    "description": "Corsi di lingue per studenti delle scuole medie e superiori a Roma: recupero debiti formativi, potenziamento e preparazione alle certificazioni Trinity e Cambridge.",
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
        "audienceType": "Studenti scuole medie e superiori (11-19 anni)"
    },
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
    .perche-grid { grid-template-columns: 1fr; }
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
            <span>Corsi per Ragazzi</span>
        </div>
        <h1>{!! \App\Models\PageContent::html('landing-ragazzi', 'hero_title') !!}</h1>
        <p class="subtitle">{{ \App\Models\PageContent::text('landing-ragazzi', 'hero_subtitle') }}</p>
    </div>
</section>

{{-- INTRO --}}
<section class="sec">
    <div class="c">
        <div class="sec-header center">
            <div class="section-label" style="justify-content:center;">Scuola secondaria di primo e secondo grado</div>
            <h2 class="sec-heading">Un percorso pensato per <em>l'età scolare</em></h2>
            <p class="sec-subtext" style="text-align:center;margin:0 auto;">{{ \App\Models\PageContent::text('landing-ragazzi', 'intro_text') }}</p>
        </div>
    </div>
</section>

{{-- PERCHÉ --}}
<section class="sec sec-bg">
    <div class="c">
        <div class="sec-header center">
            <div class="section-label" style="justify-content:center;">Perché A&amp;A</div>
            <h2 class="sec-heading">Fatto su misura per <em>ragazzi</em></h2>
        </div>
        <div class="perche-grid">
            <div class="perche-card">
                <div class="perche-icon">📚</div>
                <h3>{{ \App\Models\PageContent::text('landing-ragazzi', 'perche1_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('landing-ragazzi', 'perche1_text') }}</p>
            </div>
            <div class="perche-card">
                <div class="perche-icon">🏅</div>
                <h3>{{ \App\Models\PageContent::text('landing-ragazzi', 'perche2_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('landing-ragazzi', 'perche2_text') }}</p>
            </div>
            <div class="perche-card">
                <div class="perche-icon">🎒</div>
                <h3>{{ \App\Models\PageContent::text('landing-ragazzi', 'perche3_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('landing-ragazzi', 'perche3_text') }}</p>
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

{{-- MODALITA --}}
<section class="sec sec-bg">
    <div class="c">
        <div class="sec-header center">
            <div class="section-label" style="justify-content:center;">Modalità</div>
            <h2 class="sec-heading">Come si <em>studia</em></h2>
        </div>
        <div class="modalita-list">
            <div class="modalita-c">
                <div class="modalita-c-icon">👥</div>
                <h4>Mini Gruppo</h4>
                <p>3-4 studenti omogenei per età e livello. Più conversazione, più motivazione, costo contenuto.</p>
            </div>
            <div class="modalita-c">
                <div class="modalita-c-icon">👤</div>
                <h4>Individuale</h4>
                <p>Lezioni 1-a-1, ideali per il recupero mirato di un debito formativo o per un ripasso concentrato.</p>
            </div>
            <div class="modalita-c">
                <div class="modalita-c-icon">💻</div>
                <h4>Online</h4>
                <p>Stessa qualità della lezione in presenza, comoda tra i tanti impegni scolastici del pomeriggio.</p>
            </div>
            <div class="modalita-c">
                <div class="modalita-c-icon">☀️</div>
                <h4>Corsi estivi/invernali</h4>
                <p>Recupero debiti formativi concentrato prima degli esami di riparazione o del rientro a scuola.</p>
            </div>
        </div>
    </div>
</section>

{{-- FAQ --}}
@php
    $faqItems = \App\Models\PageContent::items('landing-ragazzi', 'faq_items');
@endphp
<x-seo-faq
    :title="\App\Models\PageContent::text('landing-ragazzi', 'faq_title')"
    :subtitle="\App\Models\PageContent::text('landing-ragazzi', 'faq_subtitle')"
    :items="$faqItems"
/>

{{-- CTA --}}
<section class="cta-band">
    <div class="c cta-band-inner">
        <div class="label">{{ \App\Models\PageContent::text('landing-ragazzi', 'cta_label') }}</div>
        <h2>{{ \App\Models\PageContent::text('landing-ragazzi', 'cta_title') }}</h2>
        <p>{{ \App\Models\PageContent::text('landing-ragazzi', 'cta_text') }}</p>
        <div class="cta-actions">
            <a href="{{ route('iscrizione') }}" class="btn-gold">Prenota il test gratuito →</a>
            <a href="{{ route('checkout.catalogo') }}" class="btn-outline-white">Vedi tutti i corsi</a>
        </div>
    </div>
</section>

@endsection
