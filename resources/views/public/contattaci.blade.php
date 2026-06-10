@extends('public.layout')

@section('title', 'Contatti A&A Language Center — Scuola di Lingue Roma San Paolo')
@section('description', 'Contatti A&A Language Center, scuola di lingue a Roma San Paolo: Viale Leonardo da Vinci 193, 00145 Roma. Tel 06 5743734, info@aealanguagecenter.it. Lun–Ven 9–20, Sab 10–14.')
@section('keywords', 'A&A Language Center contatti, scuola di lingue Roma San Paolo indirizzo, scuola di lingue Viale Leonardo da Vinci, scuola di lingue vicino metro San Paolo, telefono scuola di lingue Roma')

@section('breadcrumb-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ route('home') }}" },
        { "@@type": "ListItem", "position": 2, "name": "Contatti", "item": "{{ route('contattaci') }}" }
    ]
}
</script>
@endsection

@push('styles')
<style>
/* ── PAGINA: CONTATTACI ─────────────────────── */

.contact-layout {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 56px; align-items: start;
}

/* LEFT: INFO */
.contact-block { display: flex; flex-direction: column; gap: 20px; margin: 28px 0; }
.contact-row   { display: flex; gap: 14px; align-items: flex-start; }
.contact-row-icon {
    width: 44px; height: 44px; border-radius: var(--radius);
    background: var(--bg); border: 1.5px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0;
}
.contact-row-label {
    font-size: .7rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .07em; color: var(--muted); margin-bottom: 3px;
}
.contact-row-value { font-size: .925rem; }
.contact-row-value a { color: var(--text); transition: color .2s; }
.contact-row-value a:hover { color: var(--blue); }

