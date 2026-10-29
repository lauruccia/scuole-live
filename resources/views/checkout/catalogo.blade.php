@extends('public.layout')

@section('title', \App\Models\PageContent::text('corsi', 'meta_title'))
@section('description', \App\Models\PageContent::text('corsi', 'meta_description'))
@section('keywords', \App\Models\PageContent::text('corsi', 'meta_keywords'))

@section('breadcrumb-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ route('home') }}" },
        { "@@type": "ListItem", "position": 2, "name": "Corsi", "item": "{{ route('checkout.catalogo') }}" }
    ]
}
</script>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/catalogo.css') }}">
<style>
/* Testo introduttivo sotto la testata */
.cat-intro {
    background: #fff;
    border-bottom: 1px solid #e8eef8;
    padding: 26px 0;
}
.cat-intro p {
    max-width: 860px; margin: 0;
    font-size: .95rem; color: var(--muted, #5a6b84); line-height: 1.75;
}

/* Tipologie di corso */
.tipologie-section { background: #f5f8fc; padding: 72px 0; }
.tipologie-header { max-width: 640px; margin-bottom: 36px; }
.tipologie-grid {
    display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;
}
.tipologia-card {
    background: #fff; border: 1px solid #e8eef8; border-radius: 14px;
    padding: 26px 24px;
    transition: border-color .2s, transform .2s;
}
.tipologia-card:hover { border-color: rgba(26,86,219,.3); transform: translateY(-3px); }
.tipologia-icon {
    width: 48px; height: 48px; border-radius: 12px;
    background: var(--blue-l, #e8effc);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; margin-bottom: 14px;
}
.tipologia-card h3 { font-size: 1rem; font-weight: 800; color: var(--text, #1c2a3d); margin-bottom: 8px; }
.tipologia-card p  { font-size: .85rem; color: var(--muted, #5a6b84); line-height: 1.7; margin: 0; }
.tipologie-orari {
    margin-top: 28px; text-align: center;
    background: #fff; border: 1.5px dashed var(--blue, #1A56DB); border-radius: 12px;
    padding: 16px 24px;
    font-size: .92rem; color: var(--text, #1c2a3d); line-height: 1.65;
}
@media (max-width: 760px) { .tipologie-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')

<section class="cat-hero">
    <div class="c cat-hero-inner">
        <div class="cat-hero-label">{{ \App\Models\PageContent::text('corsi', 'hero_label') }}</div>
        <h1>{!! \App\Models\PageContent::html('corsi', 'hero_title') !!}</h1>
        <p>{{ \App\Models\PageContent::text('corsi', 'hero_subtitle') }}</p>
    </div>
</section>

{{-- Testo introduttivo (dal vecchio sito, editabile dal pannello) --}}
<div class="cat-intro">
    <div class="c">
        <p>{!! \App\Models\PageContent::html('corsi', 'intro_text') !!}</p>
    </div>
</div>

<section class="courses-section">
    <div class="c">

        @if(session('info'))
            <div style="background:#dbeafe;border:1px solid #93c5fd;color:#1e40af;border-radius:10px;padding:12px 18px;margin-bottom:28px;font-size:14px;">
                {{ session('info') }}
            </div>
        @endif

        @if($courses->isEmpty())

            <div class="empty-state">
                <div style="font-size:3rem;margin-bottom:12px;">&#128218;</div>
                <h2>Nessun corso disponibile al momento</h2>
                <p>Contattaci per conoscere i corsi in partenza o per richiedere un corso personalizzato.</p>
                <a href="{{ route('iscrizione') }}" class="btn-enroll" style="margin-top:20px;display:inline-flex;">
                    Contattaci
                </a>
            </div>

        @else

            @php
                $minTot = $courses->map(fn ($c) => $c->total_price)->min();
                $langs  = array_values(\App\Filament\Resources\ContractResource::subjectOptions());
            @endphp
            <div class="sec-title">
                <h2>Componi il tuo corso</h2>
                <p>Scegli lingua, ore e tipologia di lezioni: il prezzo si aggiorna in base alla tua scelta.</p>
            </div>

            <div class="courses-grid">
                <div class="course-card">
                    <div class="course-thumb"><img src="{{ (new \App\Models\Course)->image_url }}" alt="Corso di lingua" loading="lazy"></div>
                    <div class="course-body">
                        <div class="course-name">Corso di lingua</div>
                        <div class="course-desc">Scegli lingua, ore e tipologia di lezioni.</div>
                        <div class="course-meta">
                            <span>&#127760; {{ implode(', ', $langs) }}</span>
                        </div>
                    </div>
                    <div class="course-footer">
                        <div class="price-block">
                            <div class="price-main">da &#8364; {{ number_format((float) $minTot, 2, ',', '.') }}</div>
                        </div>
                        <a href="{{ route('checkout.acquista') }}" class="btn-enroll">Scegli &#8594;</a>
                    </div>
                </div>
            </div>

        @endif

    </div>
</section>

{{-- Tipologie di corso (dal vecchio sito, editabili dal pannello) --}}
<section class="tipologie-section" aria-labelledby="tipologie-title">
    <div class="c">
        <div class="tipologie-header">
            <div class="section-label">{{ \App\Models\PageContent::text('corsi', 'tip_label') }}</div>
            <h2 class="sec-heading" id="tipologie-title">{!! \App\Models\PageContent::html('corsi', 'tip_title') !!}</h2>
            <p class="sec-subtext">{{ \App\Models\PageContent::text('corsi', 'tip_subtext') }}</p>
        </div>
        <div class="tipologie-grid">
            <div class="tipologia-card">
                <div class="tipologia-icon">👤</div>
                <h3>{{ \App\Models\PageContent::text('corsi', 'tip1_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('corsi', 'tip1_text') }}</p>
            </div>
            <div class="tipologia-card">
                <div class="tipologia-icon">👥</div>
                <h3>{{ \App\Models\PageContent::text('corsi', 'tip2_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('corsi', 'tip2_text') }}</p>
            </div>
            <div class="tipologia-card">
                <div class="tipologia-icon">🎒</div>
                <h3>{{ \App\Models\PageContent::text('corsi', 'tip3_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('corsi', 'tip3_text') }}</p>
            </div>
            <div class="tipologia-card">
                <div class="tipologia-icon">🎓</div>
                <h3>{{ \App\Models\PageContent::text('corsi', 'tip4_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('corsi', 'tip4_text') }}</p>
            </div>
        </div>
        <p class="tipologie-orari">{!! \App\Models\PageContent::html('corsi', 'tip_orari') !!}</p>
    </div>
</section>

{{-- Il nostro metodo (testo editoriale dal vecchio sito, editabile dal pannello) --}}
<section class="tipologie-section" style="background:#fff;" aria-labelledby="metodo-title">
    <div class="c" style="max-width:820px;">
        <div class="tipologie-header" style="margin-bottom:20px;">
            <div class="section-label">{{ \App\Models\PageContent::text('corsi', 'metodo_label') }}</div>
            <h2 class="sec-heading" id="metodo-title">{!! \App\Models\PageContent::html('corsi', 'metodo_title') !!}</h2>
        </div>
        <div style="font-size:.95rem; color:var(--muted, #5a6b84); line-height:1.8;">
            {!! \App\Models\PageContent::html('corsi', 'metodo_text') !!}
        </div>
    </div>
</section>

<section class="cta-strip">
    <div class="c cta-inner">
        <div>
            <h2>Non sei sicuro di quale corso fa per te?</h2>
            <p>Prenota un test di ingresso gratuito &mdash; valutiamo il tuo livello e ti consigliamo il percorso migliore.</p>
        </div>

        <a href="{{ route('iscrizione') }}" class="btn-yellow">
            PRENOTA IL TEST GRATUITO &#8594;
        </a>
    </div>
</section>

<div style="background:#001126;color:#fff;border-top:1px solid rgba(255,255,255,.12);padding:18px 0;font-size:13px;">
    <div class="c" style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;align-items:center;">
        <div style="display:flex;gap:10px;align-items:center;color:#dbe9ff;">
            &#128205; Viale Leonardo da Vinci, 193 &ndash; 00145 Roma
        </div>

        <div style="display:flex;gap:10px;align-items:center;color:#dbe9ff;">
            &#128222; <a href="tel:+390657437364" style="color:#dbe9ff;">06 5743734</a>
        </div>

        <div style="display:flex;gap:10px;align-items:center;color:#dbe9ff;">
            &#9993; <a href="mailto:info@aealanguagecenter.it" style="color:#dbe9ff;">info@aealanguagecenter.it</a>
        </div>
    </div>
</div>

@endsection
