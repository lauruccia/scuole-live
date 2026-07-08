@extends('public.layout')

@section('title', \App\Models\PageContent::text('la-scuola', 'meta_title'))
@section('description', \App\Models\PageContent::text('la-scuola', 'meta_description'))
@section('keywords', \App\Models\PageContent::text('la-scuola', 'meta_keywords'))

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
        <h1>{!! \App\Models\PageContent::html('la-scuola', 'hero_title') !!}</h1>
        <p class="subtitle">{!! \App\Models\PageContent::html('la-scuola', 'hero_subtitle') !!}</p>
    </div>
</section>

{{-- INTRO --}}
<section class="sec">
    <div class="c">
        <div class="intro-grid">
            <div class="intro-text">
                <div class="section-label">{{ \App\Models\PageContent::text('la-scuola', 'intro_label') }}</div>
                <h2 class="sec-heading">{!! \App\Models\PageContent::html('la-scuola', 'intro_title') !!}</h2>
                {!! \App\Models\PageContent::html('la-scuola', 'intro_text') !!}
                <div class="orari-badge">
                    {!! \App\Models\PageContent::html('la-scuola', 'intro_orari') !!}
                </div>
            </div>
            <div class="intro-photo">
                <img src="{{ \App\Models\PageContent::image('la-scuola', 'intro_image') }}"
                     alt="Aula di una scuola di lingue a Roma San Paolo — A&A Language Center" loading="lazy" width="900" height="600">
            </div>
        </div>
    </div>
</section>

{{-- STATS --}}
<div class="stats-band">
    <div class="c">
        <div class="stats-inner">
            <div class="stat-item"><div class="stat-num">{{ \App\Models\PageContent::text('la-scuola', 'stat1_num') }}</div><div class="stat-label">{{ \App\Models\PageContent::text('la-scuola', 'stat1_label') }}</div></div>
            <div class="stat-item"><div class="stat-num">{{ \App\Models\PageContent::text('la-scuola', 'stat2_num') }}</div><div class="stat-label">{{ \App\Models\PageContent::text('la-scuola', 'stat2_label') }}</div></div>
            <div class="stat-item"><div class="stat-num">{{ \App\Models\PageContent::text('la-scuola', 'stat3_num') }}</div><div class="stat-label">{{ \App\Models\PageContent::text('la-scuola', 'stat3_label') }}</div></div>
            <div class="stat-item"><div class="stat-num">{{ \App\Models\PageContent::text('la-scuola', 'stat4_num') }}</div><div class="stat-label">{{ \App\Models\PageContent::text('la-scuola', 'stat4_label') }}</div></div>
        </div>
    </div>
</div>

{{-- INSEGNANTI --}}
<section class="sec sec-bg">
    <div class="c">
        <div class="sec-header">
            <div class="section-label">{{ \App\Models\PageContent::text('la-scuola', 'team_label') }}</div>
            <h2 class="sec-heading">{!! \App\Models\PageContent::html('la-scuola', 'team_title') !!}</h2>
            <p class="sec-subtext">{{ \App\Models\PageContent::text('la-scuola', 'team_subtext') }}</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🎯</div>
                <h3>{{ \App\Models\PageContent::text('la-scuola', 'team1_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('la-scuola', 'team1_text') }}</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🌍</div>
                <h3>{{ \App\Models\PageContent::text('la-scuola', 'team2_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('la-scuola', 'team2_text') }}</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3>{{ \App\Models\PageContent::text('la-scuola', 'team3_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('la-scuola', 'team3_text') }}</p>
            </div>
        </div>
    </div>
</section>

{{-- CERTIFICAZIONI --}}
<section class="sec sec-dark">
    <div class="c">
        <div class="sec-header">
            <div class="section-label white">{{ \App\Models\PageContent::text('la-scuola', 'cert_label') }}</div>
            <h2 class="sec-heading white">{!! \App\Models\PageContent::html('la-scuola', 'cert_title') !!}</h2>
            <p class="sec-subtext white">{!! \App\Models\PageContent::html('la-scuola', 'cert_text') !!}</p>
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
            <div class="section-label">{{ \App\Models\PageContent::text('la-scuola', 'pillars_label') }}</div>
            <h2 class="sec-heading">{!! \App\Models\PageContent::html('la-scuola', 'pillars_title') !!}</h2>
        </div>
        <div class="pillars-grid">
            <div class="pillar-card">
                <div class="pillar-num">01</div>
                <h3>{{ \App\Models\PageContent::text('la-scuola', 'pillar1_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('la-scuola', 'pillar1_text') }}</p>
            </div>
            <div class="pillar-card">
                <div class="pillar-num">02</div>
                <h3>{{ \App\Models\PageContent::text('la-scuola', 'pillar2_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('la-scuola', 'pillar2_text') }}</p>
            </div>
            <div class="pillar-card">
                <div class="pillar-num">03</div>
                <h3>{{ \App\Models\PageContent::text('la-scuola', 'pillar3_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('la-scuola', 'pillar3_text') }}</p>
            </div>
            <div class="pillar-card">
                <div class="pillar-num">04</div>
                <h3>{{ \App\Models\PageContent::text('la-scuola', 'pillar4_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('la-scuola', 'pillar4_text') }}</p>
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="cta-band">
    <div class="c cta-band-inner">
        <div class="label">{{ \App\Models\PageContent::text('la-scuola', 'cta_label') }}</div>
        <h2>{{ \App\Models\PageContent::text('la-scuola', 'cta_title') }}</h2>
        <p>{{ \App\Models\PageContent::text('la-scuola', 'cta_text') }}</p>
        <div class="cta-actions">
            <a href="{{ route('iscrizione') }}" class="btn-gold">{{ \App\Models\PageContent::text('la-scuola', 'cta_btn1') }}</a>
            <a href="{{ route('contattaci') }}" class="btn-outline-white">{{ \App\Models\PageContent::text('la-scuola', 'cta_btn2') }}</a>
        </div>
    </div>
</section>

@endsection
