@extends('public.layout')

@section('title', 'Lavora con Noi — Cerchiamo Docenti Madrelingua a Roma')
@section('description', 'A&A Language Center cerca insegnanti madrelingua e bilingue qualificati per corsi di inglese, spagnolo, francese, tedesco e altre lingue a Roma San Paolo. Invia la tua candidatura.')
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
        <h1>Lavora con Noi</h1>
        <p>Unisciti al nostro team di docenti qualificati</p>
    </div>
</section>

{{-- ── INTRO ── --}}
<section class="sec">
    <div class="c">
        <div class="intro-grid">
            <div class="intro-text">
                <div class="sec-title"><h2>Entra nel team A&A</h2></div>
                <p>Sei un insegnante madrelingua o bilingue? Vuoi mettere a frutto le tue competenze linguistiche in un contesto dinamico e professionale? Se ami il tuo lavoro e credi nel valore dell'educazione, A&amp;A Language Center è il posto che fa per te.</p>
                <p>Dal 2002 costruiamo un team di docenti appassionati e competenti che condividono una visione comune: rendere l'apprendimento delle lingue un'esperienza accessibile, efficace e piacevole per ogni studente.</p>
                <p>La nostra scuola si trova nel cuore del quartiere <strong>San Paolo</strong> a Roma, vicino all'Università Roma Tre, in un ambiente cosmopolita e stimolante.</p>
            </div>
            <div class="intro-photo">
                <img src="https://images.unsplash.com/photo-1507537297725-24a1c029d3ca?auto=format&fit=crop&w=900&q=85" alt="Docenti A&A Language Center">
            </div>
        </div>
    </div>
</section>

{{-- ── COSA CERCHIAMO ── --}}
<section class="sec sec-light">
    <div class="c">
        <div class="sec-title"><h2>Cosa cerchiamo</h2></div>
        <p style="font-size:15px;color:#2d3a4d;max-width:700px;line-height:1.75;margin-bottom:0;">Cerchiamo professionisti dell'insegnamento che soddisfino i seguenti requisiti. Non necessariamente tutti: valutiamo ogni candidatura nel suo insieme.</p>
        <div class="req-grid">
            <div class="req-item">
                <div class="req-check">✓</div>
                <div>
                    <h4>Madrelingua o bilingue</h4>
                    <p>Competenza linguistica nativa o equivalente nella lingua insegnata, con capacità di trasmettere sfumature culturali e contestuali.</p>
                </div>
            </div>
            <div class="req-item">
                <div class="req-check">✓</div>
                <div>
                    <h4>Formazione universitaria</h4>
                    <p>Laurea in Lingue, Letterature Straniere, Scienze della Formazione o disciplina affine.</p>
                </div>
            </div>
            <div class="req-item">
                <div class="req-check">✓</div>
                <div>
                    <h4>Certificazione di insegnamento</h4>
                    <p>Possesso di certificazioni riconosciute come TEFL, CELTA, DELTA, PGCE o equivalenti per la lingua di interesse.</p>
                </div>
            </div>
            <div class="req-item">
                <div class="req-check">✓</div>
                <div>
                    <h4>Esperienza nell'insegnamento</h4>
                    <p>Esperienza documentabile nell'insegnamento delle lingue, preferibilmente in contesti eterogenei (adulti, aziende, studenti).</p>
                </div>
            </div>
            <div class="req-item">
                <div class="req-check">✓</div>
                <div>
                    <h4>Passione e capacità relazionali</h4>
                    <p>Genuino interesse per l'insegnamento, pazienza, empatia e capacità di adattarsi ai diversi stili di apprendimento degli studenti.</p>
                </div>
            </div>
            <div class="req-item">
                <div class="req-check">+</div>
                <div>
                    <h4>Esperienza aziendale (preferenziale)</h4>
                    <p>Conoscenza del contesto lavorativo e capacità di insegnare linguaggi specialistici (Business English, legal, medical, ecc.).</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── COSA OFFRIAMO ── --}}
<section class="sec">
    <div class="c">
        <div class="sec-title"><h2>Cosa offriamo</h2></div>
        <div class="offer-grid">
            <div class="offer-card">
                <div class="offer-icon">🤝</div>
                <h3>Ambiente accogliente</h3>
                <p>Un team internazionale e collaborativo, dove ogni docente è valorizzato e ascoltato.</p>
            </div>
            <div class="offer-card">
                <div class="offer-icon">⏰</div>
                <h3>Flessibilità oraria</h3>
                <p>Orari concordati in base alle disponibilità reciproche, per conciliare lavoro e vita privata.</p>
            </div>
            <div class="offer-card">
                <div class="offer-icon">📚</div>
                <h3>Aggiornamento continuo</h3>
                <p>Accesso a materiali didattici aggiornati e opportunità di formazione professionale continua.</p>
            </div>
            <div class="offer-card">
                <div class="offer-icon">🌍</div>
                <h3>Team internazionale</h3>
                <p>Lavora a fianco di colleghi provenienti da tutto il mondo in un clima multiculturale stimolante.</p>
            </div>
        </div>
    </div>
</section>

{{-- ── SEDE ── --}}
<section class="sede-band">
    <div class="c">
        <div class="sede-grid">
            <div class="sede-text">
                <h2 class="sede-title">La nostra sede</h2>
                <p>A&amp;A Language Center si trova nel vivace quartiere San Paolo di Roma, uno dei poli culturali e universitari più dinamici della città grazie alla presenza dell'Università Roma Tre.</p>
                <p>La posizione è strategica e comodamente raggiungibile sia con i mezzi pubblici che in auto o bicicletta.</p>
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
                <img src="https://images.unsplash.com/photo-1515879218367-8466d910aaa4?auto=format&fit=crop&w=800&q=85" alt="Quartiere San Paolo Roma">
            </div>
        </div>
    </div>
</section>

{{-- ── CANDIDATURA ── --}}
<section class="sec">
    <div class="c">
        <div class="sec-title" style="text-align:center;">
            <h2 style="text-align:center;">Invia la tua candidatura</h2>
            <div style="margin:0 auto;"></div>
        </div>
        <div class="candidatura-box">
            <p>Manda il tuo curriculum vitae con una breve lettera di presentazione all'indirizzo email della direzione, oppure contattaci tramite Instagram. Valutiamo ogni candidatura con la massima attenzione e risponderemo entro pochi giorni lavorativi.</p>
            <div class="candidatura-actions">
                <a href="mailto:direzione@aealanguagecenter.it" class="btn-cta btn-primary-blue">
                    📧 Invia la tua candidatura
                </a>
                <a href="https://www.instagram.com/aealanguagecenter/" target="_blank" rel="noopener" class="btn-cta btn-instagram">
                    📷 Seguici su Instagram
                </a>
            </div>
            <p style="margin-top:24px;font-size:13px;color:#526173;">
                Invia il tuo CV a <strong>direzione@aealanguagecenter.it</strong> con oggetto "Candidatura Docente — [Lingua]"
            </p>
        </div>
    </div>
</section>

@endsection
