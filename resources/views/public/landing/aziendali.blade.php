@extends('public.layout')

@section('title', 'Corsi di Inglese Aziendali a Roma — Business English | A&A')
@section('description', 'Corsi di inglese aziendali a Roma per dipendenti, manager e team. Business English, formazione linguistica B2B in sede o online. Clienti: MEF, Confcommercio, H10 Hotels.')
@section('keywords', 'corsi di inglese aziendali Roma, corsi di lingue aziendali Roma, formazione linguistica aziendale Roma, business English Roma, corsi inglese per dipendenti Roma, corsi inglese aziende in sede Roma, formazione linguistica B2B Roma, corso inglese aziendale Roma, formazione personale finanziata lingue Roma')
@section('og-image-alt', 'Corsi di inglese aziendali a Roma — A&A Language Center')

@section('breadcrumb-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ route('home') }}" },
        { "@@type": "ListItem", "position": 2, "name": "Corsi Aziendali a Roma", "item": "{{ route('landing.aziendali') }}" }
    ]
}
</script>
@endsection

@section('extra-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Service",
    "name": "Corsi di Lingue Aziendali a Roma",
    "serviceType": "Corporate Language Training",
    "provider": {
        "@@type": "EducationalOrganization",
        "name": "A&A Language Center",
        "sameAs": "{{ config('app.url') }}",
        "telephone": "+39-06-5743734"
    },
    "areaServed": { "@@type": "City", "name": "Roma" },
    "description": "Formazione linguistica aziendale a Roma per dipendenti, manager e team executive. Corsi di inglese, business English, spagnolo, francese, tedesco e altre lingue. Lezioni in sede aziendale o online, programmi su misura con certificazioni CEFR.",
    "audience": { "@@type": "BusinessAudience", "audienceType": "Aziende, enti pubblici, hotel, studi professionali" }
}
</script>
@endsection

@push('styles')
<style>
.clienti-grid {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px;
}
.cliente-c {
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: var(--radius-lg); padding: 22px 18px; text-align: center;
    transition: border-color .25s, transform .25s;
}
.cliente-c:hover { border-color: var(--blue); transform: translateY(-3px); }
.cliente-c h4 { font-size: 1rem; font-weight: 800; color: var(--navy); margin-bottom: 6px; letter-spacing: -.01em; }
.cliente-c p { font-size: .78rem; color: var(--muted); line-height: 1.45; margin: 0; }

.benefit-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; }
.benefit-c {
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: var(--radius-lg); padding: 28px 24px;
    display: flex; gap: 18px; align-items: flex-start;
}
.benefit-icon {
    width: 48px; height: 48px; flex-shrink: 0;
    background: var(--blue-l); color: var(--blue);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem;
}
.benefit-c h3 { font-size: 1rem; font-weight: 700; color: var(--navy); margin-bottom: 8px; }
.benefit-c p { font-size: .88rem; color: var(--muted); line-height: 1.65; margin: 0; }

