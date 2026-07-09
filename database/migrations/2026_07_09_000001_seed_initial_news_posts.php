<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed iniziale del modulo News — migrazione SEO dal vecchio sito WordPress
 * ─────────────────────────────────────────────────────────────────────────────
 * Ricrea i contenuti del vecchio blog che portavano traffico (vedi
 * docs/seo-migrazione-dominio.md, Fase 4): Trinity 2026, certificazioni MIM
 * e bonus cultura (aggiornato a Carta della Cultura Giovani / Carta del
 * Merito 2026). I testi sono ripresi dall'export WordPress del 09/07/2026
 * e ripuliti/aggiornati.
 *
 * I redirect 301 dei vecchi slug puntano già a queste URL
 * (routes/redirects.php) — NON cambiare gli slug senza aggiornarli.
 *
 * Migration usata come seeder perché in produzione (cPanel, no SSH) le
 * migration girano comunque via run_migrate.php. Idempotente: se uno slug
 * esiste già (o la tabella non c'è ancora) non tocca nulla, quindi le
 * modifiche fatte poi dallo staff dal pannello News non vengono sovrascritte.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('news_posts')) {
            return; // la create_news_posts_table gira prima; guardia extra
        }

        foreach ($this->posts() as $post) {
            $exists = DB::table('news_posts')->where('slug', $post['slug'])->exists();
            if ($exists) {
                continue;
            }

            DB::table('news_posts')->insert($post + [
                'type'         => 'news',
                'is_published' => true,
                'user_id'      => null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('news_posts')) {
            return;
        }

        DB::table('news_posts')
            ->whereIn('slug', array_column($this->posts(), 'slug'))
            ->delete();
    }

    /**
     * @return array<int,array{title:string,slug:string,excerpt:string,body:string,published_at:string}>
     */
    private function posts(): array
    {
        return [
            [
                'title'        => 'Esami Trinity College London 2026: calendario e iscrizioni',
                'slug'         => 'esami-trinity-college-2026',
                'published_at' => '2026-07-09 09:00:00',
                'excerpt'      => "A&A Language Center è Sede d'Esame ufficiale Trinity College London n° 8241: il calendario delle sessioni GESE e ISE 2026 e come iscriverti alla prossima sessione direttamente nella nostra sede di Roma San Paolo.",
                'body'         => <<<'HTML'
<p>Presso <strong>A&amp;A Language Center</strong> — <strong>Sede d'Esame ufficiale Trinity College London n° 8241</strong> — si tengono durante tutto l'anno le sessioni di esame per il conseguimento delle certificazioni <strong>GESE</strong> (Graded Examinations in Spoken English) e <strong>ISE</strong> (Integrated Skills in English), per tutti i livelli <strong>A1–C2</strong> del Quadro Comune Europeo di Riferimento (QCER).</p>

<h2>Il calendario 2026</h2>
<p>Le sessioni della prima parte dell'anno si sono svolte regolarmente nella nostra sede di Roma San Paolo, anche in modalità videoconferenza:</p>
<ul>
    <li><strong>GESE — sessione Spring 2026:</strong> 16–29 marzo 2026 (iscrizioni chiuse l'11 febbraio)</li>
    <li><strong>GESE — sessione Summer 2026:</strong> 30 giugno – 12 luglio 2026 (iscrizioni chiuse il 27 maggio)</li>
    <li><strong>ISE Reading &amp; Writing:</strong> 11 marzo 2026 e 17 giugno 2026</li>
</ul>
<p><strong>Prossimo appuntamento: la sessione Autunno 2026.</strong> Le date esatte e i termini di iscrizione sono in via di pubblicazione: <a href="/contattaci">contatta la segreteria</a> (tel. 06 5743734) per prenotare il tuo posto ed essere avvisato appena il calendario è ufficiale. Le iscrizioni vanno effettuate con largo anticipo rispetto alla data d'esame.</p>

<h2>GESE o ISE: quale fa per te?</h2>
<p>Il <strong>GESE</strong> valuta le abilità orali (Speaking &amp; Listening) in un colloquio one-to-one con un esaminatore madrelingua: ideale per bambini, ragazzi e per chi vuole certificare la competenza comunicativa. L'<strong>ISE</strong> valuta in modo integrato le quattro abilità nei due moduli Speaking &amp; Listening e Reading &amp; Writing — il modulo orale si può sostenere anche online in videoconferenza. L'<strong>ISE di livello B2</strong> è particolarmente richiesto per i <strong>concorsi pubblici</strong>.</p>

<h2>Perché una certificazione Trinity</h2>
<ul>
    <li>Certificazione riconosciuta da università, aziende e istituzioni governative in Italia e nel mondo</li>
    <li>Risultati distinti per ciascuna abilità e livelli graduati dal QCER</li>
    <li>Circa <strong>3.000 corsi di laurea in Italia</strong> riconoscono le certificazioni ISE in 4 abilità, e oltre 450 riconoscono anche le GESE</li>
    <li>Punteggio aggiuntivo in graduatorie e concorsi pubblici</li>
    <li>Task d'esame che riflettono la comunicazione della vita reale: interagire in modo efficace, scrivere in risposta a una domanda, sintetizzare idee da fonti diverse</li>
</ul>
<p>Le quote d'esame variano in base al livello e alla tipologia (indicativamente da €49 a €250).</p>

<h2>Preparati con noi, sostieni l'esame da noi</h2>
<p>Con i nostri <a href="/corsi-inglese-roma">corsi di inglese</a> ti prepari all'esame con docenti qualificati e poi lo sostieni <strong>nello stesso luogo dove hai studiato</strong>, senza stress. Non sai da che livello partire? Fai il <a href="/test-di-inglese">test di inglese online gratuito</a> o prenota un <a href="/iscriviti">Entrance Test in sede</a>.</p>
<p>Scopri di più sulle <a href="/le-certificazioni">certificazioni Trinity College London</a> nella nostra scuola.</p>
HTML,
            ],
            [
                'title'        => 'Certificazioni di lingua inglese riconosciute dal MIM: perché scegliere Trinity',
                'slug'         => 'certificazioni-lingua-inglese-riconosciute-dal-mim',
                'published_at' => '2026-07-09 09:30:00',
                'excerpt'      => "Con il Decreto Dipartimentale n. 2813 del 21 novembre 2024 il Ministero dell'Istruzione e del Merito ha confermato Trinity College London tra gli enti certificatori riconosciuti. Cosa significa per università, concorsi e graduatorie — e come sostenere l'esame nella nostra sede.",
                'body'         => <<<'HTML'
<p>Con il <a href="https://www.mim.gov.it/web/guest/-/decreto-dipartimentale-n-2813-del-21-novembre-2024" rel="noopener" target="_blank">Decreto Dipartimentale n. 2813 del 21 novembre 2024</a>, il <strong>Ministero dell'Istruzione e del Merito (MIM)</strong> ha confermato <strong>Trinity College London</strong> come ente qualificato per il rilascio delle certificazioni linguistiche in lingua inglese.</p>
<p>A&amp;A Language Center è <strong>Sede d'Esame ufficiale Trinity College London n° 8241</strong>: puoi <strong>prepararti e sostenere l'esame nello stesso luogo</strong>, con docenti qualificati e supporto dedicato in ogni fase del percorso.</p>

<h2>La chiave per studiare, lavorare e crescere nel mondo</h2>
<p>Con <strong>Trinity ISE (Integrated Skills in English)</strong> dimostri le tue reali competenze comunicative in inglese e ottieni una <strong>certificazione ufficiale valida per sempre</strong>, riconosciuta in Italia e in oltre 49 Paesi del mondo.</p>
<p>Trinity ISE è tra i <strong>cinque esami ufficialmente riconosciuti dal MIM</strong> per la certificazione delle competenze linguistiche: l'inclusione tra gli enti certificatori riconosciuti garantisce la piena spendibilità del titolo in ambito scolastico, accademico e professionale.</p>

<h2>Per l'università in Italia</h2>
<ul>
    <li>Crediti universitari in <strong>oltre 3.500 corsi di laurea</strong> italiani</li>
    <li>Sostituzione del test di idoneità linguistica richiesto da molti atenei</li>
    <li>Titolo valido come requisito di accesso a corsi universitari e bandi pubblici</li>
</ul>

<h2>Per studiare all'estero</h2>
<p>Le certificazioni Trinity sono riconosciute da <strong>più di 4.500 università e istituzioni in 49 Paesi</strong>, tra cui Oxford, Cambridge, Harvard e Chicago: un'opportunità concreta per chi desidera un percorso di studi o di carriera internazionale.</p>

<h2>Un vantaggio nel mondo del lavoro</h2>
<ul>
    <li>Certifica competenze linguistiche reali, utili in contesti globali</li>
    <li><strong>Punteggio aggiuntivo nei concorsi pubblici</strong> e nelle graduatorie docenti (GPS)</li>
    <li>Riconosciuto come titolo linguistico valido in Italia e all'estero</li>
</ul>

<h2>Come funziona l'esame ISE</h2>
<p>Gli esami Trinity ISE valutano in modo integrato le quattro abilità attraverso i due moduli <strong>Speaking &amp; Listening</strong> e <strong>Reading &amp; Writing</strong>, che possono essere sostenuti anche in momenti separati; il modulo Speaking &amp; Listening anche <strong>online in videoconferenza</strong>. Sono previsti tutti i livelli: <strong>ISE A1, ISE F (A2), ISE I (B1), ISE II (B2), ISE III (C1) e ISE IV (C2)</strong>.</p>

<h2>Sostieni il tuo esame Trinity nella nostra scuola</h2>
<p>Le sessioni d'esame sono organizzate durante tutto l'anno nella nostra sede di Roma San Paolo — consulta il <a href="/news/esami-trinity-college-2026">calendario esami Trinity 2026</a>. Per informazioni o per prenotare il tuo esame ISE: <a href="/contattaci">contattaci</a> oppure scrivi a info@aealanguagecenter.it.</p>
<p>Non conosci il tuo livello? Inizia dal <a href="/test-di-inglese">test di inglese online gratuito</a> e poi fissa un colloquio con i nostri docenti. Scopri anche i <a href="/corsi-inglese-roma">corsi di preparazione</a> e la pagina <a href="/le-certificazioni">Le certificazioni</a>.</p>
HTML,
            ],
            [
                'title'        => 'Carta della Cultura Giovani e Carta del Merito: 500 € da spendere anche in corsi di lingua',
                'slug'         => 'carta-cultura-giovani-e-carta-del-merito',
                'published_at' => '2026-07-09 10:00:00',
                'excerpt'      => "I due bonus da 500 € (cumulabili fino a 1.000 €) che hanno sostituito 18app si possono usare anche per i corsi di lingua straniera. Chi li ha richiesti entro il 30 giugno 2026 può spenderli fino al 31 dicembre 2026 — anche per un corso in A&A Language Center.",
                'body'         => <<<'HTML'
<p>La <strong>Carta della Cultura Giovani</strong> e la <strong>Carta del Merito</strong> — i due bonus che hanno sostituito la vecchia 18app / Bonus Cultura — valgono <strong>500 euro ciascuna, sono cumulabili tra loro</strong> (fino a 1.000 euro) e si possono spendere anche per i <strong>corsi di lingua straniera</strong>.</p>

<h2>A chi spettano</h2>
<ul>
    <li><strong>Carta della Cultura Giovani:</strong> ai residenti in Italia (con permesso di soggiorno ove previsto) <strong>nell'anno successivo al compimento dei 18 anni</strong> — per il 2026, i nati nel 2007 — appartenenti a nuclei familiari con <strong>ISEE non superiore a 35.000 euro</strong>.</li>
    <li><strong>Carta del Merito:</strong> a chi ha conseguito il diploma, non oltre l'anno del diciannovesimo compleanno, con votazione di <strong>100 o 100 e lode</strong>. È assegnata e utilizzabile nell'anno successivo al diploma.</li>
</ul>

<h2>Scadenze da segnare</h2>
<p>Per il 2026 la registrazione sulla piattaforma ministeriale <a href="https://cartegiovani.cultura.gov.it/" rel="noopener" target="_blank">Carte Giovani</a> (con SPID o CIE) era possibile <strong>dal 31 gennaio al 30 giugno 2026</strong>. Se hai richiesto le carte entro quella data, puoi <strong>spendere i buoni fino al 31 dicembre 2026</strong>.</p>
<p>Sei nato nel 2008, o ti diplomi nel 2026 con 100? La prossima finestra di registrazione aprirà a <strong>gennaio 2027</strong>: tieni d'occhio il sito ufficiale.</p>

<h2>Come si usano per un corso di lingue</h2>
<p>Con l'attivazione delle carte non ricevi denaro direttamente: hai un <strong>portafoglio virtuale</strong> da cui generare <strong>buoni di spesa elettronici</strong> dell'importo necessario, da utilizzare nelle strutture aderenti, fisiche oppure online.</p>
<p>Vuoi investire il tuo bonus in un <strong>corso di lingua</strong> o nella preparazione di una <strong>certificazione internazionale</strong> (Trinity, Cambridge, IELTS…)? <a href="/contattaci">Contatta la segreteria</a> (tel. 06 5743734): ti guidiamo nella generazione del buono e nella scelta del percorso più adatto. Una certificazione riconosciuta vale <a href="/news/certificazioni-lingua-inglese-riconosciute-dal-mim">crediti universitari e punteggio nei concorsi</a> — un ottimo modo per trasformare il bonus in un investimento sul tuo futuro.</p>
<p>Non sai da che livello partire? Fai subito il <a href="/test-sul-livello-di-lingua">test di livello online gratuito</a>.</p>
HTML,
            ],
        ];
    }
};
