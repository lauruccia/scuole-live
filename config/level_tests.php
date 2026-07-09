<?php

/*
|──────────────────────────────────────────────────────────────────────────────
| Test di livello online — banca domande
|──────────────────────────────────────────────────────────────────────────────
| Alimenta le pagine /test-di-{lingua} (view public/test/quiz.blade.php).
| Queste pagine ricreano — sugli STESSI slug — le 5 pagine "test di lingua"
| del vecchio sito WordPress, che intercettavano ricerche di valore tipo
| "test di inglese online" (vedi docs/seo-migrazione-dominio.md).
|
| STRUTTURA
| - Ogni lingua ha 20 domande a scelta multipla, 4 per fascia CEFR
|   (A1, A2, B1, B2, C1), in ordine di difficoltà crescente.
| - 'answer' è l'indice (0-based) dell'opzione corretta.
| - Il punteggio è calcolato client-side (nessun dato inviato al server):
|   una fascia è "superata" con almeno 3 risposte corrette su 4; il livello
|   stimato è l'ultima fascia superata partendo dall'A1. Risultato SEMPRE
|   presentato come indicativo: la valutazione vera è l'Entrance Test in sede.
| - 'cms' è il prefisso dei campi editabili in config/site_contents.php
|   (pagina "test-livello": {cms}_meta_title, {cms}_meta_description, {cms}_intro).
| - 'landing_route' è la route della pagina corsi suggerita nel risultato.
|
| NB: alcune domande riprendono quelle storiche del vecchio sito (contenuto
| della scuola) per continuità con le pagine che già si posizionavano.
*/