@media (max-width: 900px) {
    .clienti-grid { grid-template-columns: 1fr 1fr; }
    .benefit-grid { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')

{{-- HERO --}}
<section class="page-hero">
    <div class="c page-hero-inner">
        <div class="breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span class="sep">›</span>
            <span>Corsi Aziendali a Roma</span>
        </div>
        <h1>Corsi di <em>Inglese Aziendali</em> a Roma</h1>
        <p class="subtitle">Formazione linguistica B2B in sede o online. Business English, lingue per il lavoro. Clienti: MEF, Confcommercio, H10 Hotels.</p>
    </div>
</section>

{{-- INTRO --}}
<section class="sec">
    <div class="c">
        <div class="sec-header center">
            <div class="section-label" style="justify-content:center;">Formazione linguistica aziendale</div>
            <h2 class="sec-heading">Lingue come <em>vantaggio competitivo</em></h2>
            <p class="sec-subtext" style="text-align:center;margin:0 auto;">A&amp;A Language Center è specializzata in <strong>corsi di lingue aziendali a Roma</strong> dal 2002. Progettiamo percorsi formativi su misura per aziende, enti pubblici, hotel e studi professionali — con focus sul lessico settoriale e sui risultati misurabili.</p>
        </div>
    </div>
</section>

{{-- CLIENTI --}}
<section class="sec sec-bg">
    <div class="c">
        <div class="sec-header center">
            <div class="section-label" style="justify-content:center;">I nostri clienti</div>
            <h2 class="sec-heading">Aziende, enti, hotel che <em>si fidano di noi</em></h2>
        </div>
        <div class="clienti-grid">
            <div class="cliente-c"><h4>MEF</h4><p>Ministero dell'Economia e delle Finanze</p></div>
            <div class="cliente-c"><h4>Confcommercio</h4><p>Associazione di categoria nazionale</p></div>
            <div class="cliente-c"><h4>H10 Hotels</h4><p>Catena alberghiera internazionale</p></div>
            <div class="cliente-c"><h4>+ 50 aziende</h4><p>Studi professionali, PMI, scuole, enti</p></div>
        </div>
    </div>
</section>

{{-- BENEFIT --}}
<section class="sec">
    <div class="c">
        <div class="sec-header">
            <div class="section-label">Perché scegliere A&A</div>
            <h2 class="sec-heading">Cosa rende <em>diversa</em> la nostra formazione</h2>
        </div>
        <div class="benefit-grid">
            <div class="benefit-c">
                <div class="benefit-icon">🎯</div>
                <div>
                    <h3>Programmi su misura</h3>
                    <p>Ogni corso è progettato sulle reali esigenze del cliente: tone of voice del settore, lessico tecnico (legale, medico, hospitality, finance), obiettivi di business misurabili.</p>
                </div>
            </div>
            <div class="benefit-c">
                <div class="benefit-icon">🏢</div>
                <div>
                    <h3>In sede o online</h3>
                    <p>I nostri docenti vengono direttamente nella sede aziendale a Roma e provincia, oppure organizziamo classi in videoconferenza con piattaforma dedicata.</p>
                </div>
            </div>
            <div class="benefit-c">
                <div class="benefit-icon">📊</div>
                <div>
                    <h3>Report mensili HR</h3>
                    <p>Forniamo report di frequenza, progressi individuali e ROI formativo. Strumenti pensati per chi gestisce HR e formazione e deve rendicontare a budget.</p>
                </div>
            </div>
            <div class="benefit-c">
                <div class="benefit-icon">🎓</div>
                <div>
                    <h3>Certificazioni valide</h3>
                    <p>Rilasciamo certificati validi per concorsi pubblici, aggiornamento professionale e formazione del personale finanziata. Preparazione esami Cambridge, IELTS, Trinity.</p>
                </div>
            </div>
            <div class="benefit-c">
                <div class="benefit-icon">⚡</div>
                <div>
                    <h3>Flessibilità totale</h3>
                    <p>Orari mattutini, pomeridiani, serali o pausa pranzo. Lezioni 1-a-1 per executive o gruppi omogenei per livello. Gestiamo turnover e assenze senza penalizzazioni.</p>
                </div>
            </div>
            <div class="benefit-c">
                <div class="benefit-icon">🔒</div>
                <div>
                    <h3>20+ anni di esperienza</h3>
                    <p>Dal 2002 formiamo aziende a Roma. Conosciamo a fondo le esigenze di tutti i comparti: PA, hospitality, retail, professional services, manufacturing.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- LINGUE --}}
<section class="sec sec-dark">
    <div class="c">
        <div class="sec-header center">
            <div class="section-label white" style="justify-content:center;">Le lingue</div>
            <h2 class="sec-heading white">Tutte le lingue per il <em class="gold">tuo business</em></h2>
            <p class="sec-subtext white" style="text-align:center;margin:0 auto;">Business English in primis, ma anche tutte le altre lingue strategiche per il mercato internazionale.</p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin-top:20px;">
            <span class="cert-tag" style="background:rgba(255,255,255,.1);border:1.5px solid rgba(255,255,255,.25);border-radius:50px;padding:7px 18px;font-size:.82rem;font-weight:600;color:#fff;">🇬🇧 Inglese (Business)</span>
            <span class="cert-tag" style="background:rgba(255,255,255,.1);border:1.5px solid rgba(255,255,255,.25);border-radius:50px;padding:7px 18px;font-size:.82rem;font-weight:600;color:#fff;">🇪🇸 Spagnolo</span>
            <span class="cert-tag" style="background:rgba(255,255,255,.1);border:1.5px solid rgba(255,255,255,.25);border-radius:50px;padding:7px 18px;font-size:.82rem;font-weight:600;color:#fff;">🇫🇷 Francese</span>
            <span class="cert-tag" style="background:rgba(255,255,255,.1);border:1.5px solid rgba(255,255,255,.25);border-radius:50px;padding:7px 18px;font-size:.82rem;font-weight:600;color:#fff;">🇩🇪 Tedesco</span>
            <span class="cert-tag" style="background:rgba(255,255,255,.1);border:1.5px solid rgba(255,255,255,.25);border-radius:50px;padding:7px 18px;font-size:.82rem;font-weight:600;color:#fff;">🇸🇦 Arabo</span>
            <span class="cert-tag" style="background:rgba(255,255,255,.1);border:1.5px solid rgba(255,255,255,.25);border-radius:50px;padding:7px 18px;font-size:.82rem;font-weight:600;color:#fff;">🇷🇺 Russo</span>
            <span class="cert-tag" style="background:rgba(255,255,255,.1);border:1.5px solid rgba(255,255,255,.25);border-radius:50px;padding:7px 18px;font-size:.82rem;font-weight:600;color:#fff;">🇨🇳 Cinese</span>
            <span class="cert-tag" style="background:rgba(255,255,255,.1);border:1.5px solid rgba(255,255,255,.25);border-radius:50px;padding:7px 18px;font-size:.82rem;font-weight:600;color:#fff;">🇵🇹 Portoghese</span>
            <span class="cert-tag" style="background:rgba(255,255,255,.1);border:1.5px solid rgba(255,255,255,.25);border-radius:50px;padding:7px 18px;font-size:.82rem;font-weight:600;color:#fff;">🇮🇹 Italiano per dipendenti stranieri</span>
        </div>
    </div>
</section>

{{-- FAQ --}}
<x-seo-faq
    title="Domande frequenti — Corsi aziendali"
    subtitle="Le risposte alle domande più comuni di HR manager, training manager e responsabili formazione."
    :items="[
        ['q' => 'Quanto costa un corso di inglese aziendale a Roma?', 'a' => '<p>I costi dipendono dal numero di partecipanti, dalle ore totali, dalla modalità (in sede o online) e dal livello del corso. Per un preventivo personalizzato <a href=\"' . route('contattaci') . '\">contattaci</a> — rispondiamo entro 24 ore con un\'analisi dei fabbisogni e una proposta dettagliata.</p>'],
        ['q' => 'Quanto dura tipicamente un corso aziendale?', 'a' => '<p>I cicli tipici sono 40, 60 o 90 ore, distribuite su 3–9 mesi a seconda dell\'intensità. Lavoriamo bene anche su programmi annuali e biennali con monitoraggio continuo del livello CEFR.</p>'],
        ['q' => 'Venite a fare lezione direttamente nella nostra sede?', 'a' => '<p>Sì. I nostri docenti si spostano nella sede aziendale a Roma e provincia. Per sedi fuori Roma valutiamo modalità mista (in presenza + online) per ottimizzare i costi.</p>'],
        ['q' => 'Rilasciate certificazioni utili per la formazione finanziata?', 'a' => '<p>Sì. Rilasciamo attestati nominativi validi per concorsi pubblici, aggiornamento professionale, formazione del personale finanziata (Fondimpresa, Fondoprofessioni, Fondirigenti) e crediti formativi.</p>'],
        ['q' => 'Possiamo fare un test di livello per tutti i dipendenti?', 'a' => '<p>Sì. Effettuiamo un Entrance Test scritto + colloquio orale individuale per ciascun dipendente, completamente gratuito. Forniamo poi un report aggregato con la distribuzione CEFR del team e suggerimenti di clusterizzazione.</p>'],
        ['q' => 'Posso usare la Carta del Docente o fondi formativi?', 'a' => '<p>Per docenti scolastici sì, la Carta del Docente è utilizzabile direttamente. Per aziende lavoriamo con i principali fondi interprofessionali (Fondimpresa, Fondoprofessioni). Ti aiutiamo nella preparazione della documentazione necessaria.</p>'],
        ['q' => 'Quali aziende avete già formato?', 'a' => '<p>Tra i nostri clienti: <strong>MEF</strong> (Ministero dell\'Economia e delle Finanze), <strong>Confcommercio</strong>, <strong>H10 Hotels</strong>, oltre a numerosi studi professionali, PMI, scuole pubbliche e private, università ed enti pubblici nazionali e locali.</p>'],
    ]"
/>

{{-- CTA --}}
<section class="cta-band">
    <div class="c cta-band-inner">
        <div class="label">Prossimo step</div>
        <h2>Chiedi un preventivo aziendale</h2>
        <p>Compila il modulo o chiamaci al 06 5743734. Analizziamo i fabbisogni del tuo team e prepariamo una proposta su misura entro 48 ore.</p>
        <div class="cta-actions">
            <a href="{{ route('contattaci') }}" class="btn-gold">Richiedi preventivo →</a>
            <a href="tel:+39065743734" class="btn-outline-white">📞 06 5743734</a>
        </div>
    </div>
</section>

@endsection
