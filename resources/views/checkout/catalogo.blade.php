@extends('public.layout')

@section('title', 'Corsi di Lingue - A&A Language Center Roma')
@section('description', 'Scegli il tuo corso di lingue a Roma. Inglese, spagnolo, arabo, francese, tedesco, italiano per stranieri. Iscriviti online con pagamento sicuro.')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/catalogo.css') }}">
@endpush

@section('content')

<section class="cat-hero">
    <div class="c cat-hero-inner">
        <div class="cat-hero-label">Corsi disponibili</div>
        <h1>Scegli il tuo corso.<br><span>Inizia a parlare.</span></h1>
        <p>Tutti i corsi sono personalizzati sul tuo livello. Docenti qualificati, orari flessibili, risultati garantiti.</p>
    </div>
</section>

<div class="filter-bar">
    <div class="c">
        <form method="GET" action="{{ route('checkout.catalogo') }}" class="filter-inner">
            <span class="filter-label">Filtra:</span>

            <select name="ore" class="filter-select {{ request('ore') ? 'active' : '' }}" onchange="this.form.submit()">
                <option value="">Tutte le ore</option>
                @foreach($availableOre as $ore)
                    <option value="{{ $ore }}" {{ request('ore') == $ore ? 'selected' : '' }}>
                        {{ number_format((float) $ore, 0, ',', '.') }} ore
                    </option>
                @endforeach
            </select>

            <select name="tipo" class="filter-select {{ request('tipo') ? 'active' : '' }}" onchange="this.form.submit()">
                <option value="">Tutte le tipologie</option>
                @foreach($availableTipi as $tipo)
                    <option value="{{ $tipo }}" {{ request('tipo') == $tipo ? 'selected' : '' }}>
                        {{ $tipo }}
                    </option>
                @endforeach
            </select>

            @if(request('ore') || request('tipo'))
                <a href="{{ route('checkout.catalogo') }}" class="btn-reset">Rimuovi filtri</a>
            @endif

            <span class="filter-count">
                {{ $courses->count() }} {{ $courses->count() == 1 ? 'corso' : 'corsi' }}
            </span>
        </form>
    </div>
</div>

<section class="courses-section">
    <div class="c">

        @if(session('info'))
            <div style="background:#dbeafe;border:1px solid #93c5fd;color:#1e40af;border-radius:10px;padding:12px 18px;margin-bottom:28px;font-size:14px;">
                {{ session('info') }}
            </div>
        @endif

        @if($courses->isEmpty())

            <div class="empty-state">
                <div style="font-size:3rem;margin-bottom:12px;">&#128218;</div>

                @if(request('ore') || request('tipo'))
                    <h2>Nessun corso trovato con questi filtri</h2>
                    <p>Prova a modificare o rimuovere i filtri per vedere tutti i corsi disponibili.</p>
                    <a href="{{ route('checkout.catalogo') }}" class="btn-enroll" style="margin-top:20px;display:inline-flex;">
                        Vedi tutti i corsi
                    </a>
                @else
                    <h2>Nessun corso disponibile al momento</h2>
                    <p>Contattaci per conoscere i corsi in partenza o per richiedere un corso personalizzato.</p>
                    <a href="{{ route('iscrizione') }}" class="btn-enroll" style="margin-top:20px;display:inline-flex;">
                        Contattaci
                    </a>
                @endif
            </div>

        @else

            <div class="sec-title">
                <h2>{{ request('ore') || request('tipo') ? 'Risultati filtrati' : 'I nostri corsi' }}</h2>
                <p>
                    {{ $courses->count() }} {{ $courses->count() == 1 ? 'corso trovato' : 'corsi trovati' }}

                    @if(request('ore'))
                        - <strong>{{ number_format((float) request('ore'), 0, ',', '.') }} ore</strong>
                    @endif

                    @if(request('tipo'))
                        - <strong>{{ request('tipo') }}</strong>
                    @endif

                    @if(!request('ore') && !request('tipo'))
                        - iscriviti online in pochi minuti
                    @endif
                </p>
            </div>

            <div class="courses-grid">
                @foreach($courses as $course)
                    <div class="course-card">

                        <div class="course-thumb">
                            @if(!empty($course->image_path))
                                <img src="{{ Storage::url($course->image_path) }}" alt="{{ $course->name }}">
                            @else
                                <span>&#127758;</span>
                            @endif
                        </div>

                        <div class="course-body">
                            <div class="course-name">{{ $course->name }}</div>

                            @if(!empty($course->description))
                                <div class="course-desc">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($course->description), 130) }}
                                </div>
                            @endif

                            <div class="course-meta">
                                <span>
                                    &#9203;
                                    {{ number_format((float) ($course->hours_purchased ?? 0), 0, ',', '.') }} ore
                                </span>

                                @if(!empty($course->lesson_type))
                                    <span>&#128100; {{ $course->lesson_type }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="course-footer">
                            <div class="price-block">
                                <div class="price-main">
                                    &#8364; {{ number_format((float) $course->total_price, 2, ',', '.') }}
                                </div>

                                @if(($course->enrollment_fee ?? 0) > 0)
                                    <div class="price-note">
                                        incl. &#8364; {{ number_format((float) $course->enrollment_fee, 2, ',', '.') }} quota iscrizione
                                    </div>
                                @endif
                            </div>

                            <a href="{{ route('checkout.show', $course) }}" class="btn-enroll">
                                Iscriviti &#8594;
                            </a>
                        </div>

                    </div>
                @endforeach
            </div>

        @endif

    </div>
</section>

<section class="cta-strip">
    <div class="c cta-inner">
        <div>
            <h2>Non sei sicuro di quale corso fa per te?</h2>
            <p>Prenota un test di ingresso gratuito &mdash; valutiamo il tuo livello e ti consigliamo il percorso migliore.</p>
        </div>

        <a href="{{ route('iscrizione') }}" class="btn-yellow">
            PRENOTA IL TEST GRATUITO &#8594;
        </a>
    </div>
</section>

<div style="background:#001126;color:#fff;border-top:1px solid rgba(255,255,255,.12);padding:18px 0;font-size:13px;">
    <div class="c" style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;align-items:center;">
        <div style="display:flex;gap:10px;align-items:center;color:#dbe9ff;">
            &#128205; Viale Leonardo da Vinci, 193 &ndash; 00145 Roma
        </div>

        <div style="display:flex;gap:10px;align-items:center;color:#dbe9ff;">
            &#128222; <a href="tel:+390657437364" style="color:#dbe9ff;">06 5743734</a>
        </div>

        <div style="display:flex;gap:10px;align-items:center;color:#dbe9ff;">
            &#9993; <a href="mailto:info@aealanguagecenter.it" style="color:#dbe9ff;">info@aealanguagecenter.it</a>
        </div>
    </div>
</div>

@endsection
