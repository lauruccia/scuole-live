@extends('public.layout')

@section('title', 'News ed Eventi — A&A Language Center Roma')
@section('description', 'Le ultime notizie, gli eventi e le comunicazioni di A&A Language Center: sessioni d\'esame Trinity, aperture iscrizioni, novità sui corsi di lingue a Roma.')
@section('keywords', 'news scuola di lingue Roma, eventi A&A Language Center, sessioni esami Trinity Roma, novità corsi di lingue Roma')

@section('breadcrumb-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ route('home') }}" },
        { "@@type": "ListItem", "position": 2, "name": "News ed Eventi", "item": "{{ route('news.index') }}" }
    ]
}
</script>
@endsection

@push('styles')
<style>
/* ── PAGINA: NEWS INDEX ─────────────────────── */
.news-hero {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
    color: #fff; padding: 72px 0 56px; text-align: center;
}
.news-hero h1 { font-size: clamp(1.9rem, 4vw, 2.6rem); font-weight: 800; margin-bottom: 12px; }
.news-hero p  { color: rgba(255,255,255,.75); max-width: 560px; margin: 0 auto; }

.news-grid {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 26px;
    padding: 56px 0 24px;
}
@media (max-width: 900px) { .news-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 600px) { .news-grid { grid-template-columns: 1fr; } }

.news-card {
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: var(--radius-lg); overflow: hidden; display: flex; flex-direction: column;
    transition: transform .25s, box-shadow .25s;
}
.news-card:hover { transform: translateY(-5px); box-shadow: var(--shadow); }
.news-card-img { aspect-ratio: 16 / 9; background: var(--blue-l); overflow: hidden; }
.news-card-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
.news-card-body { padding: 22px 22px 24px; display: flex; flex-direction: column; gap: 10px; flex: 1; }
.news-badge {
    align-self: flex-start; font-size: .68rem; font-weight: 700; letter-spacing: .06em;
    text-transform: uppercase; padding: 4px 10px; border-radius: 999px;
    background: var(--blue-l); color: var(--blue);
}
.news-badge.evento { background: var(--gold-l); color: var(--gold-d); }
.news-card h2 { font-size: 1.08rem; font-weight: 700; line-height: 1.35; }
.news-card h2 a:hover { color: var(--blue); }
.news-card-date { font-size: .78rem; color: var(--muted); }
.news-card-excerpt { font-size: .88rem; color: var(--muted); flex: 1; }
.news-card-more { font-size: .85rem; font-weight: 600; color: var(--blue); }

.news-empty { text-align: center; padding: 90px 0; color: var(--muted); }
.news-pagination { padding: 12px 0 64px; display: flex; justify-content: center; gap: 14px; align-items: center; }
.news-pagination a, .news-pagination span {
    padding: 10px 22px; border-radius: 999px; font-size: .88rem; font-weight: 600;
    border: 1.5px solid var(--border); color: var(--text);
}
.news-pagination a:hover { border-color: var(--blue); color: var(--blue); }
.news-pagination span.disabled { opacity: .4; }
.news-pagination .page-info { border: none; color: var(--muted); font-weight: 500; }
</style>
@endpush

@section('content')

<section class="news-hero">
    <div class="c">
        <h1>News ed Eventi</h1>
        <p>Le ultime notizie dalla scuola: sessioni d'esame, aperture iscrizioni, eventi e comunicazioni per studenti e famiglie.</p>
    </div>
</section>

<div class="c">
    @if($posts->isEmpty())
        <div class="news-empty">
            <p>Nessuna notizia pubblicata al momento. Torna a trovarci presto!</p>
        </div>
    @else
        <div class="news-grid">
            @foreach($posts as $post)
                <article class="news-card">
                    @if($post->cover_image)
                        <a href="{{ route('news.show', $post->slug) }}" class="news-card-img" aria-hidden="true" tabindex="-1">
                            <img src="{{ asset('storage/' . $post->cover_image) }}" alt="{{ $post->title }}" loading="lazy">
                        </a>
                    @endif
                    <div class="news-card-body">
                        <span class="news-badge {{ $post->type }}">{{ $post->type_label }}</span>
                        <h2><a href="{{ route('news.show', $post->slug) }}">{{ $post->title }}</a></h2>
                        <span class="news-card-date">
                            {{ optional($post->published_at)->translatedFormat('d F Y') }}
                            @if($post->type === 'evento' && $post->event_date)
                                · Evento: {{ $post->event_date->translatedFormat('d F Y') }}
                            @endif
                        </span>
                        <p class="news-card-excerpt">
                            {{ \Illuminate\Support\Str::limit($post->excerpt ?: strip_tags($post->body), 140) }}
                        </p>
                        <a class="news-card-more" href="{{ route('news.show', $post->slug) }}">Leggi tutto →</a>
                    </div>
                </article>
            @endforeach
        </div>

        @if($posts->hasPages())
            <nav class="news-pagination" aria-label="Paginazione news">
                @if($posts->onFirstPage())
                    <span class="disabled">← Più recenti</span>
                @else
                    <a href="{{ $posts->previousPageUrl() }}" rel="prev">← Più recenti</a>
                @endif

                <span class="page-info">Pagina {{ $posts->currentPage() }} di {{ $posts->lastPage() }}</span>

                @if($posts->hasMorePages())
                    <a href="{{ $posts->nextPageUrl() }}" rel="next">Meno recenti →</a>
                @else
                    <span class="disabled">Meno recenti →</span>
                @endif
            </nav>
        @endif
    @endif
</div>

@endsection
