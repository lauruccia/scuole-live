@extends('public.layout')

@section('title', \App\Models\PageContent::text('vacanze-studio', 'meta_title'))
@section('description', \App\Models\PageContent::text('vacanze-studio', 'meta_description'))
@section('keywords', \App\Models\PageContent::text('vacanze-studio', 'meta_keywords'))

@section('breadcrumb-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ route('home') }}" },
        { "@@type": "ListItem", "position": 2, "name": "Vacanze Studio", "item": "{{ route('vacanze-studio') }}" }
    ]
}
</script>
@endsection

@push('styles')
<style>
/* ── PAGINA: VACANZE STUDIO ──────────────── */
.vs-hero {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
    color: #fff; padding: 80px 0 60px; text-align: center;
}
.vs-hero .eyebrow {
    display: inline-block; font-size: .72rem; font-weight: 700; letter-spacing: .14em;
    text-transform: uppercase; color: var(--gold); margin-bottom: 14px;
}
.vs-hero h1 { font-size: clamp(1.9rem, 4vw, 2.7rem); font-weight: 800; margin-bottom: 14px; }
.vs-hero p { color: rgba(255,255,255,.75); max-width: 680px; margin: 0 auto; }

.vs-sec { padding: 64px 0; }
.vs-sec.alt { background: var(--bg); }
.vs-sec h2 { font-size: clamp(1.4rem, 2.6vw, 1.85rem); font-weight: 800; margin-bottom: 18px; }
.vs-narrow { max-width: 780px; margin: 0 auto; }
.vs-narrow p { margin-bottom: 16px; color: var(--text); line-height: 1.75; }

.vs-highlight {
    background: var(--gold-l); border: 1.5px solid var(--gold);
    border-radius: var(--radius-lg); padding: 22px 26px; margin: 26px auto 0; max-width: 780px;
    font-size: .95rem; line-height: 1.7;
}
.vs-highlight strong { color: var(--navy); }

.vs-features {
    display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; margin-top: 30px;
    max-width: 780px; margin-left: auto; margin-right: auto;
}
@media (max-width: 640px) { .vs-features { grid-template-columns: 1fr; } }
.vs-feature {
    background: var(--white); border: 1.5px solid var(--border); border-radius: var(--radius);
    padding: 14px 18px; font-size: .88rem; color: var(--text); display: flex; gap: 10px; align-items: flex-start;
}
.vs-feature::before { content: '✓'; color: var(--blue); font-weight: 800; }

.vs-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 10px; }
@media (max-width: 760px) { .vs-cards { grid-template-columns: 1fr; } }
.vs-card {
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: var(--radius-lg); padding: 28px 26px;
}
.vs-card h3 { font-size: 1.05rem; font-weight: 800; margin-bottom: 10px; color: var(--navy); }
.vs-card p { color: var(--muted); font-size: .9rem; line-height: 1.7; margin: 0; }

.vs-booking {
    background: var(--white); border: 1.5px solid var(--border); border-radius: var(--radius-lg);
    padding: 30px 28px; max-width: 780px; margin: 0 auto;
}
.vs-booking-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; margin-top: 18px; }
@media (max-width: 640px) { .vs-booking-grid { grid-template-columns: 1fr; } }
.vs-booking-item strong { display: block; font-size: .78rem; text-transform: uppercase; letter-spacing: .05em; color: var(--blue); margin-bottom: 4px; }
.vs-booking-item span { font-size: .92rem; color: var(--text); }

.vs-cta {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
    color: #fff; padding: 64px 0; text-align: center;
}
.vs-cta h2 { font-size: clamp(1.4rem, 3vw, 2rem); font-weight: 800; margin-bottom: 12px; }
.vs-cta p { color: rgba(255,255,255,.7); margin-bottom: 28px; }
.vs-cta .btn-hero-primary {
    display: inline-flex; align-items: center; gap: 8px;
    background: var(--gold); color: var(--navy);
    font-weight: 800; font-size: .9rem; letter-spacing: .04em;
    padding: 15px 34px; border-radius: 999px;
    transition: transform .2s, box-shadow .2s;
}
.vs-cta .btn-hero-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(201,164,44,.35); }
</style>
@endpush

