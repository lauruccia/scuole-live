@extends('public.layout')

@section('title', 'Richiesta inviata — ' . config('app.name'))

@push('styles')
<style>
    /* Navbar e container .c sono gestiti dal layout globale.
       Le precedenti regole nav/.nav-brand/.nav-links !important
       rompevano l'header — sono state rimosse. */

    /* ── PAGINA ── */
    .grazie-section {
        min-height: 70vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 4rem 1.5rem;
        background: #f6faff;
    }
    .grazie-card {
        max-width: 520px;
        width: 100%;
        text-align: center;
        background: #fff;
        border-radius: 18px;
        border: 1.5px solid #dbe7f4;
        padding: 3rem 2.5rem;
        box-shadow: 0 18px 50px rgba(0,37,91,.12);
    }
    .grazie-icon {
        width: 72px; height: 72px;
        background: #e8f5e9;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 2.2rem;
        margin: 0 auto 1.5rem;
    }
    .grazie-card h1 {
        font-size: 1.8rem;
        font-weight: 900;
        margin-bottom: .6rem;
        color: #001b3f;
        letter-spacing: -.4px;
    }
    .grazie-card > p {
        color: #526173;
        line-height: 1.7;
        margin-bottom: 2rem;
        font-size: .95rem;
    }
    .grazie-steps {
        background: #f0f6ff;
        border-radius: 10px;
        padding: 1.25rem 1.5rem;
        text-align: left;
        margin-bottom: 2rem;
    }
    .grazie-steps h3 {
        font-size: .78rem;
        font-weight: 700;
        color: #526173;
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: .85rem;
    }
    .step {
        display: flex;
        gap: .75rem;
        align-items: flex-start;
        margin-bottom: .6rem;
        font-size: .875rem;
        color: #18243a;
    }
    .step-num {
        width: 22px; height: 22px;
        background: #0057d9;
        color: #fff;
        border-radius: 50%;
        font-size: .7rem;
        font-weight: 700;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        margin-top: 2px;
    }
    .grazie-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        padding: .9rem;
        background: #0057d9;
        color: #fff;
        border-radius: 8px;
        font-size: .95rem;
        font-weight: 800;
        text-decoration: none;
        letter-spacing: .02em;
        box-shadow: 0 10px 25px rgba(0,87,217,.25);
        transition: background .2s, transform .1s;
    }
    .grazie-btn:hover { background: #0046b8; transform: translateY(-1px); }

    /* ── FOOTER OVERRIDE ── */
    footer {
        background: #001126 !important;
        color: #a8b4c8 !important;
        padding: 18px 2rem !important;
        margin-top: 0 !important;
    }
</style>
@endpush

@section('content')

<div class="grazie-section">
    <div class="grazie-card">
        <div class="grazie-icon">✅</div>
        <h1>{{ \App\Models\PageContent::text('grazie', 'grazie_title') }}</h1>
        <p>
            {!! \App\Models\PageContent::html('grazie', 'grazie_text') !!}
        </p>

        <div class="grazie-steps">
            <h3>{{ \App\Models\PageContent::text('grazie', 'steps_title') }}</h3>
            <div class="step">
                <div class="step-num">1</div>
                <span>{{ \App\Models\PageContent::text('grazie', 'step1_text') }}</span>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <span>{{ \App\Models\PageContent::text('grazie', 'step2_text') }}</span>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <span>{{ \App\Models\PageContent::text('grazie', 'step3_text') }}</span>
            </div>
        </div>

        <a href="{{ route('home') }}" class="grazie-btn">← Torna alla home</a>
    </div>
</div>

@endsection
