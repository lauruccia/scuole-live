@extends('public.layout')

@section('title', \App\Models\PageContent::text('servizi', 'meta_title'))
@section('description', \App\Models\PageContent::text('servizi', 'meta_description'))
@section('keywords', \App\Models\PageContent::text('servizi', 'meta_keywords'))

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
        <h1>{!! \App\Models\PageContent::html('servizi', 'hero_title') !!}</h1>
        <p class="subtitle">{{ \App\Models\PageContent::text('servizi', 'hero_subtitle') }}</p>
    </div>
</section>

{{-- SERVIZI --}}
<section class="sec">
    <div class="c">
        <div class="sec-header">
            <div class="section-label">{{ \App\Models\PageContent::text('servizi', 'services_label') }}</div>
            <h2 class="sec-heading">{!! \App\Models\PageContent::html('servizi', 'services_title') !!}</h2>
            <p class="sec-subtext">{{ \App\Models\PageContent::text('servizi', 'services_subtext') }}</p>
        </div>
        <div class="services-grid">
            <div class="service-card">
                <div class="service-icon">💻</div>
                <h3>{{ \App\Models\PageContent::text('servizi', 'service1_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('servizi', 'service1_text') }}</p>
                <span class="service-tag">{{ \App\Models\PageContent::text('servizi', 'service1_tag') }}</span>
            </div>
            <div class="service-card">
                <div class="service-icon">🏫</div>
                <h3>{{ \App\Models\PageContent::text('servizi', 'service2_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('servizi', 'service2_text') }}</p>
                <span class="service-tag">{{ \App\Models\PageContent::text('servizi', 'service2_tag') }}</span>
            </div>
            <div class="service-card">
                <div class="service-icon">📞</div>
                <h3>{{ \App\Models\PageContent::text('servizi', 'service3_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('servizi', 'service3_text') }}</p>
                <span class="service-tag">{{ \App\Models\PageContent::text('servizi', 'service3_tag') }}</span>
            </div>
            <div class="service-card">
                <div class="service-icon">📝</div>
                <h3>{{ \App\Models\PageContent::text('servizi', 'service4_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('servizi', 'service4_text') }}</p>
                <span class="service-tag">{{ \App\Models\PageContent::text('servizi', 'service4_tag') }}</span>
            </div>
            <div class="service-card">
                <div class="service-icon">🏆</div>
                <h3>{{ \App\Models\PageContent::text('servizi', 'service5_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('servizi', 'service5_text') }}</p>
                <span class="service-tag">{{ \App\Models\PageContent::text('servizi', 'service5_tag') }}</span>
            </div>
            <div class="service-card">
                <div class="service-icon">🎓</div>
                <h3>{{ \App\Models\PageContent::text('servizi', 'service6_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('servizi', 'service6_text') }}</p>
                <span class="service-tag">{{ \App\Models\PageContent::text('servizi', 'service6_tag') }}</span>
            </div>
        </div>
    </div>
</section>

{{-- PER CHI --}}
<section class="sec sec-bg">
    <div class="c">
        <div class="sec-header center">
            <div class="section-label" style="justify-content:center;">{{ \App\Models\PageContent::text('servizi', 'target_label') }}</div>
            <h2 class="sec-heading">{!! \App\Models\PageContent::html('servizi', 'target_title') !!}</h2>
        </div>
        <div class="target-grid">
            <div class="target-card">
                <div class="target-icon">🧒</div>
                <h3>{{ \App\Models\PageContent::text('servizi', 'target1_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('servizi', 'target1_text') }}</p>
            </div>
            <div class="target-card">
                <div class="target-icon">🎓</div>
                <h3>{{ \App\Models\PageContent::text('servizi', 'target2_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('servizi', 'target2_text') }}</p>
            </div>
            <div class="target-card">
                <div class="target-icon">💼</div>
                <h3>{{ \App\Models\PageContent::text('servizi', 'target3_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('servizi', 'target3_text') }}</p>
            </div>
            <div class="target-card">
                <div class="target-icon">🌍</div>
                <h3>{{ \App\Models\PageContent::text('servizi', 'target4_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('servizi', 'target4_text') }}</p>
            </div>
        </div>
    </div>
</section>

{{-- ORARI --}}
<section class="orari-band">
    <div class="c">
        <div class="orari-grid">
            <div>
                <div class="section-label white">{{ \App\Models\PageContent::text('servizi', 'orari_label') }}</div>
                <h2 class="orari-title">{{ \App\Models\PageContent::text('servizi', 'orari_title') }}</h2>
                <p class="orari-subtitle">{{ \App\Models\PageContent::text('servizi', 'orari_subtitle') }}</p>
                <table class="orari-table">
                    <tr><td>Lunedì – Venerdì</td><td>10:00 – 19:00</td></tr>
                    <tr><td>Sabato</td><td>9:00 – 13:00</td></tr>
                    <tr><td>Domenica</td><td>Chiuso</td></tr>
                </table>
                <div class="orari-note">
                    {!! \App\Models\PageContent::html('servizi', 'orari_note') !!}
                </div>
            </div>
            <div class="orari-visual">
                <img src="{{ \App\Models\PageContent::image('servizi', 'orari_image') }}"
                     alt="Ambiente di apprendimento A&A Language Center" loading="lazy">
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="cta-band">
    <div class="c cta-band-inner">
        <div class="label">{{ \App\Models\PageContent::text('servizi', 'cta_label') }}</div>
        <h2>{{ \App\Models\PageContent::text('servizi', 'cta_title') }}</h2>
        <p>{{ \App\Models\PageContent::text('servizi', 'cta_text') }}</p>
        <div class="cta-actions">
            <a href="{{ route('iscrizione') }}" class="btn-gold">{{ \App\Models\PageContent::text('servizi', 'cta_btn1') }}</a>
            <a href="{{ route('contattaci') }}" class="btn-outline-white">{{ \App\Models\PageContent::text('servizi', 'cta_btn2') }}</a>
        </div>
    </div>
</section>

@endsection
