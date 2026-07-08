@extends('public.layout')

@section('title', 'Le Certificazioni Trinity College London — Sede Esami n° 8241 | A&A Language Center Roma')
@section('description', 'A&A Language Center è sede d\'esami FULL Trinity College London n° 8241 a Roma. Certificazioni GESE e ISE riconosciute dal MIUR, valide per crediti formativi, crediti universitari e concorsi pubblici.')
@section('keywords', 'certificazioni Trinity Roma, sede esami Trinity College London Roma, esami GESE Roma, esami ISE Roma, certificazione inglese crediti formativi, certificazione inglese università, Trinity College London 8241')

@section('breadcrumb-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ route('home') }}" },
        { "@@type": "ListItem", "position": 2, "name": "Le Certificazioni", "item": "{{ route('le-certificazioni') }}" }
    ]
}
</script>
@endsection

@push('styles')
<style>
/* ── PAGINA: LE CERTIFICAZIONI ──────────────── */
.cert-hero {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
    color: #fff; padding: 80px 0 60px; text-align: center;
}
.cert-hero .eyebrow {
    display: inline-block; font-size: .72rem; font-weight: 700; letter-spacing: .14em;
    text-transform: uppercase; color: var(--gold); margin-bottom: 14px;
}
.cert-hero h1 { font-size: clamp(1.9rem, 4vw, 2.7rem); font-weight: 800; margin-bottom: 14px; }
.cert-hero p { color: rgba(255,255,255,.75); max-width: 640px; margin: 0 auto; }
.cert-hero-logo { margin: 26px auto 0; max-width: 200px; }
.cert-hero-logo img { width: 100%; background: #fff; border-radius: 12px; padding: 16px 20px; }

.cert-sec { padding: 64px 0; }
.cert-sec.alt { background: var(--bg); }
.cert-sec h2 { font-size: clamp(1.4rem, 2.6vw, 1.85rem); font-weight: 800; margin-bottom: 18px; }
.cert-sec h2 em { font-style: normal; color: var(--blue); }
.cert-narrow { max-width: 780px; margin: 0 auto; }
.cert-narrow p { margin-bottom: 16px; color: var(--text); line-height: 1.75; }
.cert-narrow a { color: var(--blue); text-decoration: underline; }

.cert-highlight {
    background: var(--gold-l); border: 1.5px solid var(--gold);
    border-radius: var(--radius-lg); padding: 26px 28px; margin: 26px 0;
    font-size: 1.02rem; line-height: 1.7;
}
.cert-highlight strong { color: var(--navy); }

.cert-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 34px; }
@media (max-width: 760px) { .cert-cards { grid-template-columns: 1fr; } }
.cert-card {
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: var(--radius-lg); padding: 30px 28px;
}
.cert-card .badge {
    display: inline-block; font-size: .68rem; font-weight: 700; letter-spacing: .08em;
    text-transform: uppercase; padding: 4px 12px; border-radius: 999px;
    background: var(--blue-l); color: var(--blue); margin-bottom: 14px;
}
.cert-card h3 { font-size: 1.2rem; font-weight: 800; margin-bottom: 10px; }
.cert-card p { color: var(--muted); font-size: .93rem; line-height: 1.7; margin-bottom: 12px; }
.cert-card a { color: var(--blue); font-weight: 600; font-size: .9rem; }

.cert-uses { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 30px; }
@media (max-width: 760px) { .cert-uses { grid-template-columns: 1fr; } }
.cert-use {
    background: var(--white); border: 1.5px solid var(--border); border-radius: var(--radius);
    padding: 22px 20px; text-align: center;
}
.cert-use .icon { font-size: 1.8rem; margin-bottom: 10px; display: block; }
.cert-use strong { display: block; margin-bottom: 6px; }
.cert-use span { font-size: .85rem; color: var(--muted); }

.cert-cta {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
    color: #fff; padding: 64px 0; text-align: center;
}
.cert-cta h2 { font-size: clamp(1.4rem, 3vw, 2rem); font-weight: 800; margin-bottom: 12px; }
.cert-cta p { color: rgba(255,255,255,.7); margin-bottom: 28px; }
.cert-cta .btn-hero-primary {
    display: inline-flex; align-items: center; gap: 8px;
    background: var(--gold); color: var(--navy);
    font-weight: 800; font-size: .9rem; letter-spacing: .04em;
    padding: 15px 34px; border-radius: 999px;
    transition: transform .2s, box-shadow .2s;
}
.cert-cta .btn-hero-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(201,164,44,.35); }
</style>
@endpush

@section('content')

{{-- HERO --}}
<section class="cert-hero">
    <div class="c">
        <span class="eyebrow">Open Your Mind To The World</span>
        <h1>Le Certificazioni</h1>
        <p>A&amp;A Language Center è <strong>sede d'esami FULL Trinity College London n° 8241</strong>: gli esami si tengono direttamente nella nostra sede in diversi periodi dell'anno.</p>
        <div class="cert-hero-logo">
            <img src="{{ asset('images/cert-trinity.svg') }}" alt="Trinity College London — Sede Esami n° 8241">
        </div>
    </div>
</section>

