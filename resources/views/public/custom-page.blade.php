@extends('public.layout')

@section('title', ($page->meta_title ?: $page->title) . ' — A&A Language Center')
@section('description', $page->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($page->body), 155))

@section('breadcrumb-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ route('home') }}" },
        { "@@type": "ListItem", "position": 2, "name": "{{ str_replace('"', '\\"', $page->title) }}", "item": "{{ route('page.show', $page->slug) }}" }
    ]
}
</script>
@endsection

@push('styles')
<style>
/* ── PAGINA PERSONALIZZATA (page-builder pannello) ────────────── */
.cp-hero {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
    color: #fff; padding: 72px 0 56px;
}
.cp-hero .c { max-width: 820px; }
.cp-breadcrumb { font-size: .8rem; color: rgba(255,255,255,.6); margin-bottom: 18px; display: block; }
.cp-breadcrumb a { color: rgba(255,255,255,.8); }
.cp-breadcrumb a:hover { color: var(--gold); }
.cp-hero h1 { font-size: clamp(1.8rem, 3.8vw, 2.5rem); font-weight: 800; line-height: 1.25; margin-bottom: 14px; }
.cp-hero p { color: rgba(255,255,255,.75); font-size: 1.02rem; max-width: 680px; }
.cp-hero-img { margin-top: 30px; border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow); }
.cp-hero-img img { width: 100%; display: block; }

.cp-wrap { max-width: 820px; margin: 0 auto; padding: 48px 0 64px; }
.cp-body { font-size: 1.02rem; line-height: 1.75; color: var(--text); }
.cp-body h2 { font-size: 1.4rem; margin: 30px 0 12px; }
.cp-body h3 { font-size: 1.15rem; margin: 24px 0 10px; }
.cp-body p { margin-bottom: 16px; }
.cp-body ul, .cp-body ol { margin: 0 0 16px 22px; }
.cp-body a { color: var(--blue); text-decoration: underline; }
.cp-body img { max-width: 100%; height: auto; border-radius: var(--radius); margin: 18px 0; display: block; }
.cp-body blockquote {
    border-left: 3px solid var(--gold); padding: 6px 18px; margin: 18px 0;
    color: var(--muted); font-style: italic;
}

.cp-cta {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
    color: #fff; padding: 60px 0; text-align: center;
}
.cp-cta h2 { font-size: clamp(1.4rem, 3vw, 2rem); font-weight: 800; margin-bottom: 12px; }
.cp-cta p { color: rgba(255,255,255,.7); margin-bottom: 26px; max-width: 600px; margin-left: auto; margin-right: auto; }
.cp-cta .btn-hero-primary {
    display: inline-flex; align-items: center; gap: 8px;
    background: var(--gold); color: var(--navy);
    font-weight: 800; font-size: .9rem; letter-spacing: .04em;
    padding: 15px 34px; border-radius: 999px;
    transition: transform .2s, box-shadow .2s;
}
.cp-cta .btn-hero-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(201,164,44,.35); }
</style>
@endpush

@section('content')

<section class="cp-hero">
    <div class="c">
        <span class="cp-breadcrumb">
            <a href="{{ route('home') }}">Home</a> › {{ $page->title }}
        </span>
        <h1>{{ $page->hero_title ?: $page->title }}</h1>
        @if($page->hero_subtitle)
            <p>{{ $page->hero_subtitle }}</p>
        @endif
        @if($page->hero_image)
            <div class="cp-hero-img">
                <img src="{{ asset('storage/' . $page->hero_image) }}" alt="{{ $page->title }}">
            </div>
        @endif
    </div>
</section>

<div class="c">
    <article class="cp-wrap">
        <div class="cp-body">
            {!! $page->body !!}
        </div>
    </article>
</div>

@if($page->cta_enabled)
<section class="cp-cta">
    <div class="c">
        @if($page->cta_title)<h2>{{ $page->cta_title }}</h2>@endif
        @if($page->cta_text)<p>{{ $page->cta_text }}</p>@endif
        @if($page->cta_button_label && $page->cta_button_url)
            <a href="{{ $page->cta_button_url }}" class="btn-hero-primary">{{ $page->cta_button_label }} →</a>
        @endif
    </div>
</section>
@endif

@endsection
