@extends('public.layout')

{{--
    Test di livello — pagina quiz per singola lingua (/test-di-{lingua}).
    Ricrea (stessi slug) le pagine test del vecchio sito WordPress che
    intercettavano ricerche tipo "test di inglese online".
    Domande: config/level_tests.php · Testi/meta: pannello Contenuti sito
    (pagina "test-livello"). Punteggio calcolato solo nel browser: nessun
    dato dell'utente viene inviato o salvato.
--}}

@php
    $cms = $test['cms'];
    $questions = $test['questions'];
@endphp

@section('title', \App\Models\PageContent::text('test-livello', $cms . '_meta_title'))
@section('description', \App\Models\PageContent::text('test-livello', $cms . '_meta_description'))
@section('keywords', 'test di ' . strtolower($test['name']) . ', test di ' . strtolower($test['name']) . ' online, test di livello ' . strtolower($test['name']) . ', test ' . strtolower($test['name']) . ' gratuito, quiz ' . strtolower($test['name']) . ' CEFR, livello ' . strtolower($test['name']) . ' A1 A2 B1 B2 C1, scuola di lingue Roma')
@section('og-image-alt', 'Test di ' . $test['name'] . ' online gratuito — A&A Language Center Roma')

@section('breadcrumb-jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ route('home') }}" },
        { "@@type": "ListItem", "position": 2, "name": "Test sul livello di lingua", "item": "{{ route('test.livello') }}" },
        { "@@type": "ListItem", "position": 3, "name": "Test di {{ $test['name'] }}", "item": "{{ route('test.lingua', $lingua) }}" }
    ]
}
</script>
@endsection

@push('styles')
<style>
/* ── PAGINA: TEST DI LIVELLO (QUIZ) ─────────────────── */
.quiz-progress {
    position: sticky; top: 0; z-index: 40;
    background: var(--white); border-bottom: 1.5px solid var(--border);
    padding: 10px 0; font-size: .82rem; color: var(--muted);
}
.quiz-progress .c { display: flex; align-items: center; gap: 14px; }
.quiz-bar { flex: 1; height: 8px; background: var(--border); border-radius: 99px; overflow: hidden; }
.quiz-bar span { display: block; height: 100%; width: 0; background: linear-gradient(90deg, var(--blue), var(--gold)); border-radius: 99px; transition: width .3s; }

