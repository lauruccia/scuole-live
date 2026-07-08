@extends('public.layout')

@section('title', $post->title . ' — News A&A Language Center')
@section('description', \Illuminate\Support\Str::limit($post->excerpt ?: strip_tags($post->body), 155))

@section('breadcrumb-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ route('home') }}" },
        { "@@type": "ListItem", "position": 2, "name": "News ed Eventi", "item": "{{ route('news.index') }}" },
        { "@@type": "ListItem", "position": 3, "name": "{{ str_replace('"', '\\"', $post->title) }}", "item": "{{ route('news.show', $post->slug) }}" }
    ]
}
</script>
@endsection

@section('extra-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "{{ $post->type === 'evento' ? 'Event' : 'NewsArticle' }}",
    "headline": "{{ str_replace('"', '\\"', $post->title) }}",
    @if($post->type === 'evento' && $post->event_date)
    "startDate": "{{ $post->event_date->toDateString() }}",
    "location": { "@@type": "Place", "name": "{{ str_replace('"', '\\"', $post->event_location ?: 'A&A Language Center, Roma') }}" },
    @endif
    @if($post->cover_image)
    "image": "{{ asset('storage/' . $post->cover_image) }}",
    @endif
    "datePublished": "{{ optional($post->published_at)->toIso8601String() }}",
    "name": "{{ str_replace('"', '\\"', $post->title) }}",
    "publisher": { "@@type": "Organization", "name": "A&A Language Center" }
}
</script>
@endsection

@push('styles')
<style>
/* ── PAGINA: NEWS DETTAGLIO ─────────────────── */
.post-hero {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
    color: #fff; padding: 64px 0 48px;
}
.post-hero .c { max-width: 800px; }
.post-breadcrumb { font-size: .8rem; color: rgba(255,255,255,.6); margin-bottom: 18px; display: block; }
.post-breadcrumb a { color: rgba(255,255,255,.8); }
.post-breadcrumb a:hover { color: var(--gold); }
.post-hero h1 { font-size: clamp(1.7rem, 3.6vw, 2.4rem); font-weight: 800; line-height: 1.25; margin-bottom: 14px; }
.post-meta { font-size: .85rem; color: rgba(255,255,255,.7); display: flex; flex-wrap: wrap; gap: 14px; align-items: center; }
.post-badge {
    font-size: .68rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
    padding: 4px 10px; border-radius: 999px; background: var(--gold); color: var(--navy);
}

.post-wrap { max-width: 800px; margin: 0 auto; padding: 44px 0 64px; }
.post-cover { border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 34px; box-shadow: var(--shadow); }
.post-cover img { width: 100%; display: block; }
.post-event-box {
    background: var(--gold-l); border: 1.5px solid var(--gold);
    border-radius: var(--radius); padding: 16px 20px; margin-bottom: 28px;
    font-size: .92rem;
}
.post-body { font-size: 1.02rem; line-height: 1.75; color: var(--text); }
.post-body h2 { font-size: 1.4rem; margin: 30px 0 12px; }
.post-body h3 { font-size: 1.15rem; margin: 24px 0 10px; }
.post-body p { margin-bottom: 16px; }
.post-body ul, .post-body ol { margin: 0 0 16px 22px; }
.post-body a { color: var(--blue); text-decoration: underline; }
.post-body blockquote {
    border-left: 3px solid var(--gold); padding: 6px 18px; margin: 18px 0;
    color: var(--muted); font-style: italic;
}
.post-back { display: inline-block; margin-top: 34px; font-weight: 600; color: var(--blue); }

.post-related { background: var(--bg); padding: 52px 0 64px; }
.post-related h2 { font-size: 1.3rem; font-weight: 800; margin-bottom: 24px; }
.related-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
@media (max-width: 780px) { .related-grid { grid-template-columns: 1fr; } }
.related-card {
    background: var(--white); border: 1.5px solid var(--border); border-radius: var(--radius);
    padding: 18px 20px; transition: transform .2s, box-shadow .2s;
}
.related-card:hover { transform: translateY(-3px); box-shadow: var(--shadow); }
.related-card h3 { font-size: .98rem; font-weight: 700; line-height: 1.35; margin-bottom: 6px; }
.related-card span { font-size: .78rem; color: var(--muted); }
</style>
@endpush

@section('content')

<section class="post-hero">
    <div class="c">
        <span class="post-breadcrumb">
            <a href="{{ route('home') }}">Home</a> › <a href="{{ route('news.index') }}">News ed Eventi</a>
        </span>
        <h1>{{ $post->title }}</h1>
        <div class="post-meta">
            <span class="post-badge">{{ $post->type_label }}</span>
            <span>{{ optional($post->published_at)->translatedFormat('d F Y') }}</span>
        </div>
    </div>
</section>

<div class="c">
    <article class="post-wrap">
        @if($post->cover_image)
            <div class="post-cover">
                <img src="{{ asset('storage/' . $post->cover_image) }}" alt="{{ $post->title }}">
            </div>
        @endif

        @if($post->type === 'evento' && ($post->event_date || $post->event_location))
            <div class="post-event-box">
                📅 <strong>Quando:</strong> {{ optional($post->event_date)->translatedFormat('l d F Y') ?? 'Data da definire' }}
                @if($post->event_location)
                    &nbsp;·&nbsp; 📍 <strong>Dove:</strong> {{ $post->event_location }}
                @endif
            </div>
        @endif

        <div class="post-body">
            {!! $post->body !!}
        </div>

        <a href="{{ route('news.index') }}" class="post-back">← Tutte le news</a>
    </article>
</div>

@if($related->isNotEmpty())
<section class="post-related">
    <div class="c">
        <h2>Potrebbe interessarti anche</h2>
        <div class="related-grid">
            @foreach($related as $rel)
                <a href="{{ route('news.show', $rel->slug) }}" class="related-card">
                    <h3>{{ $rel->title }}</h3>
                    <span>{{ $rel->type_label }} · {{ optional($rel->published_at)->translatedFormat('d F Y') }}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection
