@extends('public.layout')

@section('title', 'Privacy Policy — ' . config('app.name'))
@section('description', 'Informativa sul trattamento dei dati personali ai sensi del Regolamento UE 2016/679 (GDPR) — A&A Language Center, Roma.')

@push('styles')
<style>
    :root {
        --blue:      #0057d9;
        --blue-dark: #001b3f;
        --blue-deep: #001126;
        --border-f:  #dbe7f4;
    }

    /* ── OVERRIDE NAV ── */
    nav {
        background: linear-gradient(90deg, #001126, #061b3f) !important;
        border-bottom: none !important;
        height: 92px !important;
        padding: 0 max(20px, calc((100vw - 1120px) / 2)) !important;
        box-shadow: 0 8px 30px rgba(0,0,0,.18) !important;
    }
    .nav-brand { color: #fff !important; }
    .nav-brand img { height: 74px !important; }
    .nav-links a { color: rgba(255,255,255,.9) !important; font-size: 14px !important; font-weight: 700 !important; }
    .nav-links a:hover { color: #49a1ff !important; }
    .nav-links .btn-primary {
        background: #0069f2 !important;
        color: #fff !important;
        font-size: 14px !important;
        font-weight: 800 !important;
        padding: 18px 28px !important;
        border-radius: 7px !important;
    }

    /* ── HERO ── */
    .page-hero {
        background: linear-gradient(110deg, #001126 0%, #003580 55%, #0057d9 100%);
        color: #fff;
        padding: 3.5rem 1.5rem 3rem;
        text-align: center;
    }
    .page-hero h1 { font-size: clamp(1.6rem, 3.5vw, 2.4rem); font-weight: 900; margin-bottom: .5rem; letter-spacing: -.5px; }
    .page-hero p  { color: #ccdcf7; font-size: .95rem; }

    /* ── DOCUMENT ── */
    .privacy-body {
        background: #f6faff;
        padding: 3rem 1.5rem 4rem;
    }
    .privacy-doc {
        max-width: 820px;
        margin: 0 auto;
        background: #fff;
        border-radius: 16px;
        border: 1.5px solid var(--border-f);
        padding: 3rem 3.5rem;
        box-shadow: 0 8px 30px rgba(0,37,91,.08);
        font-size: .93rem;
        line-height: 1.75;
        color: #18243a;
    }
    .privacy-doc h2 {
        font-size: 1.1rem;
        font-weight: 800;
        color: #001b3f;
        margin: 2.2rem 0 .6rem;
        padding-bottom: .4rem;
        border-bottom: 2px solid #dbe7f4;
    }
    .privacy-doc h2:first-child { margin-top: 0; }
    .privacy-doc p  { margin: .6rem 0; }
    .privacy-doc ul { padding-left: 1.4rem; margin: .6rem 0; }
    .privacy-doc ul li { margin-bottom: .35rem; }
    .privacy-doc strong { color: #001b3f; }
    .privacy-doc a { color: var(--blue); }
    .privacy-doc .highlight-box {
        background: #eff6ff;
        border-left: 4px solid var(--blue);
        border-radius: 0 8px 8px 0;
        padding: 1rem 1.25rem;
        margin: 1rem 0;
        font-size: .88rem;
    }
    .privacy-doc .date-tag {
        display: inline-block;
        background: #f0f4ff;
        border: 1px solid #c7d7f4;
        border-radius: 6px;
        padding: .2rem .7rem;
        font-size: .78rem;
        font-weight: 700;
        color: #3456a0;
        margin-bottom: 1.5rem;
    }

    /* ── FOOTER OVERRIDE ── */
    footer {
        background: #001126 !important;
        color: #a8b4c8 !important;
        padding: 18px 2rem !important;
        margin-top: 0 !important;
    }

    @media (max-width: 640px) {
        .privacy-doc { padding: 1.75rem 1.25rem; }
    }
</style>
@endpush

@section('content')

<div class="page-hero">
    <h1>Informativa sulla Privacy</h1>
    <p>Ai sensi dell'art. 13 del Regolamento UE 2016/679 (GDPR)</p>
</div>

<div class="privacy-body">
    <div class="privacy-doc">

        <span class="date-tag">Ultimo aggiornamento: {{ date('d/m/Y') }}</span>

        <h2>1. Titolare del trattamento</h2>
        <p>
            Il titolare del trattamento dei dati personali è:<br>
            <strong>A&A Language Center</strong><br>
            Viale Leonardo da Vinci, 193 – 00145 Roma (RM)<br>
            P.IVA: 09121441001<br>
            Email: <a href="mailto:info@aealanguagecenter.it">info@aealanguagecenter.it</a><br>
            Telefono: <a href="tel:+390657437364">06 574 3734</a>
        </p>

        <h2>2. Tipologie di dati raccolti</h2>
        <p>Tramite il modulo di contatto sul sito web raccogliamo i seguenti dati personali:</p>
        <ul>
            <li><strong>Dati anagrafici:</strong> nome e cognome</li>
            <li><strong>Dati di contatto:</strong> indirizzo email e numero di telefono (facoltativo)</li>
            <li><strong>Dati relativi all'interesse formativo:</strong> lingua e corso di interesse, messaggi e note libere</li>
        </ul>
        <p>
            I dati contrassegnati come obbligatori nel modulo sono necessari per fornire il servizio richiesto.
            Il mancato conferimento di tali dati comporta l'impossibilità di elaborare la richiesta.
        </p>

        <h2>3. Finalità e base giuridica del trattamento</h2>
        <p>I dati personali sono trattati per le seguenti finalità:</p>
        <ul>
            <li>
                <strong>Gestione delle richieste di informazioni e iscrizione</strong> (art. 6, par. 1, lett. b GDPR —
                esecuzione di misure precontrattuali): per rispondere alle richieste inviate tramite il modulo
                di contatto, concordare un colloquio conoscitivo e proporre il percorso formativo più adatto.
            </li>
            <li>
                <strong>Adempimento di obblighi legali</strong> (art. 6, par. 1, lett. c GDPR): per rispettare
                gli obblighi di legge in materia fiscale, amministrativa e contabile nel caso in cui si instauri
                un rapporto contrattuale.
            </li>
            <li>
                <strong>Comunicazioni di servizio</strong> (art. 6, par. 1, lett. b GDPR): invio di email
                di conferma della richiesta ricevuta e comunicazioni relative allo stato della pratica.
            </li>
        </ul>
        <div class="highlight-box">
            <strong>Non utilizziamo i dati per finalità di marketing diretto o profilazione</strong> senza
            aver ottenuto un esplicito consenso aggiuntivo. Non vengono mai ceduti a terzi per scopi commerciali.
        </div>

        <h2>4. Modalità di trattamento e conservazione</h2>
        <p>
            I dati sono trattati con strumenti elettronici protetti da misure di sicurezza adeguate
            (accesso con credenziali, cifratura delle comunicazioni via SSL/TLS).
            Il trattamento avviene esclusivamente da parte del personale autorizzato della scuola.
        </p>
        <p>
            I dati dei <strong>contatti che non si trasformano in iscrizioni</strong> sono conservati per
            un massimo di <strong>24 mesi</strong> dalla data di ricezione della richiesta, dopodiché sono
            cancellati o anonimizzati.
        </p>
        <p>
            I dati dei <strong>clienti iscritti</strong> sono conservati per il periodo necessario
            all'esecuzione del contratto e, successivamente, per <strong>10 anni</strong> in adempimento
            degli obblighi fiscali e di legge.
        </p>

        <h2>5. Comunicazione e trasferimento dei dati</h2>
        <p>I dati personali non sono ceduti a terzi per scopi commerciali. Possono essere comunicati a:</p>
        <ul>
            <li>
                <strong>Fornitori di servizi tecnici</strong> (hosting, posta elettronica): esclusivamente
                nella misura necessaria a garantire il funzionamento del servizio, in qualità di responsabili
                del trattamento ai sensi dell'art. 28 GDPR, con server ubicati nell'Unione Europea.
            </li>
            <li>
                <strong>Autorità pubbliche</strong>: solo nei casi previsti dalla legge.
            </li>
        </ul>
        <p>Non è previsto alcun trasferimento di dati verso Paesi extra-UE.</p>

        <h2>6. Cookie e dati di navigazione</h2>
        <p>
            Il sito web utilizza esclusivamente <strong>cookie tecnici strettamente necessari</strong>
            al funzionamento del sito (es. cookie di sessione per la gestione dei moduli).
            Non vengono utilizzati cookie di profilazione o cookie di terze parti per finalità pubblicitarie.
        </p>

        <h2>7. Diritti dell'interessato</h2>
        <p>
            Ai sensi degli artt. 15–22 del GDPR, l'interessato ha il diritto di:
        </p>
        <ul>
            <li><strong>Accesso</strong>: ottenere conferma che siano in corso trattamenti di dati che lo riguardano e riceverne copia</li>
            <li><strong>Rettifica</strong>: richiedere la correzione di dati inesatti o incompleti</li>
            <li><strong>Cancellazione</strong> ("diritto all'oblio"): richiedere la cancellazione dei propri dati</li>
            <li><strong>Limitazione</strong>: richiedere la limitazione del trattamento in determinati casi</li>
            <li><strong>Portabilità</strong>: ricevere i propri dati in formato strutturato e leggibile</li>
            <li><strong>Opposizione</strong>: opporsi al trattamento in qualsiasi momento</li>
            <li><strong>Revoca del consenso</strong>: revocare il consenso prestato, senza pregiudizio per la liceità del trattamento effettuato prima della revoca</li>
        </ul>
        <p>
            Per esercitare i propri diritti è sufficiente inviare una richiesta scritta a:
            <a href="mailto:info@aealanguagecenter.it">info@aealanguagecenter.it</a>
        </p>
        <p>
            L'interessato ha inoltre il diritto di proporre reclamo all'<strong>Autorità Garante per
            la Protezione dei Dati Personali</strong> (Garante Privacy), con sede in Piazza Venezia 11,
            00187 Roma — <a href="https://www.garanteprivacy.it" target="_blank" rel="noopener">www.garanteprivacy.it</a>.
        </p>

        <h2>8. Modifiche alla presente informativa</h2>
        <p>
            Il Titolare si riserva il diritto di modificare questa informativa in qualsiasi momento,
            pubblicando la versione aggiornata sul sito. Si consiglia di consultare periodicamente
            questa pagina. La data dell'ultimo aggiornamento è indicata in cima al documento.
        </p>

    </div>
</div>

@endsection