.quiz-list { max-width: 760px; margin: 0 auto; display: grid; gap: 18px; }
.quiz-q {
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: var(--radius-lg); padding: 22px 24px;
    transition: border-color .25s, box-shadow .25s;
}
.quiz-q.answered { border-color: var(--blue); }
.quiz-q.missing { border-color: #d9534f; box-shadow: 0 0 0 3px rgba(217,83,79,.12); }
.quiz-q-num { font-size: .72rem; font-weight: 800; letter-spacing: .06em; color: var(--gold-d, #a07800); margin-bottom: 6px; text-transform: uppercase; }
.quiz-q-text { font-size: 1.02rem; font-weight: 700; color: var(--navy); margin-bottom: 14px; line-height: 1.5; }
.quiz-opts { display: grid; gap: 8px; }
.quiz-opt {
    display: flex; align-items: center; gap: 10px;
    border: 1.5px solid var(--border); border-radius: var(--radius);
    padding: 10px 14px; cursor: pointer; font-size: .95rem;
    transition: border-color .2s, background .2s;
}
.quiz-opt:hover { border-color: var(--blue); background: var(--blue-l, #eef4ff); }
.quiz-opt input { accent-color: var(--blue); width: 17px; height: 17px; flex: none; }
.quiz-opt.sel { border-color: var(--blue); background: var(--blue-l, #eef4ff); font-weight: 600; }

.quiz-actions { max-width: 760px; margin: 26px auto 0; text-align: center; }
.quiz-warn { display: none; color: #d9534f; font-size: .9rem; font-weight: 600; margin-bottom: 14px; }
.btn-quiz {
    display: inline-block; background: var(--gold); color: var(--navy);
    font-weight: 800; font-size: 1rem; border: none; cursor: pointer;
    padding: 15px 38px; border-radius: 50px; transition: transform .2s, box-shadow .2s;
}
.btn-quiz:hover { transform: translateY(-2px); box-shadow: var(--shadow); }

/* Risultato */
.quiz-result {
    display: none; max-width: 760px; margin: 34px auto 0;
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
    color: #fff; border-radius: var(--radius-lg); padding: 38px 34px; text-align: center;
}
.quiz-result.show { display: block; animation: qrIn .45s ease; }
@keyframes qrIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
.quiz-result-label { font-size: .72rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: var(--gold); margin-bottom: 10px; }
.quiz-result-level { font-size: clamp(2rem, 5vw, 2.8rem); font-weight: 800; margin-bottom: 6px; }
.quiz-result-score { font-size: .95rem; color: rgba(255,255,255,.75); margin-bottom: 16px; }
.quiz-result-text { font-size: .98rem; color: rgba(255,255,255,.85); line-height: 1.7; max-width: 560px; margin: 0 auto 24px; }
.quiz-result-note { font-size: .8rem; color: rgba(255,255,255,.55); margin-top: 20px; }
.quiz-result .cta-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
.quiz-retry { background: none; border: none; color: rgba(255,255,255,.65); cursor: pointer; font-size: .85rem; text-decoration: underline; margin-top: 16px; }
.quiz-retry:hover { color: #fff; }

/* Come funziona */
.quiz-steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; max-width: 860px; margin: 0 auto; }
.quiz-step { background: var(--white); border: 1.5px solid var(--border); border-radius: var(--radius-lg); padding: 22px 20px; text-align: center; }
.quiz-step .n { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 50%; background: var(--gold); color: var(--navy); font-weight: 800; margin-bottom: 10px; }
.quiz-step h3 { font-size: .95rem; font-weight: 700; color: var(--navy); margin-bottom: 6px; }
.quiz-step p { font-size: .84rem; color: var(--muted); line-height: 1.55; margin: 0; }
@media (max-width: 720px) { .quiz-steps { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')

{{-- HERO --}}
<section class="page-hero">
    <div class="c page-hero-inner">
        <div class="breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span class="sep">›</span>
            <a href="{{ route('test.livello') }}">Test sul livello di lingua</a>
            <span class="sep">›</span>
            <span>Test di {{ $test['name'] }}</span>
        </div>
        <h1>{{ $test['flag'] }} Test di <em>{{ $test['name'] }}</em></h1>
        <p class="subtitle">{{ \App\Models\PageContent::text('test-livello', $cms . '_intro') }}</p>
    </div>
</section>

{{-- COME FUNZIONA --}}
<section class="sec">
    <div class="c">
        <div class="quiz-steps">
            <div class="quiz-step">
                <span class="n">1</span>
                <h3>{{ count($questions) }} domande</h3>
                <p>A scelta multipla, in ordine di difficoltà crescente dall'A1 al C1. Bastano 5–10 minuti.</p>
            </div>
            <div class="quiz-step">
                <span class="n">2</span>
                <h3>Risultato immediato</h3>
                <p>Una stima del tuo livello CEFR, subito e senza registrazione. Nessun dato viene inviato o salvato.</p>
            </div>
            <div class="quiz-step">
                <span class="n">3</span>
                <h3>Colloquio gratuito</h3>
                <p>Fissa l'Entrance Test completo (scritto + orale) con un docente qualificato per il livello esatto.</p>
            </div>
        </div>
    </div>
</section>

{{-- QUIZ --}}
<section class="sec sec-bg" id="quiz">
    <div class="quiz-progress" id="quizProgress" aria-hidden="true">
        <div class="c">
            <span id="quizCount">0/{{ count($questions) }}</span>
            <div class="quiz-bar"><span id="quizBarFill"></span></div>
        </div>
    </div>
    <div class="c" style="padding-top:28px;">
        <noscript>
            <p style="text-align:center;max-width:640px;margin:0 auto 26px;">
                Per svolgere il test online è necessario JavaScript. In alternativa
                <a href="{{ route('iscrizione') }}">prenota direttamente l'Entrance Test gratuito</a>
                nella nostra sede: prova scritta + colloquio orale con un docente qualificato.
            </p>
        </noscript>

        <form id="quizForm" class="quiz-list" autocomplete="off">
            @foreach($questions as $i => $q)
                <fieldset class="quiz-q" data-q="{{ $i }}" data-level="{{ $q['level'] }}">
                    <legend class="quiz-q-num">Domanda {{ $i + 1 }} di {{ count($questions) }}</legend>
                    <div class="quiz-q-text">{{ $q['q'] }}</div>
                    <div class="quiz-opts">
                        @foreach($q['options'] as $j => $opt)
                            <label class="quiz-opt">
                                <input type="radio" name="q{{ $i }}" value="{{ $j }}">
                                <span>{{ $opt }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach
        </form>

        <div class="quiz-actions">
            <p class="quiz-warn" id="quizWarn"></p>
            <button type="button" class="btn-quiz" id="quizSubmit">Vedi il risultato →</button>
        </div>

        {{-- RISULTATO --}}
        <div class="quiz-result" id="quizResult" role="status" aria-live="polite">
            <div class="quiz-result-label">Il tuo livello stimato di {{ strtolower($test['name']) }}</div>
            <div class="quiz-result-level" id="quizLevel"></div>
            <div class="quiz-result-score" id="quizScore"></div>
            <p class="quiz-result-text" id="quizAdvice"></p>
            <div class="cta-actions">
                <a href="{{ route('iscrizione') }}" class="btn-gold">Prenota il colloquio gratuito →</a>
                @if(!empty($test['landing_route']) && Route::has($test['landing_route']))
                    <a href="{{ route($test['landing_route']) }}" class="btn-outline-white">{{ $test['landing_label'] }}</a>
                @else
                    <a href="{{ route('checkout.catalogo') }}" class="btn-outline-white">{{ $test['landing_label'] }}</a>
                @endif
            </div>
            <p class="quiz-result-note">Risultato indicativo, basato su sole competenze grammaticali e lessicali.
            Il livello effettivo si determina con l'Entrance Test completo (scritto + colloquio orale), gratuito e senza impegno.</p>
            <button type="button" class="quiz-retry" id="quizRetry">Ricomincia il test</button>
        </div>
    </div>
</section>

{{-- ALTRE LINGUE --}}
<section class="sec">
    <div class="c" style="text-align:center;">
        <p style="color:var(--muted);font-size:.92rem;">
            Vuoi testare un'altra lingua?
            <a href="{{ route('test.livello') }}" style="color:var(--blue);font-weight:600;">Tutti i test di livello →</a>
        </p>
    </div>
</section>

{{-- CTA --}}
<section class="cta-band">
    <div class="c cta-band-inner">
        <div class="label">{{ \App\Models\PageContent::text('test-livello', 'cta_label') }}</div>
        <h2>{{ \App\Models\PageContent::text('test-livello', 'cta_title') }}</h2>
        <p>{{ \App\Models\PageContent::text('test-livello', 'cta_text') }}</p>
        <div class="cta-actions">
            <a href="{{ route('iscrizione') }}" class="btn-gold">Prenota ora — è gratuito →</a>
            <a href="{{ route('contattaci') }}" class="btn-outline-white">Contattaci</a>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // Risposte corrette e fascia CEFR di ogni domanda (indici 0-based).
    var ANSWERS = @json(array_column($questions, 'answer'));
    var LEVELS  = @json(array_column($questions, 'level'));
    var BANDS   = ['A1', 'A2', 'B1', 'B2', 'C1'];
    var TOTAL   = ANSWERS.length;

    var form    = document.getElementById('quizForm');
    var warnEl  = document.getElementById('quizWarn');
    var result  = document.getElementById('quizResult');
    var submit  = document.getElementById('quizSubmit');

    // Descrizioni per livello stimato.
    var OUTCOMES = {
        'pre': { level: 'Principiante', advice: "Stai muovendo i primi passi: è il momento perfetto per iniziare. Con un corso base costruisci subito fondamenta solide — e con il metodo giusto i progressi arrivano in fretta." },
        'A1':  { level: 'A1 — Base', advice: "Conosci le strutture essenziali. Un corso di livello A2 ti darà l'autonomia per cavartela nelle situazioni di tutti i giorni." },
        'A2':  { level: 'A2 — Elementare', advice: "Ti esprimi su argomenti quotidiani. Il prossimo traguardo è il B1: conversazioni autonome, viaggi senza pensieri e le prime certificazioni." },
        'B1':  { level: 'B1 — Intermedio', advice: "Hai una buona autonomia. Con un corso B2 porti la lingua a livello professionale — il livello richiesto da università e concorsi." },
        'B2':  { level: 'B2 — Avanzato', advice: "Ottimo livello: lavori e studi nella lingua. Un percorso C1 e una certificazione internazionale (Trinity, IELTS, Cambridge…) mettono nero su bianco la tua competenza." },
        'C1':  { level: 'C1 — Padronanza', advice: "Complimenti, hai una padronanza notevole — forse anche da C2! Verifichiamolo insieme: una certificazione di livello avanzato è il modo migliore per valorizzarla." }
    };

    function answered() {
        var n = 0;
        for (var i = 0; i < TOTAL; i++) {
            if (form.querySelector('input[name="q' + i + '"]:checked')) n++;
        }
        return n;
    }

    function updateProgress() {
        var n = answered();
        document.getElementById('quizCount').textContent = n + '/' + TOTAL;
        document.getElementById('quizBarFill').style.width = (n / TOTAL * 100) + '%';
    }

    // Evidenzia la selezione + progress bar.
    form.addEventListener('change', function (e) {
        if (e.target.type !== 'radio') return;
        var q = e.target.closest('.quiz-q');
        q.classList.add('answered');
        q.classList.remove('missing');
        q.querySelectorAll('.quiz-opt').forEach(function (l) { l.classList.remove('sel'); });
        e.target.closest('.quiz-opt').classList.add('sel');
        warnEl.style.display = 'none';
        updateProgress();
    });

    submit.addEventListener('click', function () {
        // Tutte le domande devono avere una risposta.
        var missing = [];
        for (var i = 0; i < TOTAL; i++) {
            if (!form.querySelector('input[name="q' + i + '"]:checked')) missing.push(i);
        }
        if (missing.length) {
            missing.forEach(function (i) {
                form.querySelector('.quiz-q[data-q="' + i + '"]').classList.add('missing');
            });
            warnEl.textContent = missing.length === 1
                ? 'Manca una risposta: completa la domanda evidenziata.'
                : 'Mancano ' + missing.length + ' risposte: completa le domande evidenziate.';
            warnEl.style.display = 'block';
            form.querySelector('.quiz-q[data-q="' + missing[0] + '"]')
                .scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        // Conteggio corrette per fascia CEFR.
        var perBand = {}, correct = 0;
        BANDS.forEach(function (b) { perBand[b] = 0; });
        for (var i = 0; i < TOTAL; i++) {
            var sel = form.querySelector('input[name="q' + i + '"]:checked');
            if (parseInt(sel.value, 10) === ANSWERS[i]) {
                correct++;
                perBand[LEVELS[i]]++;
            }
        }

        // Livello = ultima fascia consecutiva "superata" (≥3 corrette su 4)
        // partendo dall'A1. Resiste meglio alle risposte casuali di una
        // semplice soglia sul totale.
        var estimated = 'pre';
        for (var b = 0; b < BANDS.length; b++) {
            if (perBand[BANDS[b]] >= 3) { estimated = BANDS[b]; } else { break; }
        }

        var out = OUTCOMES[estimated];
        document.getElementById('quizLevel').textContent  = out.level;
        document.getElementById('quizScore').textContent  = correct + ' risposte corrette su ' + TOTAL;
        document.getElementById('quizAdvice').textContent = out.advice;
        result.classList.add('show');
        submit.style.display = 'none';
        result.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    document.getElementById('quizRetry').addEventListener('click', function () {
        form.reset();
        form.querySelectorAll('.quiz-q').forEach(function (q) { q.classList.remove('answered', 'missing'); });
        form.querySelectorAll('.quiz-opt').forEach(function (l) { l.classList.remove('sel'); });
        result.classList.remove('show');
        submit.style.display = '';
        updateProgress();
        document.getElementById('quiz').scrollIntoView({ behavior: 'smooth' });
    });

    updateProgress();
})();
</script>
@endpush