.social-row { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 6px; }
.social-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 50px;
    font-size: .78rem; font-weight: 700; border: 1.5px solid; transition: all .2s;
    text-decoration: none;
}
.social-fb  { color: #1877f2; border-color: #1877f2; }
.social-fb:hover { background: #1877f2; color: #fff; }
.social-ig  { color: #e1306c; border-color: #e1306c; }
.social-ig:hover { background: #e1306c; color: #fff; }
.social-tw  { color: var(--text); border-color: var(--text); }
.social-tw:hover { background: var(--text); color: #fff; }

/* Orari box */
.orari-box {
    background: var(--bg); border: 1.5px solid var(--border);
    border-radius: var(--radius-lg); padding: 24px 22px;
}
.orari-box-title {
    font-size: .7rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .07em; color: var(--muted); margin-bottom: 16px;
}
.orari-row {
    display: flex; justify-content: space-between;
    padding: 10px 0; border-bottom: 1px solid var(--border);
    font-size: .875rem;
}
.orari-row:last-child { border-bottom: none; }
.orari-row .day { color: var(--muted); }
.orari-row .time { font-weight: 700; color: var(--blue); }
.orari-row .closed { font-weight: 700; color: #aaa; }

/* Map */
.map-box {
    border-radius: var(--radius-lg); overflow: hidden;
    border: 1.5px solid var(--border); height: 240px; margin-top: 22px;
}
.map-box iframe { width: 100%; height: 100%; border: none; display: block; }

/* RIGHT: FORM BOX */
.form-box {
    background: var(--bg); border: 1.5px solid var(--border);
    border-radius: var(--radius-lg); padding: 36px 32px;
    position: sticky; top: 96px;
}
.form-box h3 { font-size: 1.3rem; font-weight: 800; margin-bottom: 6px; color: var(--navy); }
.form-box > p { font-size: .875rem; color: var(--muted); line-height: 1.65; margin-bottom: 24px; }

.form-highlights { display: flex; flex-direction: column; gap: 12px; margin-bottom: 26px; }
.form-highlight {
    display: flex; gap: 12px; align-items: flex-start;
    padding: 14px 16px; background: var(--white);
    border: 1.5px solid var(--border); border-radius: var(--radius);
}
.form-highlight-icon { font-size: 1.25rem; flex-shrink: 0; }
.form-highlight h4 { font-size: .875rem; font-weight: 700; margin: 0 0 3px; color: var(--navy); }
.form-highlight p { font-size: .8rem; color: var(--muted); line-height: 1.5; margin: 0; }

.form-cta-btn {
    display: flex; width: 100%; align-items: center; justify-content: center;
    gap: 8px; min-height: 50px; padding: 0 24px;
    border-radius: var(--radius); font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: .925rem; font-weight: 700;
    background: var(--blue); color: #fff; border: none;
    transition: all .2s; text-decoration: none; cursor: pointer;
}
.form-cta-btn:hover { background: var(--blue-h); transform: translateY(-2px); box-shadow: 0 12px 28px rgba(26,86,219,.3); }
.form-note { text-align: center; font-size: .78rem; color: var(--muted); margin-top: 12px; line-height: 1.6; }
.form-note a { color: var(--blue); }

@media (max-width: 900px) {
    .contact-layout { grid-template-columns: 1fr; gap: 36px; }
    .form-box { position: static; }
}
@media (max-width: 640px) {
    .form-box { padding: 24px 18px; }
}
</style>
@endpush

@section('content')

{{-- PAGE HERO --}}
<section class="page-hero">
    <div class="c page-hero-inner">
        <div class="breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span class="sep">›</span>
            <span>Contattaci</span>
        </div>
        <h1><em>Contattaci</em> — A&amp;A Language Center Roma</h1>
        <p class="subtitle">Scuola di lingue a Roma San Paolo. Viale Leonardo da Vinci 193, 00145 Roma. Tel 06 5743734.</p>
    </div>
</section>

{{-- MAIN --}}
<section class="sec">
    <div class="c">
        <div class="contact-layout">

            {{-- COLONNA SX --}}
            <div>
                <div class="section-label">Dove siamo</div>
                <h2 class="sec-heading">Vieni a <em>trovarci</em></h2>
                <p class="sec-subtext">Nel cuore del quartiere <strong>San Paolo</strong> a Roma, a pochi passi da Università Roma Tre e dalle fermate metro.</p>

                <div class="contact-block">
                    <div class="contact-row">
                        <div class="contact-row-icon">📍</div>
                        <div>
                            <div class="contact-row-label">Indirizzo</div>
                            <div class="contact-row-value">
                                <a href="https://maps.google.com/?q=Viale+Leonardo+da+Vinci,+193,+00145+Roma" target="_blank" rel="noopener">
                                    Viale Leonardo da Vinci, 193<br>00145 Roma (San Paolo)
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="contact-row">
                        <div class="contact-row-icon">📞</div>
                        <div>
                            <div class="contact-row-label">Telefono</div>
                            <div class="contact-row-value"><a href="tel:+39065743734">06 5743734</a></div>
                        </div>
                    </div>
                    <div class="contact-row">
                        <div class="contact-row-icon">✉️</div>
                        <div>
                            <div class="contact-row-label">Email</div>
                            <div class="contact-row-value"><a href="mailto:info@aealanguagecenter.it">info@aealanguagecenter.it</a></div>
                        </div>
                    </div>
                    <div class="contact-row">
                        <div class="contact-row-icon">🌐</div>
                        <div>
                            <div class="contact-row-label">Social media</div>
                            <div class="contact-row-value">
                                <div class="social-row">
                                    <a href="https://www.facebook.com/aealanguagecenter.roma" target="_blank" rel="noopener" class="social-btn social-fb">Facebook</a>
                                    <a href="https://www.instagram.com/aealanguagecenter/" target="_blank" rel="noopener" class="social-btn social-ig">Instagram</a>
                                    <a href="https://twitter.com/aealanguage" target="_blank" rel="noopener" class="social-btn social-tw">Twitter / X</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="orari-box">
                    <div class="orari-box-title">Orari di apertura</div>
                    <div class="orari-row"><span class="day">Lunedì – Venerdì</span><span class="time">10:00 – 19:00</span></div>
                    <div class="orari-row"><span class="day">Sabato</span><span class="time">9:00 – 13:00</span></div>
                    <div class="orari-row"><span class="day">Domenica</span><span class="closed">Chiuso</span></div>
                </div>

                <div class="map-box">
                    <iframe
                        src="https://maps.google.com/maps?q=Viale+Leonardo+da+Vinci,+193,+00145+Roma&output=embed"
                        title="A&A Language Center — Viale Leonardo da Vinci 193, Roma"
                        loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen>
                    </iframe>
                </div>
            </div>

            {{-- COLONNA DX --}}
            <div class="form-box">
                <h3>Scrivici o prenota il test</h3>
                <p>Compila il modulo per richiedere informazioni su corsi, prezzi, orari o per prenotare il tuo test di livello gratuito. Ti risponderemo entro poche ore lavorative.</p>

                <div class="form-highlights">
                    <div class="form-highlight">
                        <div class="form-highlight-icon">🎯</div>
                        <div>
                            <h4>Test di livello gratuito</h4>
                            <p>Determiniamo il tuo livello CEFR con un test scritto e orale senza alcun costo e senza impegno.</p>
                        </div>
                    </div>
                    <div class="form-highlight">
                        <div class="form-highlight-icon">⚡</div>
                        <div>
                            <h4>Risposta rapida</h4>
                            <p>Rispondiamo a tutte le richieste entro 24 ore lavorative.</p>
                        </div>
                    </div>
                    <div class="form-highlight">
                        <div class="form-highlight-icon">💬</div>
                        <div>
                            <h4>Consulenza personalizzata</h4>
                            <p>Ti aiutiamo a scegliere il corso e la modalità più adatta ai tuoi obiettivi.</p>
                        </div>
                    </div>
                </div>

                <a href="{{ route('iscrizione') }}" class="form-cta-btn">
                    ✦ Compila il modulo di contatto
                </a>
                <p class="form-note">
                    Oppure chiamaci direttamente:<br>
                    <strong><a href="tel:+39065743734">06 5743734</a></strong>
                </p>
            </div>
        </div>
    </div>
</section>

@endsection
