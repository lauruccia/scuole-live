@extends('public.layout')

@section('title', \App\Models\PageContent::text('le-certificazioni', 'meta_title'))
@section('description', \App\Models\PageContent::text('le-certificazioni', 'meta_description'))
@section('keywords', \App\Models\PageContent::text('le-certificazioni', 'meta_keywords'))

@section('breadcrumb-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ route('home') }}" },
        { "@@type": "ListItem", "position": 2, "name": "Le Certificazioni", "item": "{{ route('le-certificazioni') }}" }
    ]
}
</script>
@endsection

@push('styles')
<style>
/* ── PAGINA: LE CERTIFICAZIONI ──────────────── */
.cert-hero {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
    color: #fff; padding: 80px 0 60px; text-align: center;
}
.cert-hero .eyebrow {
    display: inline-block; font-size: .72rem; font-weight: 700; letter-spacing: .14em;
    text-transform: uppercase; color: var(--gold); margin-bottom: 14px;
}
.cert-hero h1 { font-size: clamp(1.9rem, 4vw, 2.7rem); font-weight: 800; margin-bottom: 14px; }
.cert-hero p { color: rgba(255,255,255,.75); max-width: 640px; margin: 0 auto; }
.cert-hero-logo { margin: 26px auto 0; max-width: 200px; }
.cert-hero-logo img { width: 100%; background: #fff; border-radius: 12px; padding: 16px 20px; }

.cert-sec { padding: 64px 0; }
.cert-sec.alt { background: var(--bg); }
.cert-sec h2 { font-size: clamp(1.4rem, 2.6vw, 1.85rem); font-weight: 800; margin-bottom: 18px; }
.cert-sec h2 em { font-style: normal; color: var(--blue); }
.cert-narrow { max-width: 780px; margin: 0 auto; }
.cert-narrow p { margin-bottom: 16px; color: var(--text); line-height: 1.75; }
.cert-narrow a { color: var(--blue); text-decoration: underline; }

.cert-highlight {
    background: var(--gold-l); border: 1.5px solid var(--gold);
    border-radius: var(--radius-lg); padding: 26px 28px; margin: 26px 0;
    font-size: 1.02rem; line-height: 1.7;
}
.cert-highlight strong { color: var(--navy); }

.cert-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 34px; }
@media (max-width: 760px) { .cert-cards { grid-template-columns: 1fr; } }
.cert-card {
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: var(--radius-lg); padding: 30px 28px;
}
.cert-card .badge {
    display: inline-block; font-size: .68rem; font-weight: 700; letter-spacing: .08em;
    text-transform: uppercase; padding: 4px 12px; border-radius: 999px;
    background: var(--blue-l); color: var(--blue); margin-bottom: 14px;
}
.cert-card h3 { font-size: 1.2rem; font-weight: 800; margin-bottom: 10px; }
.cert-card p { color: var(--muted); font-size: .93rem; line-height: 1.7; margin-bottom: 12px; }
.cert-card a { color: var(--blue); font-weight: 600; font-size: .9rem; }

.cert-uses { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 30px; }
@media (max-width: 760px) { .cert-uses { grid-template-columns: 1fr; } }
.cert-use {
    background: var(--white); border: 1.5px solid var(--border); border-radius: var(--radius);
    padding: 22px 20px; text-align: center;
}
.cert-use .icon { font-size: 1.8rem; margin-bottom: 10px; display: block; }
.cert-use strong { display: block; margin-bottom: 6px; }
.cert-use span { font-size: .85rem; color: var(--muted); }

.cert-cta {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
    color: #fff; padding: 64px 0; text-align: center;
}
.cert-cta h2 { font-size: clamp(1.4rem, 3vw, 2rem); font-weight: 800; margin-bottom: 12px; }
.cert-cta p { color: rgba(255,255,255,.7); margin-bottom: 28px; }
.cert-cta .btn-hero-primary {
    display: inline-flex; align-items: center; gap: 8px;
    background: var(--gold); color: var(--navy);
    font-weight: 800; font-size: .9rem; letter-spacing: .04em;
    padding: 15px 34px; border-radius: 999px;
    transition: transform .2s, box-shadow .2s;
}
.cert-cta .btn-hero-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(201,164,44,.35); }
</style>
@endpush