return [

    /* ═══════════════════════════ INGLESE ═══════════════════════════ */
    'inglese' => [
        'name'          => 'Inglese',
        'flag'          => '🇬🇧',
        'cms'           => 'en',
        'landing_route' => 'landing.inglese',
        'landing_label' => 'Scopri i corsi di inglese',
        'questions'     => [
            // — A1 —
            ['level' => 'A1', 'q' => "I ___ from Italy.", 'options' => ['am', 'is', 'are'], 'answer' => 0],
            ['level' => 'A1', 'q' => "___ you like coffee?", 'options' => ['Do', 'Does', 'Are'], 'answer' => 0],
            ['level' => 'A1', 'q' => "This is my brother. ___ name is Marco.", 'options' => ['Her', 'His', 'Its'], 'answer' => 1],
            ['level' => 'A1', 'q' => "There ___ two books on the table.", 'options' => ['is', 'be', 'are'], 'answer' => 2],
            // — A2 —
            ['level' => 'A2', 'q' => "She ___ to the gym every Tuesday.", 'options' => ['go', 'goes', 'going'], 'answer' => 1],
            ['level' => 'A2', 'q' => "We ___ a great film last night.", 'options' => ['see', 'saw', 'seen'], 'answer' => 1],
            ['level' => 'A2', 'q' => "I'm taller ___ my sister.", 'options' => ['that', 'then', 'than'], 'answer' => 2],
            ['level' => 'A2', 'q' => "You ___ smoke in the hospital. It's forbidden.", 'options' => ["mustn't", "don't have to", "shouldn't"], 'answer' => 0],
            // — B1 —
            ['level' => 'B1', 'q' => "I've lived in Rome ___ 2015.", 'options' => ['for', 'since', 'from'], 'answer' => 1],
            ['level' => 'B1', 'q' => "If it rains tomorrow, we ___ at home.", 'options' => ['stay', 'would stay', 'will stay'], 'answer' => 2],
            ['level' => 'B1', 'q' => "The report ___ by the manager yesterday.", 'options' => ['was written', 'wrote', 'has written'], 'answer' => 0],
            ['level' => 'B1', 'q' => "She asked me where ___ .", 'options' => ['do I live', 'I lived', 'did I live'], 'answer' => 1],
            // — B2 —
            ['level' => 'B2', 'q' => "By the time we arrived, the film ___ .", 'options' => ['already started', 'has already started', 'had already started'], 'answer' => 2],
            ['level' => 'B2', 'q' => "I'd rather you ___ smoke in here.", 'options' => ["don't", "didn't", "won't"], 'answer' => 1],
            ['level' => 'B2', 'q' => "He denied ___ the money.", 'options' => ['to steal', 'stealing', 'steal'], 'answer' => 1],
            ['level' => 'B2', 'q' => "___ the bad weather, the match went ahead.", 'options' => ['Although', 'However', 'Despite'], 'answer' => 2],
            // — C1 —
            ['level' => 'C1', 'q' => "No sooner ___ the house than it started to rain.", 'options' => ['we had left', 'had we left', 'we left'], 'answer' => 1],
            ['level' => 'C1', 'q' => "The project fell ___ at the last minute because of funding cuts.", 'options' => ['out', 'off', 'through'], 'answer' => 2],
            ['level' => 'C1', 'q' => "Had I known about the meeting, I ___ .", 'options' => ['would attend', 'would have attended', 'had attended'], 'answer' => 1],
            ['level' => 'C1', 'q' => "It's high time the government ___ something about housing costs.", 'options' => ['does', 'has done', 'did'], 'answer' => 2],
        ],
    ],

    /* ═══════════════════════════ FRANCESE ═══════════════════════════ */
    'francese' => [
        'name'          => 'Francese',
        'flag'          => '🇫🇷',
        'cms'           => 'fr',
        'landing_route' => null,
        'landing_label' => 'Scopri i corsi di francese',
        'questions'     => [
            // — A1 —
            ['level' => 'A1', 'q' => "Je ___ italienne.", 'options' => ['suis', 'es', 'est'], 'answer' => 0],
            ['level' => 'A1', 'q' => "Comment tu ___ ?", 'options' => ["t'appelle", "t'appelles", "s'appelle"], 'answer' => 1],
            ['level' => 'A1', 'q' => "Nous habitons ___ Rome.", 'options' => ['au', 'à', 'en'], 'answer' => 1],
            ['level' => 'A1', 'q' => "C'est ___ amie Marie.", 'options' => ['ma', 'mes', 'mon'], 'answer' => 2],
            // — A2 —
            ['level' => 'A2', 'q' => "Hier, nous ___ au cinéma.", 'options' => ['allons', 'sommes allés', 'allions'], 'answer' => 1],
            ['level' => 'A2', 'q' => "Tu as vu Pierre ? — Oui, je ___ ai vu ce matin.", 'options' => ['le', "l'", 'lui'], 'answer' => 1],
            ['level' => 'A2', 'q' => "Elle est ___ intelligente que son frère.", 'options' => ['plus', 'mieux', 'meilleure'], 'answer' => 0],
            ['level' => 'A2', 'q' => "Vous ___ du sport le week-end ?", 'options' => ['faisez', 'font', 'faites'], 'answer' => 2],
            // — B1 —
            ['level' => 'B1', 'q' => "Si j'avais le temps, je ___ plus souvent.", 'options' => ['voyagerai', 'voyagerais', 'voyageais'], 'answer' => 1],
            ['level' => 'B1', 'q' => "Il faut que tu ___ tes devoirs avant de sortir.", 'options' => ['finis', 'finiras', 'finisses'], 'answer' => 2],
            ['level' => 'B1', 'q' => "C'est la ville ___ je suis né.", 'options' => ['que', 'où', 'dont'], 'answer' => 1],
            ['level' => 'B1', 'q' => "Quand j'étais petit, je ___ au football tous les jours.", 'options' => ['jouais', 'ai joué', 'jouerais'], 'answer' => 0],
            // — B2 —
            ['level' => 'B2', 'q' => "Le livre ___ tu m'as parlé est passionnant.", 'options' => ['que', 'duquel', 'dont'], 'answer' => 2],
            ['level' => 'B2', 'q' => "Bien qu'il ___ fatigué, il a continué à travailler.", 'options' => ['est', 'soit', 'était'], 'answer' => 1],
            ['level' => 'B2', 'q' => "Après ___ mangé, ils sont partis.", 'options' => ['avoir', 'être', 'ayant'], 'answer' => 0],
            ['level' => 'B2', 'q' => "Si tu passes chez Brigitte ce soir, rapporte-___ ce disque.", 'options' => ['la', "l'", 'lui'], 'answer' => 2],
            // — C1 —
            ['level' => 'C1', 'q' => "Je crains qu'il ne ___ trop tard.", 'options' => ['est', 'soit', 'sera'], 'answer' => 1],
            ['level' => 'C1', 'q' => "Quoi qu'il ___ , je le soutiendrai.", 'options' => ['arrivera', 'arriverait', 'arrive'], 'answer' => 2],
            ['level' => 'C1', 'q' => "Elle a réussi son examen ___ elle n'avait presque pas étudié.", 'options' => ["alors qu'", "bien qu'", "quoiqu'"], 'answer' => 0],
            ['level' => 'C1', 'q' => "Il s'est vu ___ le prix d'excellence.", 'options' => ['décerné', 'décerner', 'à décerner'], 'answer' => 1],
        ],
    ],

    /* ═══════════════════════════ SPAGNOLO ═══════════════════════════ */
    'spagnolo' => [
        'name'          => 'Spagnolo',
        'flag'          => '🇪🇸',
        'cms'           => 'es',
        'landing_route' => null,
        'landing_label' => 'Scopri i corsi di spagnolo',
        'questions'     => [
            // — A1 —
            ['level' => 'A1', 'q' => "Yo ___ de Italia.", 'options' => ['soy', 'estoy', 'es'], 'answer' => 0],
            ['level' => 'A1', 'q' => "¿Cómo ___ llamas?", 'options' => ['se', 'te', 'me'], 'answer' => 1],
            ['level' => 'A1', 'q' => "___ libro es muy interesante.", 'options' => ['La', 'Los', 'El'], 'answer' => 2],
            ['level' => 'A1', 'q' => "Nosotros ___ en Roma.", 'options' => ['vivimos', 'viven', 'vivís'], 'answer' => 0],
            // — A2 —
            ['level' => 'A2', 'q' => "¿Cuántas horas ___ usted normalmente?", 'options' => ['dormes', 'duermes', 'duerme'], 'answer' => 2],
            ['level' => 'A2', 'q' => "Ayer ___ al cine con mis amigos.", 'options' => ['voy', 'fui', 'iba'], 'answer' => 1],
            ['level' => 'A2', 'q' => "¿Quieres venir ___ al cine?", 'options' => ['con me', 'con mí', 'conmigo'], 'answer' => 2],
            ['level' => 'A2', 'q' => "Me ___ mucho las películas de terror.", 'options' => ['gusta', 'gustan', 'gusto'], 'answer' => 1],
            // — B1 —
            ['level' => 'B1', 'q' => "No creo que ___ buena idea.", 'options' => ['es', 'sea', 'será'], 'answer' => 1],
            ['level' => 'B1', 'q' => "Cuando ___ a casa, te llamaré.", 'options' => ['llego', 'llegaré', 'llegue'], 'answer' => 2],
            ['level' => 'B1', 'q' => "Todavía no he llamado a ___ .", 'options' => ['alguien', 'nadie', 'ninguno'], 'answer' => 1],
            ['level' => 'B1', 'q' => "Si mañana ___ , no iremos a la playa.", 'options' => ['llueve', 'lloverá', 'lloviese'], 'answer' => 0],
            // — B2 —
            ['level' => 'B2', 'q' => "Si ___ más dinero, me compraría una casa.", 'options' => ['tengo', 'tuviera', 'tendría'], 'answer' => 1],
            ['level' => 'B2', 'q' => "Me pidió que le ___ el informe.", 'options' => ['envío', 'enviaré', 'enviara'], 'answer' => 2],
            ['level' => 'B2', 'q' => "___ terminó la reunión, todos se fueron.", 'options' => ['En cuanto', 'Mientras', 'Aunque'], 'answer' => 0],
            ['level' => 'B2', 'q' => "Es la ciudad ___ habitantes son más amables.", 'options' => ['que sus', 'cuyos', 'de quien'], 'answer' => 1],
            // — C1 —
            ['level' => 'C1', 'q' => "De ___ sabido, no habría venido.", 'options' => ['haberlo', 'lo haber', 'haber'], 'answer' => 0],
            ['level' => 'C1', 'q' => "No es que no ___ ir, es que no puedo.", 'options' => ['quiero', 'querré', 'quiera'], 'answer' => 2],
            ['level' => 'C1', 'q' => "Por más que ___ , no lo convencerás.", 'options' => ['insistes', 'insistas', 'insistirás'], 'answer' => 1],
            ['level' => 'C1', 'q' => "Se ruega a los pasajeros que ___ sus cinturones.", 'options' => ['abrochan', 'abrocharán', 'abrochen'], 'answer' => 2],
        ],
    ],

    /* ══════════════════ ITALIANO (per stranieri) ══════════════════ */
    'italiano' => [
        'name'          => 'Italiano',
        'flag'          => '🇮🇹',
        'cms'           => 'it',
        'landing_route' => 'landing.italiano-stranieri',
        'landing_label' => 'Italian courses in Rome — Corsi di italiano',
        'questions'     => [
            // — A1 —
            ['level' => 'A1', 'q' => "Io ___ mio fratello a scuola ogni mattina.", 'options' => ['accompagni', 'accompagniamo', 'accompagno'], 'answer' => 2],
            ['level' => 'A1', 'q' => "Maria ___ italiana.", 'options' => ['è', 'sei', 'sono'], 'answer' => 0],
            ['level' => 'A1', 'q' => "Noi ___ a Roma da due anni.", 'options' => ['abitate', 'abitiamo', 'abitano'], 'answer' => 1],
            ['level' => 'A1', 'q' => "Mi piace molto ___ pizza.", 'options' => ['il', 'le', 'la'], 'answer' => 2],
            // — A2 —
            ['level' => 'A2', 'q' => "Ieri ___ al mare con gli amici.", 'options' => ['vado', 'sono andato', 'andavo'], 'answer' => 1],
            ['level' => 'A2', 'q' => "Tesoro mio, ___ la luce, per piacere!", 'options' => ['accendi', 'accendo', 'accenda'], 'answer' => 0],
            ['level' => 'A2', 'q' => "Hai visto Marco? — Sì, ___ho visto ieri.", 'options' => ['gli ', 'l’', 'ci '], 'answer' => 1],
            ['level' => 'A2', 'q' => "___ il vino rosso al bianco, signora?", 'options' => ['Preferisci', 'Preferiamo', 'Preferisce'], 'answer' => 2],
            // — B1 —
            ['level' => 'B1', 'q' => "Da bambino ___ sempre al parco.", 'options' => ['giocavo', 'ho giocato', 'giocherò'], 'answer' => 0],
            ['level' => 'B1', 'q' => "Se avrò tempo, ___ a trovarti.", 'options' => ['vengo', 'venissi', 'verrò'], 'answer' => 2],
            ['level' => 'B1', 'q' => "L'espressione «In bocca al lupo» significa:", 'options' => ['Vattene via!', 'Stai attento!', 'Buona fortuna!'], 'answer' => 2],
            ['level' => 'B1', 'q' => "Dimentico ___ di prendere le chiavi di casa.", 'options' => ['mai', 'spesso', 'quasi'], 'answer' => 1],
            // — B2 —
            ['level' => 'B2', 'q' => "Penso che Luca ___ ragione.", 'options' => ['ha', 'abbia', 'avrà'], 'answer' => 1],
            ['level' => 'B2', 'q' => "Se ___ conosciuto Pirandello, gli ___ chiesto un autografo.", 'options' => ['avessi / avrei', 'avevo / avrei', 'avessi / avrò'], 'answer' => 0],
            ['level' => 'B2', 'q' => "È il film più bello ___ io abbia mai visto.", 'options' => ['di cui', 'che', 'quale'], 'answer' => 1],
            ['level' => 'B2', 'q' => "Nonostante ___ stanco, è venuto alla festa.", 'options' => ['era', 'sarebbe', 'fosse'], 'answer' => 2],
            // — C1 —
            ['level' => 'C1', 'q' => "___ arrivati in ritardo, trovammo posto in prima fila.", 'options' => ['Pur essendo', 'Essendo', 'Avendo'], 'answer' => 0],
            ['level' => 'C1', 'q' => "Qualora ___ bisogno, non esitate a contattarci.", 'options' => ['avete', 'aveste', 'avreste'], 'answer' => 1],
            ['level' => 'C1', 'q' => "Non appena l'ebbe ___ , se ne pentì.", 'options' => ['dicendo', 'dire', 'detto'], 'answer' => 2],
            ['level' => 'C1', 'q' => "Si tratta di un problema ___ non sottovalutare.", 'options' => ['di', 'da', 'per'], 'answer' => 1],
        ],
    ],

];
