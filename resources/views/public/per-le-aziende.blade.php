@extends('public.layout')

@section('title', \App\Models\PageContent::text('per-le-aziende', 'meta_title'))
@section('description', \App\Models\PageContent::text('per-le-aziende', 'meta_description'))
@section('keywords', \App\Models\PageContent::text('per-le-aziende', 'meta_keywords'))

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
        <h1>{!! \App\Models\PageContent::html('per-le-aziende', 'hero_title') !!}</h1>
        <p class="subtitle">{{ \App\Models\PageContent::text('per-le-aziende', 'hero_subtitle') }}</p>
    </div>
</section>

{{-- INTRO --}}
<section class="sec">
    <div class="c">
        <div class="intro-grid">
            <div class="intro-text">
                <div class="section-label">{{ \App\Models\PageContent::text('per-le-aziende', 'intro_label') }}</div>
                <h2 class="sec-heading">{!! \App\Models\PageContent::html('per-le-aziende', 'intro_title') !!}</h2>
                {!! \App\Models\PageContent::html('per-le-aziende', 'intro_text') !!}
            </div>
            <div class="intro-photo">
                <img src="{{ \App\Models\PageContent::image('per-le-aziende', 'intro_image') }}"
                     alt="Corso di inglese aziendale a Roma — formazione linguistica B2B A&A Language Center" loading="lazy" width="900" height="600">
            </div>
        </div>
    </div>
</section>

{{-- COME FUNZIONA --}}
<section class="sec sec-bg">
    <div class="c">
        <div class="sec-header">
            <div class="section-label">{{ \App\Models\PageContent::text('per-le-aziende', 'steps_label') }}</div>
            <h2 class="sec-heading">{!! \App\Models\PageContent::html('per-le-aziende', 'steps_title') !!}</h2>
            <p class="sec-subtext">{{ \App\Models\PageContent::text('per-le-aziende', 'steps_subtext') }}</p>
        </div>
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-num">1</div>
                <h3>{{ \App\Models\PageContent::text('per-le-aziende', 'step1_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('per-le-aziende', 'step1_text') }}</p>
            </div>
            <div class="step-card">
                <div class="step-num">2</div>
                <h3>{{ \App\Models\PageContent::text('per-le-aziende', 'step2_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('per-le-aziende', 'step2_text') }}</p>
            </div>
            <div class="step-card">
                <div class="step-num">3</div>
                <h3>{{ \App\Models\PageContent::text('per-le-aziende', 'step3_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('per-le-aziende', 'step3_text') }}</p>
            </div>
        </div>
    </div>
</section>

{{-- MODALITA --}}
<section class="sec">
    <div class="c">
        <div class="sec-header">
            <div class="section-label">{{ \App\Models\PageContent::text('per-le-aziende', 'modalita_label') }}</div>
            <h2 class="sec-heading">{!! \App\Models\PageContent::html('per-le-aziende', 'modalita_title') !!}</h2>
            <p class="sec-subtext">{{ \App\Models\PageContent::text('per-le-aziende', 'modalita_subtext') }}</p>
        </div>
        <div class="modalita-grid">
            <div class="modalita-item">
                <div class="modalita-icon">🏫</div>
                <div>
                    <h3>{{ \App\Models\PageContent::text('per-le-aziende', 'mod1_title') }}</h3>
                    <p>{{ \App\Models\PageContent::text('per-le-aziende', 'mod1_text') }}</p>
                </div>
            </div>
            <div class="modalita-item">
                <div class="modalita-icon">🏢</div>
                <div>
                    <h3>{{ \App\Models\PageContent::text('per-le-aziende', 'mod2_title') }}</h3>
                    <p>{{ \App\Models\PageContent::text('per-le-aziende', 'mod2_text') }}</p>
                </div>
            </div>
            <div class="modalita-item">
                <div class="modalita-icon">💻</div>
                <div>
                    <h3>{{ \App\Models\PageContent::text('per-le-aziende', 'mod3_title') }}</h3>
                    <p>{{ \App\Models\PageContent::text('per-le-aziende', 'mod3_text') }}</p>
                </div>
            </div>
            <div class="modalita-item">
                <div class="modalita-icon">👥</div>
                <div>
                    <h3>{{ \App\Models\PageContent::text('per-le-aziende', 'mod4_title') }}</h3>
                    <p>{{ \App\Models\PageContent::text('per-le-aziende', 'mod4_text') }}</p>
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
        <p class="clienti-label">{{ \App\Models\PageContent::text('per-le-aziende', 'clienti_label') }}</p>
        <div class="clienti-grid">
            @foreach (\App\Models\PageContent::lines('per-le-aziende', 'clienti_tags') as $cliente)
                <span class="cliente-tag">{{ $cliente }}</span>
            @endforeach
        </div>
    </div>
</div>

{{-- CTA --}}
<section class="cta-band">
    <div class="c cta-band-inner">
        <div class="label">{{ \App\Models\PageContent::text('per-le-aziende', 'cta_label') }}</div>
        <h2>{{ \App\Models\PageContent::text('per-le-aziende', 'cta_title') }}</h2>
        <p>{{ \App\Models\PageContent::text('per-le-aziende', 'cta_text') }}</p>
        <div class="cta-actions">
            <a href="{{ route('iscrizione') }}" class="btn-gold">{{ \App\Models\PageContent::text('per-le-aziende', 'cta_btn1') }}</a>
            <a href="mailto:info@aealanguagecenter.it" class="btn-outline-white">{{ \App\Models\PageContent::text('per-le-aziende', 'cta_btn2') }}</a>
        </div>
    </div>
</section>

@endsection
