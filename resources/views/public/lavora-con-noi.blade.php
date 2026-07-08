@extends('public.layout')

@section('title', \App\Models\PageContent::text('lavora-con-noi', 'meta_title'))
@section('description', \App\Models\PageContent::text('lavora-con-noi', 'meta_description'))
@section('keywords', 'lavoro insegnante lingue Roma, cercasi docente madrelingua Roma, lavorare scuola di lingue Roma, candidatura insegnante lingue Roma, lavoro docente inglese Roma')

@section('breadcrumb-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ route('home') }}" },
        { "@@type": "ListItem", "position": 2, "name": "Lavora con Noi", "item": "{{ route('lavora-con-noi') }}" }
    ]
}
</script>
@endsection

@push('styles')
<style>
    /* NB: la navbar e il container .c sono gestiti dal layout globale
       public.layout. Le precedenti regole nav/.nav-brand/.nav-links con
       !important rompevano l'header — sono state rimosse. */

    :root {
        --blue:      #0057d9;
        --blue-dark: #001b3f;
        --blue-deep: #001126;
        --light:     #f6faff;
        --text:      #06152f;
        --muted:     #526173;
        --yellow:    #ffd800;
        --border:    #dbe7f4;
    }

    .page-hero {
        background: linear-gradient(135deg, #001733 0%, #0045a7 100%);
        color: #fff;
        padding: 52px 0 44px;
        text-align: center;
    }
    .page-hero h1 { font-size: 42px; margin: 0 0 10px; letter-spacing: -1px; font-weight: 900; }
    .page-hero p  { font-size: 15px; color: rgba(255,255,255,.85); margin: 0; }
    .breadcrumb   { font-size: 13px; color: rgba(255,255,255,.6); margin-bottom: 14px; }
    .breadcrumb a { color: rgba(255,255,255,.7); text-decoration: none; }

    .sec { padding: 58px 0; }
    .sec-light { background: var(--light); }

    .sec-title h2 { font-size: 30px; margin: 0 0 18px; letter-spacing: -.8px; color: #001126; }
    .sec-title::after {
        content: "";
        width: 28px; height: 3px;
        background: var(--blue);
        display: block;
        margin: 8px 0 0;
        border-radius: 10px;
    }

    /* INTRO GRID */
    .intro-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 52px;
        align-items: center;
    }
    .intro-text p { font-size: 15px; line-height: 1.75; color: #2d3a4d; margin-bottom: 16px; }
    .intro-photo {
        border-radius: 14px;
        overflow: hidden;
        height: 360px;
    }
    .intro-photo img { width: 100%; height: 100%; object-fit: cover; }

    /* REQUISITI */
    .req-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
        margin-top: 28px;
    }
    .req-item {
        display: flex;
        gap: 14px;
        align-items: flex-start;
        padding: 20px 18px;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 12px;
        transition: .22s;
    }
    .req-item:hover { box-shadow: 0 8px 24px rgba(0,37,91,.08); }
    .req-check {
        width: 28px; height: 28px;
        border-radius: 50%;
        background: var(--blue);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 13px;
        font-weight: 900;
        margin-top: 2px;
    }
    .req-item h4 { font-size: 14px; margin-bottom: 4px; color: var(--blue-deep); font-weight: 800; }
    .req-item p { font-size: 12px; line-height: 1.6; color: #526173; margin: 0; }

    /* OFFERTA */
    .offer-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-top: 32px;
    }
    .offer-card {
        text-align: center;
        padding: 28px 18px;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 14px;
        transition: .22s;
    }
    .offer-card:hover { box-shadow: 0 12px 32px rgba(0,37,91,.1); transform: translateY(-4px); }
    .offer-icon { font-size: 36px; margin-bottom: 14px; }
    .offer-card h3 { font-size: 14px; font-weight: 800; margin-bottom: 7px; color: var(--blue-deep); }
    .offer-card p { font-size: 12px; color: #526173; line-height: 1.55; margin: 0; }

    /* SEDE */
    .sede-band {
        background: linear-gradient(135deg, #001733 0%, #003d94 100%);
        color: #fff;
        padding: 52px 0;
    }
    .sede-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 52px;
        align-items: center;
    }
    .sede-title { font-size: 28px; font-weight: 900; margin: 0 0 14px; letter-spacing: -.6px; }
    .sede-text p { font-size: 14px; color: rgba(255,255,255,.85); line-height: 1.75; margin-bottom: 14px; }
    .sede-details { display: flex; flex-direction: column; gap: 12px; margin-top: 20px; }
    .sede-detail { display: flex; gap: 12px; align-items: flex-start; }
    .sede-detail-icon { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
    .sede-detail p { font-size: 13px; color: rgba(255,255,255,.85); margin: 0; line-height: 1.5; }
    .sede-photo {
        border-radius: 14px;
        overflow: hidden;
        height: 320px;
    }
    .sede-photo img { width: 100%; height: 100%; object-fit: cover; opacity: .85; }

    /* CANDIDATURA */
    .candidatura-box {
        max-width: 680px;
        margin: 0 auto;
        text-align: center;
    }
    .candidatura-box p { font-size: 15px; color: #2d3a4d; line-height: 1.75; margin-bottom: 28px; }
    .candidatura-actions { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }
    .btn-cta {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 50px;
        padding: 0 32px;
        border-radius: 7px;
        font-size: 14px;
        font-weight: 900;
        transition: .22s;
        text-decoration: none;
        cursor: pointer;
    }
    .btn-primary-blue { background: var(--blue); color: #fff; border: 2px solid var(--blue); }
    .btn-primary-blue:hover { background: #0049c0; transform: translateY(-2px); }
    .btn-instagram {
        background: linear-gradient(135deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
        color: #fff;
        border: none;
    }
    .btn-instagram:hover { transform: translateY(-2px); opacity: .9; }

    footer {
        background: #001126 !important;
        color: #fff !important;
        border-top: 1px solid rgba(255,255,255,.12);
        padding: 18px 0 !important;
        font-size: 13px !important;
        margin-top: 0 !important;
    }

    @media (max-width: 900px) {
        .intro-grid { grid-template-columns: 1fr; gap: 28px; }
        .intro-photo { height: 240px; }
        .req-grid { grid-template-columns: 1fr; }
        .offer-grid { grid-template-columns: repeat(2, 1fr); }
        .sede-grid { grid-template-columns: 1fr; gap: 28px; }
        .sede-photo { height: 220px; }
    }
    @media (max-width: 640px) {
        .page-hero h1 { font-size: 28px; }
        .offer-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')

{{-- ── PAGE HERO ── --}}
<section class="page-hero">
    <div class="c">
        <div class="breadcrumb"><a href="{{ route('home') }}">Home</a> › Lavora con Noi</div>
        <h1>{{ \App\Models\PageContent::text('lavora-con-noi', 'hero_title') }}</h1>
        <p>{{ \App\Models\PageContent::text('lavora-con-noi', 'hero_subtitle') }}</p>
    </div>
</section>

{{-- ── INTRO ── --}}
<section class="sec">
    <div class="c">
        <div class="intro-grid">
            <div class="intro-text">
                <div class="sec-title"><h2>{{ \App\Models\PageContent::text('lavora-con-noi', 'intro_title') }}</h2></div>
                {!! \App\Models\PageContent::html('lavora-con-noi', 'intro_text') !!}
            </div>
            <div class="intro-photo">
                <img src="{{ \App\Models\PageContent::image('lavora-con-noi', 'intro_image') }}" alt="Docenti A&A Language Center">
            </div>
        </div>
    </div>
</section>

{{-- ── COSA CERCHIAMO ── --}}
<section class="sec sec-light">
    <div class="c">
        <div class="sec-title"><h2>{{ \App\Models\PageContent::text('lavora-con-noi', 'req_title') }}</h2></div>
        <p style="font-size:15px;color:#2d3a4d;max-width:700px;line-height:1.75;margin-bottom:0;">{{ \App\Models\PageContent::text('lavora-con-noi', 'req_intro') }}</p>
        <div class="req-grid">
            <div class="req-item">
                <div class="req-check">✓</div>
                <div>
                    <h4>{{ \App\Models\PageContent::text('lavora-con-noi', 'req1_title') }}</h4>
                    <p>{{ \App\Models\PageContent::text('lavora-con-noi', 'req1_text') }}</p>
                </div>
            </div>
            <div class="req-item">
                <div class="req-check">✓</div>
                <div>
                    <h4>{{ \App\Models\PageContent::text('lavora-con-noi', 'req2_title') }}</h4>
                    <p>{{ \App\Models\PageContent::text('lavora-con-noi', 'req2_text') }}</p>
                </div>
            </div>
            <div class="req-item">
                <div class="req-check">✓</div>
                <div>
                    <h4>{{ \App\Models\PageContent::text('lavora-con-noi', 'req3_title') }}</h4>
                    <p>{{ \App\Models\PageContent::text('lavora-con-noi', 'req3_text') }}</p>
                </div>
            </div>
            <div class="req-item">
                <div class="req-check">✓</div>
                <div>
                    <h4>{{ \App\Models\PageContent::text('lavora-con-noi', 'req4_title') }}</h4>
                    <p>{{ \App\Models\PageContent::text('lavora-con-noi', 'req4_text') }}</p>
                </div>
            </div>
            <div class="req-item">
                <div class="req-check">✓</div>
                <div>
                    <h4>{{ \App\Models\PageContent::text('lavora-con-noi', 'req5_title') }}</h4>
                    <p>{{ \App\Models\PageContent::text('lavora-con-noi', 'req5_text') }}</p>
                </div>
            </div>
            <div class="req-item">
                <div class="req-check">+</div>
                <div>
                    <h4>{{ \App\Models\PageContent::text('lavora-con-noi', 'req6_title') }}</h4>
                    <p>{{ \App\Models\PageContent::text('lavora-con-noi', 'req6_text') }}</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── COSA OFFRIAMO ── --}}
<section class="sec">
    <div class="c">
        <div class="sec-title"><h2>{{ \App\Models\PageContent::text('lavora-con-noi', 'offer_title') }}</h2></div>
        <div class="offer-grid">
            <div class="offer-card">
                <div class="offer-icon">🤝</div>
                <h3>{{ \App\Models\PageContent::text('lavora-con-noi', 'offer1_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('lavora-con-noi', 'offer1_text') }}</p>
            </div>
            <div class="offer-card">
                <div class="offer-icon">⏰</div>
                <h3>{{ \App\Models\PageContent::text('lavora-con-noi', 'offer2_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('lavora-con-noi', 'offer2_text') }}</p>
            </div>
            <div class="offer-card">
                <div class="offer-icon">📚</div>
                <h3>{{ \App\Models\PageContent::text('lavora-con-noi', 'offer3_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('lavora-con-noi', 'offer3_text') }}</p>
            </div>
            <div class="offer-card">
                <div class="offer-icon">🌍</div>
                <h3>{{ \App\Models\PageContent::text('lavora-con-noi', 'offer4_title') }}</h3>
                <p>{{ \App\Models\PageContent::text('lavora-con-noi', 'offer4_text') }}</p>
            </div>
        </div>
    </div>
</section>

{{-- ── SEDE ── --}}
<section class="sede-band">
    <div class="c">
        <div class="sede-grid">
            <div class="sede-text">
                <h2 class="sede-title">{{ \App\Models\PageContent::text('lavora-con-noi', 'sede_title') }}</h2>
                {!! \App\Models\PageContent::html('lavora-con-noi', 'sede_text') !!}
                <div class="sede-details">
                    <div class="sede-detail">
                        <div class="sede-detail-icon">📍</div>
                        <p>Viale Leonardo da Vinci, 193 — 00145 Roma</p>
                    </div>
                    <div class="sede-detail">
                        <div class="sede-detail-icon">🚇</div>
                        <p>Metro B — Fermata San Paolo (5 minuti a piedi)<br>Metro B — Fermata Marconi (10 minuti a piedi)</p>
                    </div>
                    <div class="sede-detail">
                        <div class="sede-detail-icon">🚌</div>
                        <p>Diverse linee di autobus con fermata nelle immediate vicinanze</p>
                    </div>
                </div>
            </div>
            <div class="sede-photo">
                <img src="{{ \App\Models\PageContent::image('lavora-con-noi', 'sede_image') }}" alt="Quartiere San Paolo Roma">
            </div>
        </div>
    </div>
</section>

{{-- ── CANDIDATURA ── --}}
<section class="sec">
    <div class="c">
        <div class="sec-title" style="text-align:center;">
            <h2 style="text-align:center;">{{ \App\Models\PageContent::text('lavora-con-noi', 'cand_title') }}</h2>
            <div style="margin:0 auto;"></div>
        </div>
        <div class="candidatura-box">
            <p>{{ \App\Models\PageContent::text('lavora-con-noi', 'cand_text') }}</p>
            <div class="candidatura-actions">
                <a href="mailto:direzione@aealanguagecenter.it" class="btn-cta btn-primary-blue">
                    📧 Invia la tua candidatura
                </a>
                <a href="https://www.instagram.com/aealanguagecenter/" target="_blank" rel="noopener" class="btn-cta btn-instagram">
                    📷 Seguici su Instagram
                </a>
            </div>
            <p style="margin-top:24px;font-size:13px;color:#526173;">
                {!! \App\Models\PageContent::html('lavora-con-noi', 'cand_note') !!}
            </p>
        </div>
    </div>
</section>

@endsection