@section('content')

{{-- HERO --}}
<section class="cert-hero">
    <div class="c">
        <span class="eyebrow">{{ \App\Models\PageContent::text('le-certificazioni', 'hero_eyebrow') }}</span>
        <h1>{{ \App\Models\PageContent::text('le-certificazioni', 'hero_title') }}</h1>
        <p>{!! \App\Models\PageContent::html('le-certificazioni', 'hero_text') !!}</p>
        <div class="cert-hero-logo">
            <img src="{{ asset('images/cert-trinity.png') }}" alt="Trinity College London — Registered Exam Centre 8241">
        </div>
    </div>
</section>

{{-- SEDE ESAMI --}}
<section class="cert-sec">
    <div class="c cert-narrow">
        <h2>{!! \App\Models\PageContent::html('le-certificazioni', 'sede_title') !!}</h2>
        {!! \App\Models\PageContent::html('le-certificazioni', 'sede_text') !!}
        <div class="cert-highlight">
            {!! \App\Models\PageContent::html('le-certificazioni', 'sede_highlight') !!}
        </div>
        <p>{!! \App\Models\PageContent::html('le-certificazioni', 'sede_text2') !!}</p>
    </div>
</section>

{{-- RICONOSCIMENTO MIUR --}}
<section class="cert-sec alt">
    <div class="c cert-narrow">
        <h2>{!! \App\Models\PageContent::html('le-certificazioni', 'miur_title') !!}</h2>
        {!! \App\Models\PageContent::html('le-certificazioni', 'miur_text') !!}
    </div>
</section>

{{-- GESE / ISE --}}
<section class="cert-sec">
    <div class="c">
        <div class="cert-narrow">
            <h2>{!! \App\Models\PageContent::html('le-certificazioni', 'esami_title') !!}</h2>
            <p>{!! \App\Models\PageContent::html('le-certificazioni', 'esami_intro') !!}</p>
        </div>
        <div class="cert-cards">
            <div class="cert-card">
                <span class="badge">{{ \App\Models\PageContent::text('le-certificazioni', 'ise_badge') }}</span>
                <h3>{{ \App\Models\PageContent::text('le-certificazioni', 'ise_title') }}</h3>
                <p>{!! \App\Models\PageContent::html('le-certificazioni', 'ise_text') !!}</p>
                <a href="https://www.trinitycollege.it/inglese/integrated-skills-in-english" target="_blank" rel="noopener">Scopri la certificazione ISE →</a>
            </div>
            <div class="cert-card">
                <span class="badge">{!! \App\Models\PageContent::html('le-certificazioni', 'gese_badge') !!}</span>
                <h3>{{ \App\Models\PageContent::text('le-certificazioni', 'gese_title') }}</h3>
                <p>{!! \App\Models\PageContent::html('le-certificazioni', 'gese_text') !!}</p>
                <a href="https://www.trinitycollege.it/riconoscimenti/" target="_blank" rel="noopener">Vedi i riconoscimenti GESE →</a>
            </div>
        </div>
    </div>
</section>

{{-- CAMBRIDGE PREPARATION CENTRE + ALTRE CERTIFICAZIONI --}}
<section class="cert-sec alt">
    <div class="c cert-narrow">
        <h2>{!! \App\Models\PageContent::html('le-certificazioni', 'cambridge_title') !!}</h2>
        {!! \App\Models\PageContent::html('le-certificazioni', 'cambridge_text') !!}
        <div class="cert-highlight">
            {!! \App\Models\PageContent::html('le-certificazioni', 'cambridge_highlight') !!}
        </div>
    </div>
</section>

