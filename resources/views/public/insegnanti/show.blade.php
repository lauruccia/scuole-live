@extends('public.layout')

@section('title', $teacher->name . ' — A&A Language Center')
@section('description', \Illuminate\Support\Str::limit(strip_tags($teacher->bio), 155))

@section('breadcrumb-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ route('home') }}" },
        { "@@type": "ListItem", "position": 2, "name": "Insegnanti", "item": "{{ route('insegnanti.index') }}" },
        { "@@type": "ListItem", "position": 3, "name": "{{ str_replace('\"', '\\\"', $teacher->name) }}", "item": "{{ route('insegnanti.show', $teacher->slug) }}" }
    ]
}
</script>
@endsection

@push('styles')
<style>
/* ── PAGINA: INSEGNANTE DETTAGLIO ─────────────────── */
.teacher-hero {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
    color: #fff; padding: 64px 0 48px;
}
.teacher-hero .c { max-width: 800px; display: flex; gap: 26px; align-items: center; }
.teacher-hero-avatar {
    width: 96px; height: 96px; border-radius: 50%; overflow: hidden; flex-shrink: 0;
    background: rgba(255,255,255,.12); display: flex; align-items: center; justify-content: center; font-size: 2.4rem;
    border: 2px solid rgba(255,255,255,.25);
}
.teacher-hero-avatar img { width: 100%; height: 100%; object-fit: cover; }
.teacher-breadcrumb { font-size: .8rem; color: rgba(255,255,255,.6); margin-bottom: 10px; display: block; }
.teacher-breadcrumb a { color: rgba(255,255,255,.8); }
.teacher-breadcrumb a:hover { color: var(--gold); }
.teacher-hero h1 { font-size: clamp(1.5rem, 3.2vw, 2.1rem); font-weight: 800; line-height: 1.25; margin-bottom: 8px; }
.teacher-hero-lang {
    display: inline-block; font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
    padding: 4px 12px; border-radius: 999px; background: var(--gold); color: var(--navy);
}

.teacher-wrap { max-width: 800px; margin: 0 auto; padding: 44px 0 64px; }
.teacher-facts {
    display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 36px;
}
@media (max-width: 640px) { .teacher-facts { grid-template-columns: 1fr; } }
.teacher-fact {
    background: var(--white); border: 1.5px solid var(--border); border-radius: var(--radius);
    padding: 18px 20px;
}
.teacher-fact strong { display: block; font-size: .78rem; text-transform: uppercase; letter-spacing: .05em; color: var(--blue); margin-bottom: 6px; }
.teacher-fact span { font-size: .92rem; color: var(--text); line-height: 1.6; }
.teacher-body { font-size: 1.02rem; line-height: 1.75; color: var(--text); }
.teacher-body p { margin-bottom: 16px; }
.teacher-back { display: inline-block; margin-top: 34px; font-weight: 600; color: var(--blue); }
</style>
@endpush

@section('content')

<section class="teacher-hero">
    <div class="c">
        <div class="teacher-hero-avatar">
            @if($teacher->photo)
                <img src="{{ asset('storage/' . $teacher->photo) }}" alt="{{ $teacher->name }}">
            @else
                <span>🧑‍🏫</span>
            @endif
        </div>
        <div>
            <span class="teacher-breadcrumb">
                <a href="{{ route('home') }}">Home</a> › <a href="{{ route('insegnanti.index') }}">Insegnanti</a>
            </span>
            <h1>{{ $teacher->name }}</h1>
            @if($teacher->language)
                <span class="teacher-hero-lang">{{ $teacher->language }}</span>
            @endif
        </div>
    </div>
</section>

<div class="c">
    <article class="teacher-wrap">
        @if($teacher->qualifications || $teacher->certifications)
            <div class="teacher-facts">
                @if($teacher->qualifications)
                    <div class="teacher-fact">
                        <strong>Titoli di studio</strong>
                        <span>{{ $teacher->qualifications }}</span>
                    </div>
                @endif
                @if($teacher->certifications)
                    <div class="teacher-fact">
                        <strong>Certificazioni / esami</strong>
                        <span>{{ $teacher->certifications }}</span>
                    </div>
                @endif
            </div>
        @endif

        <div class="teacher-body">
            {!! $teacher->bio !!}
        </div>

        <a href="{{ route('insegnanti.index') }}" class="teacher-back">← Tutti gli insegnanti</a>
    </article>
</div>

@endsection
