@extends('public.layout')

{{--
    Test sul livello di lingua — pagina indice (/test-sul-livello-di-lingua).
    Ricrea (stesso slug) la pagina hub del vecchio sito WordPress e linka i
    4 test per lingua. Testi editabili dal pannello Contenuti sito
    (pagina "test-livello", sezione hub).
--}}

@section('title', \App\Models\PageContent::text('test-livello', 'meta_title'))
@section('description', \App\Models\PageContent::text('test-livello', 'meta_description'))
@section('keywords', 'test di livello lingua, test lingua online gratuito, test di inglese, test di francese, test di spagnolo, test di italiano per stranieri, test livello CEFR, entrance test scuola di lingue Roma')
@section('og-image-alt', 'Test sul livello di lingua online gratuito — A&A Language Center Roma')

@section('breadcrumb-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ route('home') }}" },
        { "@@type": "ListItem", "position": 2, "name": "Test sul livello di lingua", "item": "{{ route('test.livello') }}" }
    ]
}
</script>
@endsection

@push('styles')
<style>
/* ── PAGINA: TEST DI LIVELLO (HUB) ─────────────────── */
.tests-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }
.test-card {
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: var(--radius-lg); padding: 30px 24px; text-align: center;
    transition: transform .25s, box-shadow .25s, border-color .25s;
    display: block;
}
.test-card:hover { transform: translateY(-4px); border-color: var(--blue); box-shadow: var(--shadow); }
.test-card .flag { font-size: 2.4rem; display: block; margin-bottom: 12px; }
.test-card h3 { font-size: 1.05rem; font-weight: 800; color: var(--navy); margin-bottom: 8px; }
.test-card p { font-size: .84rem; color: var(--muted); line-height: 1.55; margin-bottom: 14px; }
.test-card .go { font-size: .85rem; font-weight: 700; color: var(--blue); }
@media (max-width: 980px) { .tests-grid { grid-template-columns: 1fr 1fr; } }
@media (max-width: 560px) { .tests-grid { grid-template-columns: 1fr; } }

.entrance-box {
    max-width: 860px; margin: 0 auto;
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
    color: #fff; border-radius: var(--radius-lg); padding: 36px 34px;
}
.entrance-box h2 { font-size: 1.35rem; font-weight: 800; margin-bottom: 12px; }
.entrance-box h2 em { font-style: normal; color: var(--gold); }
.entrance-box p { font-size: .95rem; color: rgba(255,255,255,.8); line-height: 1.75; margin-bottom: 20px; }
</style>
@endpush

@section('content')

{{-- HERO --}}
<section class="page-hero">
    <div class="c page-hero-inner">
        <div class="breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span class="sep">›</span>
            <span>Test sul livello di lingua</span>
        </div>
        <h1>{!! \App\Models\PageContent::html('test-livello', 'hero_title') !!}</h1>
        <p class="subtitle">{{ \App\Models\PageContent::text('test-livello', 'hero_subtitle') }}</p>
    </div>
</section>

{{-- INTRO --}}
<section class="sec">
    <div class="c">
        <div class="sec-header center">
            <div class="section-label" style="justify-content:center;">Da dove parti?</div>
            <h2 class="sec-heading">Scegli la lingua e <em>fai il test</em></h2>
            <p class="sec-subtext" style="text-align:center;margin:0 auto;">{{ \App\Models\PageContent::text('test-livello', 'intro_text') }}</p>
        </div>

        <div class="tests-grid">
            @foreach($tests as $slug => $t)
                <a href="{{ route('test.lingua', $slug) }}" class="test-card">
                    <span class="flag">{{ $t['flag'] }}</span>
                    <h3>Test di {{ $t['name'] }}</h3>
                    <p>{{ count($t['questions']) }} domande a scelta multipla · risultato CEFR immediato · gratuito e senza registrazione</p>
                    <span class="go">Inizia il test →</span>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ENTRANCE TEST --}}
<section class="sec sec-bg">
    <div class="c">
        <div class="entrance-box">
            <h2>{{ \App\Models\PageContent::text('test-livello', 'entrance_title') }}</h2>
            <p>{{ \App\Models\PageContent::text('test-livello', 'entrance_text') }}</p>
            <div class="cta-actions">
                <a href="{{ route('iscrizione') }}" class="btn-gold">Prenota l'Entrance Test gratuito →</a>
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="cta-band">
    <div class="c cta-band-inner">
        <div class="label">{{ \App\Models\PageContent::text('test-livello', 'cta_label') }}</div>
        <h2>{{ \App\Models\PageContent::text('test-livello', 'cta_title') }}</h2>
        <p>{{ \App\Models\PageContent::text('test-livello', 'cta_text') }}</p>
        <div class="cta-actions">
            <a href="{{ route('iscrizione') }}" class="btn-gold">Prenota ora — è gratuito →</a>
            <a href="{{ route('checkout.catalogo') }}" class="btn-outline-white">Vedi tutti i corsi</a>
        </div>
    </div>
</section>

@endsection