@section('content')

{{-- HERO --}}
<section class="vs-hero">
    <div class="c">
        <span class="eyebrow">{{ \App\Models\PageContent::text('vacanze-studio', 'hero_eyebrow') }}</span>
        <h1>{{ \App\Models\PageContent::text('vacanze-studio', 'hero_title') }}</h1>
        <p>{!! \App\Models\PageContent::html('vacanze-studio', 'hero_text') !!}</p>
    </div>
</section>

{{-- LA CASELLA SUMMER CAMP --}}
<section class="vs-sec">
    <div class="c vs-narrow">
        <h2>{!! \App\Models\PageContent::html('vacanze-studio', 'casella_title') !!}</h2>
        {!! \App\Models\PageContent::html('vacanze-studio', 'casella_text') !!}
        <div class="vs-highlight">
            {!! \App\Models\PageContent::html('vacanze-studio', 'casella_dates_note') !!}
        </div>
        @php $features = \App\Models\PageContent::lines('vacanze-studio', 'casella_features'); @endphp
        @if($features)
            <div class="vs-features">
                @foreach($features as $feature)
                    <div class="vs-feature">{{ $feature }}</div>
                @endforeach
            </div>
        @endif
    </div>
</section>

{{-- PROGRAMMI ALL'ESTERO --}}
<section class="vs-sec alt">
    <div class="c">
        <div class="vs-narrow" style="text-align:center; max-width:640px;">
            <h2>{!! \App\Models\PageContent::html('vacanze-studio', 'estero_title') !!}</h2>
        </div>
        <div class="vs-cards">
            <div class="vs-card">
                <h3>{{ \App\Models\PageContent::text('vacanze-studio', 'junior_title') }}</h3>
                <p>{!! \App\Models\PageContent::html('vacanze-studio', 'junior_text') !!}</p>
            </div>
            <div class="vs-card">
                <h3>{{ \App\Models\PageContent::text('vacanze-studio', 'adulti_title') }}</h3>
                <p>{!! \App\Models\PageContent::html('vacanze-studio', 'adulti_text') !!}</p>
            </div>
        </div>
    </div>
</section>

{{-- PREPARAZIONE ONLINE --}}
<section class="vs-sec">
    <div class="c vs-narrow">
        <h2>{!! \App\Models\PageContent::html('vacanze-studio', 'prep_title') !!}</h2>
        {!! \App\Models\PageContent::html('vacanze-studio', 'prep_text') !!}
    </div>
</section>

{{-- PRENOTAZIONE --}}
<section class="vs-sec alt">
    <div class="c">
        <div class="vs-booking">
            <h2 style="text-align:center;">{{ \App\Models\PageContent::text('vacanze-studio', 'booking_title') }}</h2>
            <p style="text-align:center; color:var(--muted); font-size:.9rem;">{!! \App\Models\PageContent::html('vacanze-studio', 'booking_text') !!}</p>
            <div class="vs-booking-grid">
                <div class="vs-booking-item">
                    <strong>Telefono</strong>
                    <span>{!! \App\Models\PageContent::html('vacanze-studio', 'booking_phone') !!}</span>
                </div>
                <div class="vs-booking-item">
                    <strong>Email</strong>
                    <span>{!! \App\Models\PageContent::html('vacanze-studio', 'booking_email') !!}</span>
                </div>
                <div class="vs-booking-item" style="grid-column: 1 / -1;">
                    <strong>Orari segreteria</strong>
                    <span>{{ \App\Models\PageContent::text('vacanze-studio', 'booking_orari') }}</span>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="vs-cta">
    <div class="c">
        <h2>{!! \App\Models\PageContent::html('vacanze-studio', 'cta_title') !!}</h2>
        <p>{!! \App\Models\PageContent::html('vacanze-studio', 'cta_text') !!}</p>
        <a href="{{ route('contattaci') }}" class="btn-hero-primary">{{ \App\Models\PageContent::text('vacanze-studio', 'cta_button') }}</a>
    </div>
</section>

@endsection
