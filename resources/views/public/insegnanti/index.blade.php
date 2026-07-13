@extends('public.layout')

@section('title', 'I Nostri Insegnanti — A&A Language Center Roma')
@section('description', 'Conosci il team di insegnanti qualificati di A&A Language Center: titoli di studio, certificazioni e metodo di insegnamento per ogni lingua.')
@section('keywords', 'insegnanti scuola di lingue Roma, docenti madrelingua Roma, team A&A Language Center')

@section('breadcrumb-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ route('home') }}" },
        { "@@type": "ListItem", "position": 2, "name": "Insegnanti", "item": "{{ route('insegnanti.index') }}" }
    ]
}
</script>
@endsection

@push('styles')
<style>
/* ── PAGINA: INSEGNANTI INDEX ─────────────────────── */
.teachers-hero {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
    color: #fff; padding: 72px 0 56px; text-align: center;
}
.teachers-hero h1 { font-size: clamp(1.9rem, 4vw, 2.6rem); font-weight: 800; margin-bottom: 12px; }
.teachers-hero p  { color: rgba(255,255,255,.75); max-width: 620px; margin: 0 auto; }

.teachers-grid {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 26px;
    padding: 56px 0 72px;
}
@media (max-width: 900px) { .teachers-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 600px) { .teachers-grid { grid-template-columns: 1fr; } }

.teacher-card {
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: var(--radius-lg); overflow: hidden; text-align: center;
    padding: 32px 24px; transition: transform .25s, box-shadow .25s;
}
.teacher-card:hover { transform: translateY(-5px); box-shadow: var(--shadow); }
.teacher-avatar {
    width: 92px; height: 92px; border-radius: 50%; overflow: hidden;
    margin: 0 auto 18px; background: var(--blue-l);
    display: flex; align-items: center; justify-content: center; font-size: 2.2rem;
}
.teacher-avatar img { width: 100%; height: 100%; object-fit: cover; }
.teacher-card h2 { font-size: 1.05rem; font-weight: 700; margin-bottom: 6px; }
.teacher-card .teacher-lang {
    display: inline-block; font-size: .72rem; font-weight: 700; letter-spacing: .06em;
    text-transform: uppercase; color: var(--blue); background: var(--blue-l);
    border-radius: 999px; padding: 4px 12px; margin-bottom: 12px;
}
.teacher-card p { font-size: .875rem; color: var(--muted); line-height: 1.7; margin-bottom: 14px; }
.teacher-card a.teacher-more { font-size: .85rem; font-weight: 600; color: var(--blue); }

.teachers-empty { text-align: center; padding: 90px 0; color: var(--muted); }
</style>
@endpush

@section('content')

<section class="teachers-hero">
    <div class="c">
        <h1>I Nostri Insegnanti</h1>
        <p>Un team di docenti qualificati, ognuno con un percorso di studi e un metodo diversi, uniti dalla stessa attenzione verso lo studente.</p>
    </div>
</section>

<div class="c">
    @if($teachers->isEmpty())
        <div class="teachers-empty">
            <p>Il team è in aggiornamento. Torna a trovarci presto o <a href="{{ route('la-scuola') }}">scopri la scuola</a>.</p>
        </div>
    @else
        <div class="teachers-grid">
            @foreach($teachers as $teacher)
                <article class="teacher-card">
                    <div class="teacher-avatar">
                        @if($teacher->photo)
                            <img src="{{ asset('storage/' . $teacher->photo) }}" alt="{{ $teacher->name }}" loading="lazy">
                        @else
                            <span>🧑‍🏫</span>
                        @endif
                    </div>
                    @if($teacher->language)
                        <span class="teacher-lang">{{ $teacher->language }}</span>
                    @endif
                    <h2>{{ $teacher->name }}</h2>
                    <p>{{ \Illuminate\Support\Str::limit(strip_tags($teacher->bio), 130) }}</p>
                    <a class="teacher-more" href="{{ route('insegnanti.show', $teacher->slug) }}">Scopri il profilo →</a>
                </article>
            @endforeach
        </div>
    @endif
</div>

@endsection