{{-- SEDE ESAMI --}}
<section class="cert-sec">
    <div class="c cert-narrow">
        <h2>Sede d'esami ufficiale <em>Trinity College London n° 8241</em></h2>
        <p>Gli studenti possono sostenere gli esami direttamente in sede e ottenere certificati validi per i <strong>Crediti Formativi negli esami di maturità</strong>, per i <strong>crediti universitari</strong> e per i <strong>concorsi pubblici</strong>.</p>
        <p>Gli esami Trinity sono particolarmente importanti anche per gli studenti universitari: sono riconosciuti per l'ammissione a un gran numero di facoltà e possono essere utilizzati come crediti universitari per l'idoneità linguistica.</p>
        <div class="cert-highlight">
            🎓 <strong>Più di 1.600 facoltà e corsi di laurea riconoscono le certificazioni Trinity.</strong><br>
            <a href="https://www.trinitycollege.it/riconoscimenti/" target="_blank" rel="noopener">Consulta l'elenco completo dei riconoscimenti →</a>
        </div>
        <p>Le certificazioni Trinity College London sono riconosciute da <strong>università, aziende e istituzioni governative</strong> in Italia e nel mondo.</p>
    </div>
</section>

{{-- RICONOSCIMENTO MIUR --}}
<section class="cert-sec alt">
    <div class="c cert-narrow">
        <h2>Ente certificatore riconosciuto dal <em>Ministero dell'Istruzione</em></h2>
        <p>Trinity College London è incluso nell'elenco degli Enti certificatori pubblicato dal Ministero Italiano della Pubblica Istruzione che soddisfano i requisiti per il riconoscimento della validità delle certificazioni delle competenze linguistico-comunicative in lingua straniera (Decreto 07.03.2012, Prot. 3889, aggiornato con Decreto del Direttore n. 118 del 28.02.2017).</p>
        <p><a href="https://www.miur.gov.it/enti-certificatori-lingue-straniere" target="_blank" rel="noopener">www.miur.gov.it/enti-certificatori-lingue-straniere →</a></p>
        <p>Trinity College London — Italian Co-ordinator è inoltre un <strong>Ente accreditato dal Ministero per la formazione degli insegnanti</strong> secondo la normativa vigente. Le certificazioni Trinity possono essere valutate come crediti formativi per l'Esame di Stato.</p>
    </div>
</section>

{{-- GESE / ISE --}}
<section class="cert-sec">
    <div class="c">
        <div class="cert-narrow">
            <h2>Gli esami: <em>ISE</em> e <em>GESE</em></h2>
            <p>Le certificazioni Trinity principalmente riconosciute dalle università italiane sono le <strong>ISE — Integrated Skills in English</strong>. Molti corsi di laurea riconoscono anche le certificazioni <strong>GESE — Graded Examinations in Spoken English</strong>.</p>
        </div>
        <div class="cert-cards">
            <div class="cert-card">
                <span class="badge">Speciale Università</span>
                <h3>ISE — Integrated Skills in English</h3>
                <p>Valutazione dell'uso integrato delle 4 abilità: <strong>Reading &amp; Writing</strong> e <strong>Speaking &amp; Listening</strong>. È la certificazione più riconosciuta dalle università italiane per l'idoneità linguistica e i crediti universitari.</p>
                <a href="https://www.trinitycollege.it/inglese/integrated-skills-in-english" target="_blank" rel="noopener">Scopri la certificazione ISE →</a>
            </div>
            <div class="cert-card">
                <span class="badge">Speaking &amp; Listening</span>
                <h3>GESE — Graded Examinations in Spoken English</h3>
                <p>Esami orali graduati su 12 livelli, dalla prima scolarizzazione al livello C2. Riconosciuti da molti corsi di laurea e ideali per crediti formativi alla maturità e per i concorsi pubblici.</p>
                <a href="https://www.trinitycollege.it/riconoscimenti/" target="_blank" rel="noopener">Vedi i riconoscimenti GESE →</a>
            </div>
        </div>
    </div>
</section>

{{-- A COSA SERVONO --}}
<section class="cert-sec alt">
    <div class="c">
        <div class="cert-narrow" style="text-align:center;">
            <h2>A cosa servono le certificazioni Trinity</h2>
        </div>
        <div class="cert-uses">
            <div class="cert-use">
                <span class="icon">🏫</span>
                <strong>Esame di maturità</strong>
                <span>Crediti formativi per l'Esame di Stato secondo la normativa vigente</span>
            </div>
            <div class="cert-use">
                <span class="icon">🎓</span>
                <strong>Università</strong>
                <span>Ammissione e crediti per l'idoneità linguistica in più di 1.600 facoltà e corsi di laurea</span>
            </div>
            <div class="cert-use">
                <span class="icon">💼</span>
                <strong>Concorsi pubblici</strong>
                <span>Certificazioni valide per concorsi pubblici e riconosciute da aziende e istituzioni</span>
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="cert-cta">
    <div class="c">
        <h2>Iscriviti a uno dei nostri corsi di preparazione<br>agli esami ISE B1 · B2 · C1 — Trinity College London</h2>
        <p>Diventa parte di A&amp;A per migliorare la tua carriera. Segreteria: <a href="tel:+39065743734" style="color:var(--gold);font-weight:700;">06 574 3734</a></p>
        <a href="{{ route('contattaci') }}" class="btn-hero-primary">CONTATTACI →</a>
    </div>
</section>

@endsection