{{-- ALTRE LINGUE: DELF/DALF, DELE, CILS, GOETHE + IELTS --}}
<section class="cert-sec">
    <div class="c cert-narrow">
        <h2>{!! \App\Models\PageContent::html('le-certificazioni', 'altre_title') !!}</h2>
        <p>{!! \App\Models\PageContent::html('le-certificazioni', 'altre_intro') !!}</p>
        <div class="cert-cards">
            <div class="cert-card">
                <span class="badge">{{ \App\Models\PageContent::text('le-certificazioni', 'delf_badge') }}</span>
                <h3>{{ \App\Models\PageContent::text('le-certificazioni', 'delf_title') }}</h3>
                <p>{!! \App\Models\PageContent::html('le-certificazioni', 'delf_text') !!}</p>
            </div>
            <div class="cert-card">
                <span class="badge">{{ \App\Models\PageContent::text('le-certificazioni', 'dele_badge') }}</span>
                <h3>{{ \App\Models\PageContent::text('le-certificazioni', 'dele_title') }}</h3>
                <p>{!! \App\Models\PageContent::html('le-certificazioni', 'dele_text') !!}</p>
            </div>
            <div class="cert-card">
                <span class="badge">{{ \App\Models\PageContent::text('le-certificazioni', 'cils_badge') }}</span>
                <h3>{{ \App\Models\PageContent::text('le-certificazioni', 'cils_title') }}</h3>
                <p>{!! \App\Models\PageContent::html('le-certificazioni', 'cils_text') !!}</p>
            </div>
            <div class="cert-card">
                <span class="badge">{{ \App\Models\PageContent::text('le-certificazioni', 'goethe_badge') }}</span>
                <h3>{{ \App\Models\PageContent::text('le-certificazioni', 'goethe_title') }}</h3>
                <p>{!! \App\Models\PageContent::html('le-certificazioni', 'goethe_text') !!}</p>
            </div>
        </div>
    </div>
</section>

{{-- CORSI IELTS --}}
<section class="cert-sec alt">
    <div class="c cert-narrow">
        <h2>{!! \App\Models\PageContent::html('le-certificazioni', 'ielts_title') !!}</h2>
        <p>{!! \App\Models\PageContent::html('le-certificazioni', 'ielts_text') !!}</p>
        <div class="cert-highlight">
            {!! \App\Models\PageContent::html('le-certificazioni', 'ielts_prezzi') !!}
        </div>
    </div>
</section>

{{-- A COSA SERVONO --}}
<section class="cert-sec">
    <div class="c">
        <div class="cert-narrow" style="text-align:center;">
            <h2>{{ \App\Models\PageContent::text('le-certificazioni', 'uses_title') }}</h2>
        </div>
        <div class="cert-uses">
            <div class="cert-use">
                <span class="icon">🏫</span>
                <strong>{{ \App\Models\PageContent::text('le-certificazioni', 'use1_title') }}</strong>
                <span>{{ \App\Models\PageContent::text('le-certificazioni', 'use1_text') }}</span>
            </div>
            <div class="cert-use">
                <span class="icon">🎓</span>
                <strong>{{ \App\Models\PageContent::text('le-certificazioni', 'use2_title') }}</strong>
                <span>{{ \App\Models\PageContent::text('le-certificazioni', 'use2_text') }}</span>
            </div>
            <div class="cert-use">
                <span class="icon">💼</span>
                <strong>{{ \App\Models\PageContent::text('le-certificazioni', 'use3_title') }}</strong>
                <span>{{ \App\Models\PageContent::text('le-certificazioni', 'use3_text') }}</span>
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="cert-cta">
    <div class="c">
        <h2>{!! \App\Models\PageContent::html('le-certificazioni', 'cta_title') !!}</h2>
        <p>{!! \App\Models\PageContent::html('le-certificazioni', 'cta_text') !!}</p>
        <a href="{{ route('contattaci') }}" class="btn-hero-primary">{{ \App\Models\PageContent::text('le-certificazioni', 'cta_button') }}</a>
    </div>
</section>

@endsection
