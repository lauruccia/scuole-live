<?php

/**
 * ─── Contenuti editabili del sito pubblico (mini-CMS) ───────────────────────
 *
 * Questo file definisce QUALI campi di ogni pagina pubblica sono modificabili
 * dal pannello (Sito web → Contenuti sito) e il loro testo PREDEFINITO.
 *
 * Il DB (tabella page_contents) contiene solo i valori personalizzati:
 * se un campo non è mai stato modificato — o viene svuotato dal pannello —
 * il sito usa il default qui sotto. Il default è quindi anche il "ripristina".
 *
 * Tipi di campo:
 *   text     → riga singola, output escapato ({{ }})
 *   textarea → più righe di testo semplice
 *   html     → testo con HTML semplice (<strong>, <em>, <br>, <a>), output raw
 *   richtext → editor visuale (paragrafi interi), output raw
 *   image    → upload immagine (o URL); default = URL attuale
 *   faq      → elenco domande/risposte (repeater), salvato come JSON
 *   lines    → una voce per riga (es. elenco clienti)
 *
 * NB: qui NON si usa route() (il file config viene caricato prima delle
 * route e verrebbe rotto da config:cache) — usare i path statici (/corsi…).
 */

return [

    /* ═══════════════════════════ HOME ═══════════════════════════ */
    'home' => [
        'label' => 'Home',
        'sections' => [
            'seo' => [
                'label' => 'SEO (motori di ricerca)',
                'fields' => [
                    'meta_title' => ['label' => 'Titolo pagina (tag title)', 'type' => 'text', 'default' => "Scuola di Lingue a Roma | A&A Language Center San Paolo"],
                    'meta_description' => ['label' => 'Meta description', 'type' => 'textarea', 'default' => "Scuola di lingue a Roma San Paolo dal 2002. Corsi di inglese, spagnolo, francese, tedesco, arabo, italiano per stranieri con docenti qualificati madrelingua e/o bilingue. Sede esami Trinity College London. Test di livello gratuito."],
                    'meta_keywords' => ['label' => 'Meta keywords', 'type' => 'textarea', 'default' => "scuola di lingue Roma, scuola di lingue Roma San Paolo, corsi di lingue Roma, corsi di inglese Roma, docenti madrelingua e bilingue Roma, Trinity College Roma, certificazioni internazionali lingue Roma, A&A Language Center"],
                ],
            ],
            'hero' => [
                'label' => 'Hero (testata)',
                'fields' => [
                    'hero_eyebrow' => ['label' => 'Riga sopra il titolo', 'type' => 'html', 'default' => "<span>20+ ANNI DI ESPERIENZA</span> &nbsp;·&nbsp; DOCENTI QUALIFICATI MADRELINGUA E/O BILINGUE &nbsp;·&nbsp; CERTIFICAZIONI INTERNAZIONALI"],
                    'hero_kicker' => ['label' => 'Kicker (riga piccola sopra il titolo)', 'type' => 'text', 'default' => "Scuola di Lingue a Roma San Paolo dal 2002"],
                    'hero_title' => ['label' => 'Titolo principale', 'type' => 'text', 'default' => "Parla al mondo."],
                    'hero_title_accent' => ['label' => 'Titolo — seconda riga (blu)', 'type' => 'text', 'default' => "Cambia il tuo futuro."],
                    'hero_desc' => ['label' => 'Descrizione', 'type' => 'html', 'default' => "Corsi di <strong>inglese</strong>, <strong>spagnolo</strong>, <strong>francese</strong>, <strong>tedesco</strong>, <strong>arabo</strong> e <strong>italiano per stranieri</strong> con docenti qualificati madrelingua e/o bilingue. Preparazione certificazioni Trinity, Cambridge, IELTS, DELE, DELF e Goethe. Test di livello gratuito."],
                    'hero_cta_primary' => ['label' => 'Bottone principale', 'type' => 'text', 'default' => "PRENOTA IL TEST GRATUITO →"],
                    'hero_cta_secondary' => ['label' => 'Bottone secondario', 'type' => 'text', 'default' => "SCOPRI I CORSI →"],
                    'hero_image' => ['label' => 'Foto di sfondo', 'type' => 'image', 'default' => "https://images.unsplash.com/photo-1543269865-cbf427effbad?auto=format&fit=crop&w=1920&q=85"],
                    'trinity_badge_title' => ['label' => 'Badge Trinity — titolo', 'type' => 'text', 'default' => "Sede Esami Ufficiale n° 8241"],
                    'trinity_badge_sub' => ['label' => 'Badge Trinity — sottotitolo', 'type' => 'html', 'default' => "GESE &amp; ISE — Scopri le certificazioni →"],
                ],
            ],
            'cert_strip' => [
                'label' => 'Fascia certificazioni',
                'fields' => [
                    'cert_strip_label' => ['label' => 'Testo della fascia loghi', 'type' => 'text', 'default' => "Sede Esami Ufficiale Trinity College London n° 8241 · Cambridge Preparation Centre · Preparazione a tutte le principali certificazioni"],
                ],
            ],
            'why' => [
                'label' => 'Perché scegliere A&A',
                'fields' => [
                    'why_label' => ['label' => 'Etichetta sezione', 'type' => 'text', 'default' => "Perché scegliere A&A"],
                    'why_title' => ['label' => 'Titolo', 'type' => 'html', 'default' => "Un metodo.<br>Un'esperienza.<br>Risultati concreti."],
                    'why_text' => ['label' => 'Paragrafo', 'type' => 'html', 'default' => "<strong>A&amp;A Language Center</strong> è una <strong>scuola di lingue a Roma</strong>, nel quartiere San Paolo, con oltre 20 anni di esperienza nell'insegnamento delle lingue straniere. Metodi innovativi, docenti qualificati madrelingua e/o bilingue e un approccio completamente personalizzato sul tuo livello CEFR e sui tuoi obiettivi — sia che tu cerchi corsi di inglese, italiano per stranieri o lingue per il lavoro."],
                    'why_cta' => ['label' => 'Bottone', 'type' => 'text', 'default' => "Prenota il test gratuito →"],
                    'feature1_title' => ['label' => 'Card 1 — titolo', 'type' => 'text', 'default' => "Insegnanti internazionali"],
                    'feature1_text' => ['label' => 'Card 1 — testo', 'type' => 'textarea', 'default' => "Docenti qualificati madrelingua e/o bilingue provenienti da tutto il mondo con esperienza didattica certificata."],
                    'feature2_title' => ['label' => 'Card 2 — titolo', 'type' => 'text', 'default' => "Percorsi personalizzati"],
                    'feature2_text' => ['label' => 'Card 2 — testo', 'type' => 'textarea', 'default' => "Corsi costruiti sui tuoi obiettivi e sul tuo livello CEFR, valutato con test gratuito."],
                    'feature3_title' => ['label' => 'Card 3 — titolo', 'type' => 'text', 'default' => "Certificazioni riconosciute"],
                    'feature3_text' => ['label' => 'Card 3 — testo', 'type' => 'textarea', 'default' => "Sede esami Trinity College London, Cambridge Preparation Centre e preparazione a IELTS e tutte le principali certificazioni."],
                    'feature4_title' => ['label' => 'Card 4 — titolo', 'type' => 'text', 'default' => "Online e in presenza"],
                    'feature4_text' => ['label' => 'Card 4 — testo', 'type' => 'textarea', 'default' => "Scegli la modalità che preferisci. Sempre con qualità top e docenti dedicati."],
                    'feature5_title' => ['label' => 'Card 5 — titolo', 'type' => 'text', 'default' => "Mini gruppi"],
                    'feature5_text' => ['label' => 'Card 5 — testo', 'type' => 'textarea', 'default' => "Classi a numero ridotto per la tua attenzione vera e un apprendimento efficace."],
                    'feature6_title' => ['label' => 'Card 6 — titolo', 'type' => 'text', 'default' => "Career focused"],
                    'feature6_text' => ['label' => 'Card 6 — testo', 'type' => 'textarea', 'default' => "Lingue per il lavoro, l'università e la tua crescita professionale nel mercato globale."],
                ],
            ],
            'corsi' => [
                'label' => 'Sezione corsi (sfondo blu)',
                'fields' => [
                    'corsi_label' => ['label' => 'Etichetta sezione', 'type' => 'text', 'default' => "I nostri corsi"],
                    'corsi_title' => ['label' => 'Titolo', 'type' => 'html', 'default' => "Scegli la lingua.<br>Apri le porte<br>al <em>mondo</em>."],
                    'corsi_subtext' => ['label' => 'Sottotitolo', 'type' => 'textarea', 'default' => "Corsi per ogni livello e ogni età. Programmi allineati al framework CEFR con certificazioni riconosciute a livello internazionale."],
                    'corsi_cta' => ['label' => 'Bottone', 'type' => 'text', 'default' => "TUTTI I CORSI →"],
                ],
            ],
            'metodo' => [
                'label' => 'Il nostro metodo',
                'fields' => [
                    'metodo_label' => ['label' => 'Etichetta sezione', 'type' => 'text', 'default' => "Il nostro metodo"],
                    'metodo_title' => ['label' => 'Titolo', 'type' => 'html', 'default' => "Un percorso su misura,<br>passo dopo passo."],
                    'metodo_subtext' => ['label' => 'Sottotitolo', 'type' => 'textarea', 'default' => "Ogni studente è unico. Iniziamo dal tuo livello reale e costruiamo insieme il percorso più efficace verso i tuoi obiettivi."],
                    'metodo_cta' => ['label' => 'Bottone', 'type' => 'text', 'default' => "Inizia ora →"],
                    'step1_title' => ['label' => 'Passo 1 — titolo', 'type' => 'text', 'default' => "Test iniziale"],
                    'step1_text' => ['label' => 'Passo 1 — testo', 'type' => 'textarea', 'default' => "Valutiamo il tuo livello e i tuoi obiettivi."],
                    'step2_title' => ['label' => 'Passo 2 — titolo', 'type' => 'text', 'default' => "Piano personalizzato"],
                    'step2_text' => ['label' => 'Passo 2 — testo', 'type' => 'textarea', 'default' => "Costruiamo il percorso perfetto per te."],
                    'step3_title' => ['label' => 'Passo 3 — titolo', 'type' => 'text', 'default' => "Speaking immersion"],
                    'step3_text' => ['label' => 'Passo 3 — testo', 'type' => 'textarea', 'default' => "Parla, ascolta, vivi la lingua ogni giorno."],
                    'step4_title' => ['label' => 'Passo 4 — titolo', 'type' => 'text', 'default' => "Preparazione certificazioni"],
                    'step4_text' => ['label' => 'Passo 4 — testo', 'type' => 'textarea', 'default' => "Ti prepariamo e ti accompagniamo all'esame."],
                    'step5_title' => ['label' => 'Passo 5 — titolo', 'type' => 'text', 'default' => "Obiettivi raggiunti"],
                    'step5_text' => ['label' => 'Passo 5 — testo', 'type' => 'textarea', 'default' => "Nuove competenze, nuove opportunità, nuovo futuro."],
                ],
            ],
            'stats' => [
                'label' => 'Numeri chiave',
                'fields' => [
                    'stat1_num' => ['label' => 'Dato 1 — numero', 'type' => 'html', 'default' => "20<sup>+</sup>"],
                    'stat1_label' => ['label' => 'Dato 1 — etichetta', 'type' => 'text', 'default' => "Anni di esperienza"],
                    'stat2_num' => ['label' => 'Dato 2 — numero', 'type' => 'html', 'default' => "250<sup>+</sup>"],
                    'stat2_label' => ['label' => 'Dato 2 — etichetta', 'type' => 'text', 'default' => "Studenti formati"],
                    'stat3_num' => ['label' => 'Dato 3 — numero', 'type' => 'html', 'default' => "98<sup>%</sup>"],
                    'stat3_label' => ['label' => 'Dato 3 — etichetta', 'type' => 'text', 'default' => "Studenti soddisfatti"],
                    'stat4_num' => ['label' => 'Dato 4 — numero', 'type' => 'html', 'default' => "6"],
                    'stat4_label' => ['label' => 'Dato 4 — etichetta', 'type' => 'text', 'default' => "Certificazioni internazionali"],
                ],
            ],
            'faq' => [
                'label' => 'Domande frequenti (FAQ)',
                'fields' => [
                    'faq_title' => ['label' => 'Titolo FAQ', 'type' => 'text', 'default' => "Domande frequenti su A&A Language Center"],
                    'faq_subtitle' => ['label' => 'Sottotitolo FAQ', 'type' => 'text', 'default' => "Le risposte alle domande più comuni su corsi, prezzi, certificazioni e modalità."],
                    'faq_items' => ['label' => 'Domande e risposte', 'type' => 'faq', 'default' => [
                        ['q' => "Che corsi di lingue offrite a Roma?", 'a' => "<p>Offriamo corsi di <strong>inglese, spagnolo, francese, tedesco, arabo, russo, portoghese, cinese</strong> e <strong>italiano per stranieri</strong>. Tutti i livelli CEFR (A1–C2), con docenti qualificati madrelingua e/o bilingue. Vedi il <a href=\"/corsi\">catalogo completo</a>.</p>"],
                        ['q' => "Dove si trova la scuola?", 'a' => "<p>Siamo in <strong>Viale Leonardo da Vinci 193, 00145 Roma</strong>, nel quartiere San Paolo. A pochi passi dalle fermate metro San Paolo e Marconi (linea B), ben collegati con EUR, Garbatella, Ostiense e Testaccio.</p>"],
                        ['q' => "Il test di livello è davvero gratuito?", 'a' => "<p>Sì, completamente gratuito e senza impegno. Comprende una parte scritta e una orale con un docente qualificato madrelingua o bilingue. Al termine ricevi una valutazione CEFR dettagliata. <a href=\"/iscriviti\">Prenotalo qui</a>.</p>"],
                        ['q' => "Posso seguire i corsi online?", 'a' => "<p>Sì. Tutti i nostri corsi sono disponibili anche in modalità online (videoconferenza live) con la stessa qualità delle lezioni in presenza. Offriamo inoltre il servizio esclusivo <strong>Inglese al Telefono</strong>: 30 minuti al giorno per migliorare lo speaking.</p>"],
                        ['q' => "Siete sede d'esame Trinity College London?", 'a' => "<p>Sì. A&amp;A Language Center è <strong>Sede d'Esame Ufficiale Trinity College London n° 8241</strong>. Organizziamo sessioni GESE e ISE durante tutto l'anno direttamente nella nostra sede.</p>"],
                        ['q' => "Avete corsi per aziende?", 'a' => "<p>Sì. Dal 2002 facciamo formazione linguistica B2B per aziende, enti pubblici, hotel e studi professionali. Tra i clienti: MEF, Confcommercio, H10 Hotels. Vedi <a href=\"/corsi-inglese-aziendali-roma\">Corsi Aziendali</a>.</p>"],
                    ]],
                ],
            ],
            'cta' => [
                'label' => 'Invito finale (CTA)',
                'fields' => [
                    'cta_title' => ['label' => 'Titolo', 'type' => 'html', 'default' => "Entri per imparare una lingua.<br>Esci con nuove opportunità."],
                    'cta_text' => ['label' => 'Testo', 'type' => 'textarea', 'default' => "Il tuo futuro parla più lingue. Inizia oggi il tuo percorso con A&A Language Center."],
                    'cta_button' => ['label' => 'Bottone', 'type' => 'text', 'default' => "PRENOTA IL TUO TEST GRATUITO →"],
                    'cta_image' => ['label' => 'Foto di sfondo', 'type' => 'image', 'default' => "https://images.unsplash.com/photo-1531572753322-ad063cecc140?auto=format&fit=crop&w=1600&q=70"],
                ],
            ],
        ],
    ],

    /* ═══════════════════════ LA SCUOLA ═══════════════════════ */
    'la-scuola' => [
        'label' => 'La Scuola',
        'sections' => [
            'seo' => [
                'label' => 'SEO (motori di ricerca)',
                'fields' => [
                    'meta_title' => ['label' => 'Titolo pagina (tag title)', 'type' => 'text', 'default' => "La Scuola di Lingue a Roma San Paolo | A&A Language Center"],
                    'meta_description' => ['label' => 'Meta description', 'type' => 'textarea', 'default' => "A&A Language Center, scuola di lingue a Roma San Paolo dal 2002. Sede ufficiale esami Trinity College London n° 8241. Docenti qualificati madrelingua e/o bilingue, corsi personalizzati per tutte le età e tutti i livelli CEFR."],
                    'meta_keywords' => ['label' => 'Meta keywords', 'type' => 'textarea', 'default' => "scuola di lingue Roma San Paolo, scuola di inglese Roma San Paolo, centro esami Trinity Roma, sede Trinity College Roma 8241, scuola di lingue EUR, scuola di lingue Marconi, scuola di lingue Garbatella, A&A Language Center"],
                ],
            ],
            'hero' => [
                'label' => 'Testata',
                'fields' => [
                    'hero_title' => ['label' => 'Titolo', 'type' => 'html', 'default' => "La <em>Scuola di Lingue</em> a Roma San Paolo"],
                    'hero_subtitle' => ['label' => 'Sottotitolo', 'type' => 'html', 'default' => "Open Your Mind To The World — A&amp;A Language Center, dal 2002 a Roma. Sede ufficiale esami Trinity College London n° 8241."],
                ],
            ],
            'intro' => [
                'label' => 'Chi siamo',
                'fields' => [
                    'intro_label' => ['label' => 'Etichetta sezione', 'type' => 'text', 'default' => "Chi siamo"],
                    'intro_title' => ['label' => 'Titolo', 'type' => 'html', 'default' => "Una scuola con <em>una storia</em>"],
                    'intro_text' => ['label' => 'Testo', 'type' => 'richtext', 'default' => "<p>Hai mai pensato di imparare una lingua straniera ma non hai mai trovato il corso giusto? A&amp;A Language Center è la risposta. Dal 2002 offriamo corsi di <strong>Inglese, Spagnolo, Francese, Tedesco, Portoghese, Russo, Arabo</strong> e <strong>Italiano per Stranieri</strong>, costruiti su misura per ogni studente.</p><p>La nostra <strong>scuola di lingue a Roma</strong> si trova nel vivace quartiere <strong>San Paolo</strong>, polo universitario di Roma Tre, a pochi passi dalle fermate metro <strong>San Paolo</strong> e <strong>Marconi</strong>, ben collegata anche con i quartieri <strong>EUR</strong>, <strong>Garbatella</strong> e <strong>Ostiense</strong>. Un ambiente accogliente, moderno e stimolante dove imparare diventa un piacere.</p>"],
                    'intro_orari' => ['label' => 'Badge orari', 'type' => 'html', 'default' => "🕐 <em>Lun–Ven</em> 10:00–19:00 &nbsp;·&nbsp; <em>Sab</em> 9:00–13:00"],
                    'intro_image' => ['label' => 'Foto', 'type' => 'image', 'default' => "https://images.unsplash.com/photo-1524178232363-1fb2b075b655?auto=format&fit=crop&w=900&q=85"],
                ],
            ],
            'stats' => [
                'label' => 'Numeri (fascia blu)',
                'fields' => [
                    'stat1_num' => ['label' => 'Dato 1 — numero', 'type' => 'text', 'default' => "15"],
                    'stat1_label' => ['label' => 'Dato 1 — etichetta', 'type' => 'text', 'default' => "Docenti qualificati"],
                    'stat2_num' => ['label' => 'Dato 2 — numero', 'type' => 'text', 'default' => "250+"],
                    'stat2_label' => ['label' => 'Dato 2 — etichetta', 'type' => 'text', 'default' => "Studenti all'anno"],
                    'stat3_num' => ['label' => 'Dato 3 — numero', 'type' => 'text', 'default' => "49"],
                    'stat3_label' => ['label' => 'Dato 3 — etichetta', 'type' => 'text', 'default' => "Corsi disponibili"],
                    'stat4_num' => ['label' => 'Dato 4 — numero', 'type' => 'text', 'default' => "20+"],
                    'stat4_label' => ['label' => 'Dato 4 — etichetta', 'type' => 'text', 'default' => "Anni di esperienza"],
                ],
            ],
            'team' => [
                'label' => 'Gli insegnanti',
                'fields' => [
                    'team_label' => ['label' => 'Etichetta sezione', 'type' => 'text', 'default' => "Il team"],
                    'team_title' => ['label' => 'Titolo', 'type' => 'html', 'default' => "Gli <em>Insegnanti</em>"],
                    'team_subtext' => ['label' => 'Sottotitolo', 'type' => 'textarea', 'default' => "Staff internazionale di madrelingua e bilingue, selezionati con rigore e costantemente aggiornati. Tutti certificati e con esperienza pluriennale nell'insegnamento."],
                    'team1_title' => ['label' => 'Card 1 — titolo', 'type' => 'text', 'default' => "Lezioni personalizzate"],
                    'team1_text' => ['label' => 'Card 1 — testo', 'type' => 'textarea', 'default' => "Ogni percorso è costruito sulle esigenze reali dello studente: obiettivi, tempi, livello di partenza e stile di apprendimento. Nessun corso uguale all'altro."],
                    'team2_title' => ['label' => 'Card 2 — titolo', 'type' => 'text', 'default' => "Docenti qualificati madrelingua e/o bilingue"],
                    'team2_text' => ['label' => 'Card 2 — testo', 'type' => 'textarea', 'default' => "Tutti i nostri insegnanti provengono da paesi di lingua madre o sono bilingue certificati, con aggiornamenti annuali tenuti dal Trinity College London."],
                    'team3_title' => ['label' => 'Card 3 — titolo', 'type' => 'text', 'default' => "Metodo A&A"],
                    'team3_text' => ['label' => 'Card 3 — testo', 'type' => 'textarea', 'default' => "Veloce, flessibile e funzionale. Puoi scegliere dove frequentare: a scuola, a casa, in ufficio o anche al telefono. L'importante è che tu raggiunga i tuoi obiettivi."],
                ],
            ],
            'cert' => [
                'label' => 'Certificazioni (sezione scura)',
                'fields' => [
                    'cert_label' => ['label' => 'Etichetta sezione', 'type' => 'text', 'default' => "Certificazioni"],
                    'cert_title' => ['label' => 'Titolo', 'type' => 'html', 'default' => "Sede ufficiale <span style=\"color:var(--gold)\">Trinity College London</span>"],
                    'cert_text' => ['label' => 'Testo', 'type' => 'html', 'default' => "Siamo <strong style=\"color:#fff;\">Sede d'esame n° 8241</strong>. Organizziamo sessioni GESE e ISE durante tutto l'anno e prepariamo i nostri studenti per le principali certificazioni internazionali. <a href=\"/le-certificazioni\" style=\"color:var(--gold);font-weight:700;text-decoration:underline;\">Scopri le certificazioni →</a>"],
                ],
            ],
            'pillars' => [
                'label' => 'I 4 pilastri',
                'fields' => [
                    'pillars_label' => ['label' => 'Etichetta sezione', 'type' => 'text', 'default' => "Perché sceglierci"],
                    'pillars_title' => ['label' => 'Titolo', 'type' => 'html', 'default' => "I 4 pilastri di <em>A&A</em>"],
                    'pillar1_title' => ['label' => 'Pilastro 1 — titolo', 'type' => 'text', 'default' => "Esperienza"],
                    'pillar1_text' => ['label' => 'Pilastro 1 — testo', 'type' => 'textarea', 'default' => "A&A Language Center opera dal 2002. In vent'anni abbiamo formato migliaia di studenti di ogni età e livello, costruendo un'esperienza didattica solida, collaudata e in continua evoluzione."],
                    'pillar2_title' => ['label' => 'Pilastro 2 — titolo', 'type' => 'text', 'default' => "Eccellenza"],
                    'pillar2_text' => ['label' => 'Pilastro 2 — testo', 'type' => 'textarea', 'default' => "Sede d'esami GESE e ISE n° 8241 del Trinity College London. Prepariamo per IELTS, TOEFL, Cambridge, DELE, CILS, DELF/DALF, Zertifikat Deutsch, TRKI–TORFL e molte altre certificazioni."],
                    'pillar3_title' => ['label' => 'Pilastro 3 — titolo', 'type' => 'text', 'default' => "Docenti di Qualità"],
                    'pillar3_text' => ['label' => 'Pilastro 3 — testo', 'type' => 'textarea', 'default' => "I nostri docenti provengono da ogni parte del mondo, portando un prezioso arricchimento culturale. Tutti certificati e con esperienza pluriennale, garantiscono un apprendimento autentico ed efficace."],
                    'pillar4_title' => ['label' => 'Pilastro 4 — titolo', 'type' => 'text', 'default' => "Corsi Personalizzati"],
                    'pillar4_text' => ['label' => 'Pilastro 4 — testo', 'type' => 'textarea', 'default' => "Ogni studente riceve un programma didattico costruito su misura, con il monte ore più adatto ai propri obiettivi, tempi e stile di apprendimento. Nessun corso uguale all'altro."],
                ],
            ],
            'cta' => [
                'label' => 'Invito finale (CTA)',
                'fields' => [
                    'cta_label' => ['label' => 'Etichetta', 'type' => 'text', 'default' => "Inizia ora"],
                    'cta_title' => ['label' => 'Titolo', 'type' => 'text', 'default' => "Pronto a iniziare il tuo percorso linguistico?"],
                    'cta_text' => ['label' => 'Testo', 'type' => 'textarea', 'default' => "Prenota il tuo test di livello gratuito. Nessun impegno, solo il primo passo verso una nuova lingua."],
                    'cta_btn1' => ['label' => 'Bottone 1', 'type' => 'text', 'default' => "✦ Prenota il Test Gratuito"],
                    'cta_btn2' => ['label' => 'Bottone 2', 'type' => 'text', 'default' => "Contattaci →"],
                ],
            ],
        ],
    ],

    /* ═══════════════════════ SERVIZI ═══════════════════════ */
    'servizi' => [
        'label' => 'Servizi',
        'sections' => [
            'seo' => [
                'label' => 'SEO (motori di ricerca)',
                'fields' => [
                    'meta_title' => ['label' => 'Titolo pagina (tag title)', 'type' => 'text', 'default' => "Servizi — Corsi di Lingue Online e in Presenza a Roma"],
                    'meta_description' => ['label' => 'Meta description', 'type' => 'textarea', 'default' => "Corsi di lingue online e in presenza a Roma, inglese al telefono, test di livello gratuito, preparazione certificazioni Trinity, Cambridge, IELTS. Corsi di formazione per docenti."],
                    'meta_keywords' => ['label' => 'Meta keywords', 'type' => 'textarea', 'default' => "corsi di lingue online Roma, corsi di lingue in presenza Roma, inglese al telefono Roma, test livello inglese gratuito Roma, corsi formazione docenti lingue Roma, corso inglese individuale Roma, corso inglese mini gruppo Roma, preparazione certificazioni lingue Roma"],
                ],
            ],
            'hero' => [
                'label' => 'Testata',
                'fields' => [
                    'hero_title' => ['label' => 'Titolo', 'type' => 'html', 'default' => "I Nostri <em>Servizi</em> — Corsi di Lingue a Roma"],
                    'hero_subtitle' => ['label' => 'Sottotitolo', 'type' => 'text', 'default' => "Corsi online e in presenza, inglese al telefono, test di livello gratuito, preparazione certificazioni internazionali e formazione docenti."],
                ],
            ],
            'services' => [
                'label' => 'Le 6 card servizi',
                'fields' => [
                    'services_label' => ['label' => 'Etichetta sezione', 'type' => 'text', 'default' => "Cosa offriamo"],
                    'services_title' => ['label' => 'Titolo', 'type' => 'html', 'default' => "Sei soluzioni per <em>ogni esigenza</em>"],
                    'services_subtext' => ['label' => 'Sottotitolo', 'type' => 'textarea', 'default' => "Servizi pensati per adattarsi ai tuoi ritmi, ai tuoi obiettivi e al tuo stile di vita."],
                    'service1_title' => ['label' => 'Servizio 1 — titolo', 'type' => 'text', 'default' => "Corsi Online"],
                    'service1_text' => ['label' => 'Servizio 1 — testo', 'type' => 'textarea', 'default' => "Lezioni individuali comode e flessibili tramite Skype, FaceTime o Microsoft Teams con i nostri docenti qualificati. Stessa qualità delle lezioni in presenza, senza spostarti da casa o dall'ufficio."],
                    'service1_tag' => ['label' => 'Servizio 1 — etichetta', 'type' => 'text', 'default' => "Skype · FaceTime · Teams"],
                    'service2_title' => ['label' => 'Servizio 2 — titolo', 'type' => 'text', 'default' => "Corsi in Presenza"],
                    'service2_text' => ['label' => 'Servizio 2 — testo', 'type' => 'textarea', 'default' => "Lezioni nella nostra sede di Roma San Paolo, in un ambiente confortevole e stimolante. Ideale per chi desidera un'immersione linguistica completa e il confronto diretto con i compagni."],
                    'service2_tag' => ['label' => 'Servizio 2 — etichetta', 'type' => 'text', 'default' => "Roma San Paolo"],
                    'service3_title' => ['label' => 'Servizio 3 — titolo', 'type' => 'text', 'default' => "Inglese al Telefono"],
                    'service3_text' => ['label' => 'Servizio 3 — testo', 'type' => 'textarea', 'default' => "Lezioni di conversazione telefonica per chi ha pochissimo tempo. Pratico, veloce e sorprendentemente efficace: bastano 30 minuti al giorno per migliorare sensibilmente."],
                    'service3_tag' => ['label' => 'Servizio 3 — etichetta', 'type' => 'text', 'default' => "Flessibile · Ovunque"],
                    'service4_title' => ['label' => 'Servizio 4 — titolo', 'type' => 'text', 'default' => "Test di Livello Gratuito"],
                    'service4_text' => ['label' => 'Servizio 4 — testo', 'type' => 'textarea', 'default' => "Prima di iniziare qualsiasi corso, offriamo un Entrance Test scritto e orale completamente gratuito per determinare il tuo livello di partenza secondo il framework CEFR (A1–C2)."],
                    'service4_tag' => ['label' => 'Servizio 4 — etichetta', 'type' => 'text', 'default' => "Gratuito · Senza impegno"],
                    'service5_title' => ['label' => 'Servizio 5 — titolo', 'type' => 'text', 'default' => "Preparazione Certificazioni"],
                    'service5_text' => ['label' => 'Servizio 5 — testo', 'type' => 'textarea', 'default' => "Corsi intensivi mirati alla preparazione degli esami per le principali certificazioni internazionali: Trinity, Cambridge, IELTS, TOEFL, DELF/DALF, Goethe Institut, PLIDA, DELE e altre."],
                    'service5_tag' => ['label' => 'Servizio 5 — etichetta', 'type' => 'text', 'default' => "Trinity · Cambridge · IELTS · DELF"],
                    'service6_title' => ['label' => 'Servizio 6 — titolo', 'type' => 'text', 'default' => "Corsi per Docenti"],
                    'service6_text' => ['label' => 'Servizio 6 — testo', 'type' => 'textarea', 'default' => "A&A Language Center è accreditata MIUR come ente di formazione. Offriamo corsi di lingue dedicati ai docenti, per la formazione personale e la preparazione di certificazioni linguistiche valide ai fini concorsuali."],
                    'service6_tag' => ['label' => 'Servizio 6 — etichetta', 'type' => 'text', 'default' => "Formazione docenti · MIUR"],
                ],
            ],
            'target' => [
                'label' => 'Per chi (target)',
                'fields' => [
                    'target_label' => ['label' => 'Etichetta sezione', 'type' => 'text', 'default' => "Target"],
                    'target_title' => ['label' => 'Titolo', 'type' => 'html', 'default' => "Per tutte le età e <em>tutti i livelli</em>"],
                    'target1_title' => ['label' => 'Card 1 — titolo', 'type' => 'text', 'default' => "Bambini e Ragazzi"],
                    'target1_text' => ['label' => 'Card 1 — testo', 'type' => 'textarea', 'default' => "Approccio ludico e coinvolgente per costruire le basi della lingua sin dall'infanzia."],
                    'target2_title' => ['label' => 'Card 2 — titolo', 'type' => 'text', 'default' => "Studenti"],
                    'target2_text' => ['label' => 'Card 2 — testo', 'type' => 'textarea', 'default' => "Preparazione per esami scolastici, universitari e certificazioni internazionali."],
                    'target3_title' => ['label' => 'Card 3 — titolo', 'type' => 'text', 'default' => "Professionisti"],
                    'target3_text' => ['label' => 'Card 3 — testo', 'type' => 'textarea', 'default' => "Business English, presentazioni, negoziazioni e comunicazione formale in lingua straniera."],
                    'target4_title' => ['label' => 'Card 4 — titolo', 'type' => 'text', 'default' => "Stranieri in Italia"],
                    'target4_text' => ['label' => 'Card 4 — testo', 'type' => 'textarea', 'default' => "Corsi di Italiano per stranieri con percorsi di integrazione e certificazioni PLIDA."],
                ],
            ],
            'orari' => [
                'label' => 'Modalità e orari',
                'fields' => [
                    'orari_label' => ['label' => 'Etichetta sezione', 'type' => 'text', 'default' => "Modalità e orari"],
                    'orari_title' => ['label' => 'Titolo', 'type' => 'text', 'default' => "Flessibilità totale"],
                    'orari_subtitle' => ['label' => 'Sottotitolo', 'type' => 'textarea', 'default' => "Siamo aperti quasi tutti i giorni per garantirti la massima flessibilità. Ogni lezione ha una durata minima di 55 minuti per un apprendimento efficace e completo."],
                    'orari_note' => ['label' => 'Nota in evidenza', 'type' => 'html', 'default' => "💡 <strong>Orario No-Stop</strong> dal lunedì al venerdì: nessuna pausa pranzo. Prenota la tua lezione nell'orario più comodo, anche in pausa dal lavoro."],
                    'orari_image' => ['label' => 'Foto', 'type' => 'image', 'default' => "https://images.unsplash.com/photo-1580582932707-520aed937b7b?auto=format&fit=crop&w=800&q=85"],
                ],
            ],
            'cta' => [
                'label' => 'Invito finale (CTA)',
                'fields' => [
                    'cta_label' => ['label' => 'Etichetta', 'type' => 'text', 'default' => "Inizia oggi"],
                    'cta_title' => ['label' => 'Titolo', 'type' => 'text', 'default' => "Prenota il tuo test di livello gratuito"],
                    'cta_text' => ['label' => 'Testo', 'type' => 'textarea', 'default' => "Scopri quale servizio è più adatto alle tue esigenze. Zero impegno, massima chiarezza."],
                    'cta_btn1' => ['label' => 'Bottone 1', 'type' => 'text', 'default' => "✦ Prenota ora — è gratis"],
                    'cta_btn2' => ['label' => 'Bottone 2', 'type' => 'text', 'default' => "Contattaci →"],
                ],
            ],
        ],
    ],

    /* ═══════════════════════ CONTATTACI ═══════════════════════ */
    'contattaci' => [
        'label' => 'Contattaci',
        'sections' => [
            'seo' => [
                'label' => 'SEO (motori di ricerca)',
                'fields' => [
                    'meta_title' => ['label' => 'Titolo pagina (tag title)', 'type' => 'text', 'default' => "Contatti A&A Language Center — Scuola di Lingue Roma San Paolo"],
                    'meta_description' => ['label' => 'Meta description', 'type' => 'textarea', 'default' => "Contatti A&A Language Center, scuola di lingue a Roma San Paolo: Viale Leonardo da Vinci 193, 00145 Roma. Tel 06 5743734, info@aealanguagecenter.it. Lun–Ven 9–20, Sab 10–14."],
                    'meta_keywords' => ['label' => 'Meta keywords', 'type' => 'textarea', 'default' => "A&A Language Center contatti, scuola di lingue Roma San Paolo indirizzo, scuola di lingue Viale Leonardo da Vinci, scuola di lingue vicino metro San Paolo, telefono scuola di lingue Roma"],
                ],
            ],
            'hero' => [
                'label' => 'Testata',
                'fields' => [
                    'hero_title' => ['label' => 'Titolo', 'type' => 'html', 'default' => "<em>Contattaci</em> — A&amp;A Language Center Roma"],
                    'hero_subtitle' => ['label' => 'Sottotitolo', 'type' => 'text', 'default' => "Scuola di lingue a Roma San Paolo. Viale Leonardo da Vinci 193, 00145 Roma. Tel 06 5743734."],
                ],
            ],
            'dove' => [
                'label' => 'Dove siamo',
                'fields' => [
                    'dove_label' => ['label' => 'Etichetta sezione', 'type' => 'text', 'default' => "Dove siamo"],
                    'dove_title' => ['label' => 'Titolo', 'type' => 'html', 'default' => "Vieni a <em>trovarci</em>"],
                    'dove_subtext' => ['label' => 'Sottotitolo', 'type' => 'html', 'default' => "Nel cuore del quartiere <strong>San Paolo</strong> a Roma, a pochi passi da Università Roma Tre e dalle fermate metro."],
                ],
            ],
            'form_box' => [
                'label' => 'Box "Scrivici"',
                'fields' => [
                    'box_title' => ['label' => 'Titolo box', 'type' => 'text', 'default' => "Scrivici o prenota il test"],
                    'box_text' => ['label' => 'Testo box', 'type' => 'textarea', 'default' => "Compila il modulo per richiedere informazioni su corsi, prezzi, orari o per prenotare il tuo test di livello gratuito. Ti risponderemo entro poche ore lavorative."],
                    'hl1_title' => ['label' => 'Punto 1 — titolo', 'type' => 'text', 'default' => "Test di livello gratuito"],
                    'hl1_text' => ['label' => 'Punto 1 — testo', 'type' => 'textarea', 'default' => "Determiniamo il tuo livello CEFR con un test scritto e orale senza alcun costo e senza impegno."],
                    'hl2_title' => ['label' => 'Punto 2 — titolo', 'type' => 'text', 'default' => "Risposta rapida"],
                    'hl2_text' => ['label' => 'Punto 2 — testo', 'type' => 'textarea', 'default' => "Rispondiamo a tutte le richieste entro 24 ore lavorative."],
                    'hl3_title' => ['label' => 'Punto 3 — titolo', 'type' => 'text', 'default' => "Consulenza personalizzata"],
                    'hl3_text' => ['label' => 'Punto 3 — testo', 'type' => 'textarea', 'default' => "Ti aiutiamo a scegliere il corso e la modalità più adatta ai tuoi obiettivi."],
                    'box_cta' => ['label' => 'Bottone', 'type' => 'text', 'default' => "✦ Compila il modulo di contatto"],
                ],
            ],
        ],
    ],

    /* ═══════════════════ LE CERTIFICAZIONI ═══════════════════ */
    'le-certificazioni' => [
        'label' => 'Le Certificazioni',
        'sections' => [
            'seo' => [
                'label' => 'SEO (motori di ricerca)',
                'fields' => [
                    'meta_title' => ['label' => 'Titolo pagina (tag title)', 'type' => 'text', 'default' => "Le Certificazioni Trinity College London — Sede Esami n° 8241 | A&A Language Center Roma"],
                    'meta_description' => ['label' => 'Meta description', 'type' => 'textarea', 'default' => "A&A Language Center è sede d'esami FULL Trinity College London n° 8241 a Roma. Certificazioni GESE e ISE riconosciute dal MIUR, valide per crediti formativi, crediti universitari e concorsi pubblici."],
                    'meta_keywords' => ['label' => 'Meta keywords', 'type' => 'textarea', 'default' => "certificazioni Trinity Roma, sede esami Trinity College London Roma, esami GESE Roma, esami ISE Roma, certificazione inglese crediti formativi, certificazione inglese università, Trinity College London 8241"],
                ],
            ],
            'hero' => [
                'label' => 'Testata',
                'fields' => [
                    'hero_eyebrow' => ['label' => 'Riga sopra il titolo', 'type' => 'text', 'default' => "Open Your Mind To The World"],
                    'hero_title' => ['label' => 'Titolo', 'type' => 'text', 'default' => "Le Certificazioni"],
                    'hero_text' => ['label' => 'Testo', 'type' => 'html', 'default' => "A&amp;A Language Center è <strong>sede d'esami FULL Trinity College London n° 8241</strong>: gli esami si tengono direttamente nella nostra sede in diversi periodi dell'anno."],
                ],
            ],
            'sede' => [
                'label' => 'Sede d\'esami Trinity',
                'fields' => [
                    'sede_title' => ['label' => 'Titolo', 'type' => 'html', 'default' => "Sede d'esami ufficiale <em>Trinity College London n° 8241</em>"],
                    'sede_text' => ['label' => 'Testo', 'type' => 'richtext', 'default' => "<p>Gli studenti possono sostenere gli esami direttamente in sede e ottenere certificati validi per i <strong>Crediti Formativi negli esami di maturità</strong>, per i <strong>crediti universitari</strong> e per i <strong>concorsi pubblici</strong>.</p><p>Gli esami Trinity sono particolarmente importanti anche per gli studenti universitari: sono riconosciuti per l'ammissione a un gran numero di facoltà e possono essere utilizzati come crediti universitari per l'idoneità linguistica.</p>"],
                    'sede_highlight' => ['label' => 'Riquadro in evidenza', 'type' => 'html', 'default' => "🎓 <strong>Più di 1.600 facoltà e corsi di laurea riconoscono le certificazioni Trinity.</strong><br>\n            <a href=\"https://www.trinitycollege.it/riconoscimenti/\" target=\"_blank\" rel=\"noopener\">Consulta l'elenco completo dei riconoscimenti →</a>"],
                    'sede_text2' => ['label' => 'Testo dopo il riquadro', 'type' => 'html', 'default' => "Le certificazioni Trinity College London sono riconosciute da <strong>università, aziende e istituzioni governative</strong> in Italia e nel mondo."],
                ],
            ],
            'miur' => [
                'label' => 'Riconoscimento MIUR',
                'fields' => [
                    'miur_title' => ['label' => 'Titolo', 'type' => 'html', 'default' => "Ente certificatore riconosciuto dal <em>Ministero dell'Istruzione</em>"],
                    'miur_text' => ['label' => 'Testo', 'type' => 'richtext', 'default' => "<p>Trinity College London è incluso nell'elenco degli Enti certificatori pubblicato dal Ministero Italiano della Pubblica Istruzione che soddisfano i requisiti per il riconoscimento della validità delle certificazioni delle competenze linguistico-comunicative in lingua straniera (Decreto 07.03.2012, Prot. 3889, aggiornato con Decreto del Direttore n. 118 del 28.02.2017).</p><p><a href=\"https://www.miur.gov.it/enti-certificatori-lingue-straniere\" target=\"_blank\" rel=\"noopener\">www.miur.gov.it/enti-certificatori-lingue-straniere →</a></p><p>Trinity College London — Italian Co-ordinator è inoltre un <strong>Ente accreditato dal Ministero per la formazione degli insegnanti</strong> secondo la normativa vigente. Le certificazioni Trinity possono essere valutate come crediti formativi per l'Esame di Stato.</p>"],
                ],
            ],
            'esami' => [
                'label' => 'Gli esami ISE e GESE',
                'fields' => [
                    'esami_title' => ['label' => 'Titolo', 'type' => 'html', 'default' => "Gli esami: <em>ISE</em> e <em>GESE</em>"],
                    'esami_intro' => ['label' => 'Introduzione', 'type' => 'html', 'default' => "Le certificazioni Trinity principalmente riconosciute dalle università italiane sono le <strong>ISE — Integrated Skills in English</strong>. Molti corsi di laurea riconoscono anche le certificazioni <strong>GESE — Graded Examinations in Spoken English</strong>."],
                    'ise_badge' => ['label' => 'Card ISE — badge', 'type' => 'text', 'default' => "Speciale Università"],
                    'ise_title' => ['label' => 'Card ISE — titolo', 'type' => 'text', 'default' => "ISE — Integrated Skills in English"],
                    'ise_text' => ['label' => 'Card ISE — testo', 'type' => 'html', 'default' => "Valutazione dell'uso integrato delle 4 abilità: <strong>Reading &amp; Writing</strong> e <strong>Speaking &amp; Listening</strong>. È la certificazione più riconosciuta dalle università italiane per l'idoneità linguistica e i crediti universitari."],
                    'gese_badge' => ['label' => 'Card GESE — badge', 'type' => 'html', 'default' => "Speaking &amp; Listening"],
                    'gese_title' => ['label' => 'Card GESE — titolo', 'type' => 'text', 'default' => "GESE — Graded Examinations in Spoken English"],
                    'gese_text' => ['label' => 'Card GESE — testo', 'type' => 'html', 'default' => "Esami orali graduati su 12 livelli, dalla prima scolarizzazione al livello C2. Riconosciuti da molti corsi di laurea e ideali per crediti formativi alla maturità e per i concorsi pubblici."],
                ],
            ],
            'cambridge' => [
                'label' => 'Cambridge e altre certificazioni',
                'fields' => [
                    'cambridge_title' => ['label' => 'Titolo', 'type' => 'html', 'default' => "Cambridge <em>Preparation Centre</em> e le altre certificazioni"],
                    'cambridge_text' => ['label' => 'Testo', 'type' => 'richtext', 'default' => "<p>A&amp;A Language Center è <strong>Preparation Centre di Cambridge English</strong>: prepariamo i nostri studenti agli esami Cambridge (KET, PET, First, Advanced, Proficiency) con percorsi mirati tenuti da docenti qualificati.</p><p>Prepariamo inoltre a tutte le principali certificazioni internazionali — <strong>IELTS, TOEFL, DELE (Instituto Cervantes), DELF/DALF (France Éducation International), Goethe-Zertifikat, PLIDA, CILS/CELI, TRKI–TORFL, CAPLE</strong> — i cui esami si sostengono presso i rispettivi enti certificatori ufficiali.</p>"],
                    'cambridge_highlight' => ['label' => 'Riquadro in evidenza', 'type' => 'html', 'default' => "ℹ️ <strong>In sintesi:</strong> siamo sede d'esami ufficiale <strong>solo per Trinity College London</strong> (n° 8241): gli esami Trinity si sostengono direttamente da noi. Per tutte le altre certificazioni siamo centro di preparazione."],
                ],
            ],
            'uses' => [
                'label' => 'A cosa servono',
                'fields' => [
                    'uses_title' => ['label' => 'Titolo', 'type' => 'text', 'default' => "A cosa servono le certificazioni Trinity"],
                    'use1_title' => ['label' => 'Card 1 — titolo', 'type' => 'text', 'default' => "Esame di maturità"],
                    'use1_text' => ['label' => 'Card 1 — testo', 'type' => 'textarea', 'default' => "Crediti formativi per l'Esame di Stato secondo la normativa vigente"],
                    'use2_title' => ['label' => 'Card 2 — titolo', 'type' => 'text', 'default' => "Università"],
                    'use2_text' => ['label' => 'Card 2 — testo', 'type' => 'textarea', 'default' => "Ammissione e crediti per l'idoneità linguistica in più di 1.600 facoltà e corsi di laurea"],
                    'use3_title' => ['label' => 'Card 3 — titolo', 'type' => 'text', 'default' => "Concorsi pubblici"],
                    'use3_text' => ['label' => 'Card 3 — testo', 'type' => 'textarea', 'default' => "Certificazioni valide per concorsi pubblici e riconosciute da aziende e istituzioni"],
                ],
            ],
            'cta' => [
                'label' => 'Invito finale (CTA)',
                'fields' => [
                    'cta_title' => ['label' => 'Titolo', 'type' => 'html', 'default' => "Iscriviti a uno dei nostri corsi di preparazione<br>agli esami ISE B1 · B2 · C1 — Trinity College London"],
                    'cta_text' => ['label' => 'Testo', 'type' => 'html', 'default' => "Diventa parte di A&amp;A per migliorare la tua carriera. Segreteria: <a href=\"tel:+39065743734\" style=\"color:var(--gold);font-weight:700;\">06 574 3734</a>"],
                    'cta_button' => ['label' => 'Bottone', 'type' => 'text', 'default' => "CONTATTACI →"],
                ],
            ],
        ],
    ],

    /* ═══════════════════ PER LE AZIENDE ═══════════════════ */
    'per-le-aziende' => [
        'label' => 'Per le Aziende',
        'sections' => [
            'seo' => [
                'label' => 'SEO (motori di ricerca)',
                'fields' => [
                    'meta_title' => ['label' => 'Titolo pagina (tag title)', 'type' => 'text', 'default' => "Corsi di Inglese Aziendali a Roma | Formazione Linguistica B2B"],
                    'meta_description' => ['label' => 'Meta description', 'type' => 'textarea', 'default' => "Corsi di lingue aziendali personalizzati a Roma per dipendenti, manager e team. Inglese commerciale, Business English, certificazioni CEFR. Lezioni in sede o online. Clienti: MEF, Confcommercio, H10 Hotels."],
                    'meta_keywords' => ['label' => 'Meta keywords', 'type' => 'textarea', 'default' => "corsi di inglese aziendali Roma, corsi di lingue aziendali Roma, formazione linguistica aziendale Roma, business English Roma, corsi inglese per dipendenti Roma, corsi inglese aziende in sede Roma, formazione linguistica B2B Roma, corso inglese commerciale Roma"],
                ],
            ],
            'hero' => [
                'label' => 'Testata',
                'fields' => [
                    'hero_title' => ['label' => 'Titolo', 'type' => 'html', 'default' => "Corsi di Lingue <em>Aziendali</em> a Roma"],
                    'hero_subtitle' => ['label' => 'Sottotitolo', 'type' => 'text', 'default' => "Formazione linguistica B2B su misura per il tuo team. \"We make that language your tool. Not your obstacle.\""],
                ],
            ],
            'intro' => [
                'label' => 'Introduzione',
                'fields' => [
                    'intro_label' => ['label' => 'Etichetta sezione', 'type' => 'text', 'default' => "Formazione aziendale"],
                    'intro_title' => ['label' => 'Titolo', 'type' => 'html', 'default' => "Formiamo il vostro <em>team</em>"],
                    'intro_text' => ['label' => 'Testo', 'type' => 'richtext', 'default' => "<p>A&amp;A Language Center opera dal 2002 con una consolidata esperienza nella <strong>formazione linguistica aziendale a Roma</strong>. Tra i nostri clienti figurano grandi aziende come <strong>MEF</strong>, <strong>Confcommercio</strong> e <strong>H10 Hotels</strong>, oltre a università, enti pubblici nazionali e locali, scuole pubbliche e private.</p><p>Siamo specializzati in corsi personalizzati di <strong>Inglese, Spagnolo, Francese, Tedesco, Portoghese, Russo, Arabo</strong> e <strong>Italiano per stranieri</strong>, progettati per rispondere alle esigenze concrete del mondo professionale.</p><p>I certificati rilasciati sono validi per concorsi pubblici, aggiornamento professionale e formazione del personale finanziata.</p>"],
                    'intro_image' => ['label' => 'Foto', 'type' => 'image', 'default' => "https://images.unsplash.com/photo-1542744173-8e7e53415bb0?auto=format&fit=crop&w=900&q=85"],
                ],
            ],
            'steps' => [
                'label' => 'Come funziona',
                'fields' => [
                    'steps_label' => ['label' => 'Etichetta sezione', 'type' => 'text', 'default' => "Il processo"],
                    'steps_title' => ['label' => 'Titolo', 'type' => 'html', 'default' => "Come <em>funziona</em>"],
                    'steps_subtext' => ['label' => 'Sottotitolo', 'type' => 'textarea', 'default' => "Un percorso strutturato in tre fasi per garantire risultati concreti e misurabili."],
                    'step1_title' => ['label' => 'Fase 1 — titolo', 'type' => 'text', 'default' => "Analisi delle esigenze"],
                    'step1_text' => ['label' => 'Fase 1 — testo', 'type' => 'textarea', 'default' => "Incontriamo il responsabile HR per individuare le esigenze dell'azienda, le aspettative del personale e gli obiettivi concreti da raggiungere."],
                    'step2_title' => ['label' => 'Fase 2 — titolo', 'type' => 'text', 'default' => "Programma su misura"],
                    'step2_text' => ['label' => 'Fase 2 — testo', 'type' => 'textarea', 'default' => "Strutturiamo il programma didattico seguendo i livelli CEFR di partenza del personale e i risultati attesi. Ogni modulo è calibrato sulle reali necessità comunicative."],
                    'step3_title' => ['label' => 'Fase 3 — titolo', 'type' => 'text', 'default' => "Formazione e certificazione"],
                    'step3_text' => ['label' => 'Fase 3 — testo', 'type' => 'textarea', 'default' => "Esame intermedio e finale per misurare i progressi. Al termine viene rilasciato un attestato con livello CEFR. Possibilità di certificazioni internazionali riconosciute."],
                ],
            ],
            'modalita' => [
                'label' => 'Modalità di erogazione',
                'fields' => [
                    'modalita_label' => ['label' => 'Etichetta sezione', 'type' => 'text', 'default' => "Erogazione"],
                    'modalita_title' => ['label' => 'Titolo', 'type' => 'html', 'default' => "Modalità <em>flessibili</em>"],
                    'modalita_subtext' => ['label' => 'Sottotitolo', 'type' => 'textarea', 'default' => "Ci adattiamo alle esigenze logistiche e organizzative della vostra azienda."],
                    'mod1_title' => ['label' => 'Modalità 1 — titolo', 'type' => 'text', 'default' => "In sede presso A&A"],
                    'mod1_text' => ['label' => 'Modalità 1 — testo', 'type' => 'textarea', 'default' => "I dipendenti seguono le lezioni nella nostra sede di Roma San Paolo, in un ambiente dedicato e attrezzato per una formazione immersiva."],
                    'mod2_title' => ['label' => 'Modalità 2 — titolo', 'type' => 'text', 'default' => "Presso la vostra sede"],
                    'mod2_text' => ['label' => 'Modalità 2 — testo', 'type' => 'textarea', 'default' => "I nostri docenti si recano direttamente nella vostra azienda, riducendo gli spostamenti del personale e ottimizzando i tempi."],
                    'mod3_title' => ['label' => 'Modalità 3 — titolo', 'type' => 'text', 'default' => "Videoconferenza in diretta"],
                    'mod3_text' => ['label' => 'Modalità 3 — testo', 'type' => 'textarea', 'default' => "Corsi online tramite Zoom, Teams o la piattaforma preferita dall'azienda. Stessa qualità didattica, massima flessibilità geografica."],
                    'mod4_title' => ['label' => 'Modalità 4 — titolo', 'type' => 'text', 'default' => "Individuali o di gruppo"],
                    'mod4_text' => ['label' => 'Modalità 4 — testo', 'type' => 'textarea', 'default' => "Corsi one-to-one per figure dirigenziali o piccoli gruppi omogenei per livello. Ogni formato è ottimizzato per il massimo apprendimento."],
                ],
            ],
            'clienti' => [
                'label' => 'Clienti',
                'fields' => [
                    'clienti_label' => ['label' => 'Etichetta', 'type' => 'text', 'default' => "Alcuni dei nostri clienti aziendali"],
                    'clienti_tags' => ['label' => 'Elenco clienti (uno per riga)', 'type' => 'lines', 'default' => "wpd Italia\nCIOFS-FP\nPromo.Ter Confcommercio Roma\nIdea Congress\nMEF — Ministero dell'Economia\nISCR\nFORMACAMERA\nH10 Hotels\nESC 2\nEasy Parking\nECA Italia"],
                ],
            ],
            'cta' => [
                'label' => 'Invito finale (CTA)',
                'fields' => [
                    'cta_label' => ['label' => 'Etichetta', 'type' => 'text', 'default' => "Formiamo il vostro team"],
                    'cta_title' => ['label' => 'Titolo', 'type' => 'text', 'default' => "Richiedete un preventivo gratuito"],
                    'cta_text' => ['label' => 'Testo', 'type' => 'textarea', 'default' => "Contattateci per ricevere un preventivo personalizzato. Risponderemo entro 24 ore lavorative."],
                    'cta_btn1' => ['label' => 'Bottone 1', 'type' => 'text', 'default' => "✦ Richiedi Preventivo Gratuito"],
                    'cta_btn2' => ['label' => 'Bottone 2', 'type' => 'text', 'default' => "✉ Scrivi via email"],
                ],
            ],
        ],
    ],

    /* ═══════════════════ LAVORA CON NOI ═══════════════════ */
    'lavora-con-noi' => [
        'label' => 'Lavora con Noi',
        'sections' => [
            'seo' => [
                'label' => 'SEO (motori di ricerca)',
                'fields' => [
                    'meta_title' => ['label' => 'Titolo pagina (tag title)', 'type' => 'text', 'default' => "Lavora con Noi — Cerchiamo Docenti Madrelingua a Roma"],
                    'meta_description' => ['label' => 'Meta description', 'type' => 'textarea', 'default' => "A&A Language Center cerca insegnanti madrelingua e bilingue qualificati per corsi di inglese, spagnolo, francese, tedesco e altre lingue a Roma San Paolo. Invia la tua candidatura."],
                ],
            ],
            'hero' => [
                'label' => 'Testata',
                'fields' => [
                    'hero_title' => ['label' => 'Titolo', 'type' => 'text', 'default' => "Lavora con Noi"],
                    'hero_subtitle' => ['label' => 'Sottotitolo', 'type' => 'text', 'default' => "Unisciti al nostro team di docenti qualificati"],
                ],
            ],
            'intro' => [
                'label' => 'Introduzione',
                'fields' => [
                    'intro_title' => ['label' => 'Titolo', 'type' => 'text', 'default' => "Entra nel team A&A"],
                    'intro_text' => ['label' => 'Testo', 'type' => 'richtext', 'default' => "<p>Sei un insegnante madrelingua o bilingue? Vuoi mettere a frutto le tue competenze linguistiche in un contesto dinamico e professionale? Se ami il tuo lavoro e credi nel valore dell'educazione, A&amp;A Language Center è il posto che fa per te.</p><p>Dal 2002 costruiamo un team di docenti appassionati e competenti che condividono una visione comune: rendere l'apprendimento delle lingue un'esperienza accessibile, efficace e piacevole per ogni studente.</p><p>La nostra scuola si trova nel cuore del quartiere <strong>San Paolo</strong> a Roma, vicino all'Università Roma Tre, in un ambiente cosmopolita e stimolante.</p>"],
                    'intro_image' => ['label' => 'Foto', 'type' => 'image', 'default' => "https://images.unsplash.com/photo-1507537297725-24a1c029d3ca?auto=format&fit=crop&w=900&q=85"],
                ],
            ],
            'requisiti' => [
                'label' => 'Cosa cerchiamo',
                'fields' => [
                    'req_title' => ['label' => 'Titolo', 'type' => 'text', 'default' => "Cosa cerchiamo"],
                    'req_intro' => ['label' => 'Introduzione', 'type' => 'textarea', 'default' => "Cerchiamo professionisti dell'insegnamento che soddisfino i seguenti requisiti. Non necessariamente tutti: valutiamo ogni candidatura nel suo insieme."],
                    'req1_title' => ['label' => 'Requisito 1 — titolo', 'type' => 'text', 'default' => "Madrelingua o bilingue"],
                    'req1_text' => ['label' => 'Requisito 1 — testo', 'type' => 'textarea', 'default' => "Competenza linguistica nativa o equivalente nella lingua insegnata, con capacità di trasmettere sfumature culturali e contestuali."],
                    'req2_title' => ['label' => 'Requisito 2 — titolo', 'type' => 'text', 'default' => "Formazione universitaria"],
                    'req2_text' => ['label' => 'Requisito 2 — testo', 'type' => 'textarea', 'default' => "Laurea in Lingue, Letterature Straniere, Scienze della Formazione o disciplina affine."],
                    'req3_title' => ['label' => 'Requisito 3 — titolo', 'type' => 'text', 'default' => "Certificazione di insegnamento"],
                    'req3_text' => ['label' => 'Requisito 3 — testo', 'type' => 'textarea', 'default' => "Possesso di certificazioni riconosciute come TEFL, CELTA, DELTA, PGCE o equivalenti per la lingua di interesse."],
                    'req4_title' => ['label' => 'Requisito 4 — titolo', 'type' => 'text', 'default' => "Esperienza nell'insegnamento"],
                    'req4_text' => ['label' => 'Requisito 4 — testo', 'type' => 'textarea', 'default' => "Esperienza documentabile nell'insegnamento delle lingue, preferibilmente in contesti eterogenei (adulti, aziende, studenti)."],
                    'req5_title' => ['label' => 'Requisito 5 — titolo', 'type' => 'text', 'default' => "Passione e capacità relazionali"],
                    'req5_text' => ['label' => 'Requisito 5 — testo', 'type' => 'textarea', 'default' => "Genuino interesse per l'insegnamento, pazienza, empatia e capacità di adattarsi ai diversi stili di apprendimento degli studenti."],
                    'req6_title' => ['label' => 'Requisito 6 — titolo', 'type' => 'text', 'default' => "Esperienza aziendale (preferenziale)"],
                    'req6_text' => ['label' => 'Requisito 6 — testo', 'type' => 'textarea', 'default' => "Conoscenza del contesto lavorativo e capacità di insegnare linguaggi specialistici (Business English, legal, medical, ecc.)."],
                ],
            ],
            'offerta' => [
                'label' => 'Cosa offriamo',
                'fields' => [
                    'offer_title' => ['label' => 'Titolo', 'type' => 'text', 'default' => "Cosa offriamo"],
                    'offer1_title' => ['label' => 'Card 1 — titolo', 'type' => 'text', 'default' => "Ambiente accogliente"],
                    'offer1_text' => ['label' => 'Card 1 — testo', 'type' => 'textarea', 'default' => "Un team internazionale e collaborativo, dove ogni docente è valorizzato e ascoltato."],
                    'offer2_title' => ['label' => 'Card 2 — titolo', 'type' => 'text', 'default' => "Flessibilità oraria"],
                    'offer2_text' => ['label' => 'Card 2 — testo', 'type' => 'textarea', 'default' => "Orari concordati in base alle disponibilità reciproche, per conciliare lavoro e vita privata."],
                    'offer3_title' => ['label' => 'Card 3 — titolo', 'type' => 'text', 'default' => "Aggiornamento continuo"],
                    'offer3_text' => ['label' => 'Card 3 — testo', 'type' => 'textarea', 'default' => "Accesso a materiali didattici aggiornati e opportunità di formazione professionale continua."],
                    'offer4_title' => ['label' => 'Card 4 — titolo', 'type' => 'text', 'default' => "Team internazionale"],
                    'offer4_text' => ['label' => 'Card 4 — testo', 'type' => 'textarea', 'default' => "Lavora a fianco di colleghi provenienti da tutto il mondo in un clima multiculturale stimolante."],
                ],
            ],
            'sede' => [
                'label' => 'La nostra sede',
                'fields' => [
                    'sede_title' => ['label' => 'Titolo', 'type' => 'text', 'default' => "La nostra sede"],
                    'sede_text' => ['label' => 'Testo', 'type' => 'richtext', 'default' => "<p>A&amp;A Language Center si trova nel vivace quartiere San Paolo di Roma, uno dei poli culturali e universitari più dinamici della città grazie alla presenza dell'Università Roma Tre.</p><p>La posizione è strategica e comodamente raggiungibile sia con i mezzi pubblici che in auto o bicicletta.</p>"],
                    'sede_image' => ['label' => 'Foto', 'type' => 'image', 'default' => "https://images.unsplash.com/photo-1515879218367-8466d910aaa4?auto=format&fit=crop&w=800&q=85"],
                ],
            ],
            'candidatura' => [
                'label' => 'Candidatura',
                'fields' => [
                    'cand_title' => ['label' => 'Titolo', 'type' => 'text', 'default' => "Invia la tua candidatura"],
                    'cand_text' => ['label' => 'Testo', 'type' => 'textarea', 'default' => "Manda il tuo curriculum vitae con una breve lettera di presentazione all'indirizzo email della direzione, oppure contattaci tramite Instagram. Valutiamo ogni candidatura con la massima attenzione e risponderemo entro pochi giorni lavorativi."],
                    'cand_note' => ['label' => 'Nota finale', 'type' => 'html', 'default' => "Invia il tuo CV a <strong>direzione@aealanguagecenter.it</strong> con oggetto \"Candidatura Docente — [Lingua]\""],
                ],
            ],
        ],
    ],

    /* ═══════════════════════ ISCRIVITI ═══════════════════════ */
    'iscriviti' => [
        'label' => 'Iscriviti / Test gratuito',
        'sections' => [
            'seo' => [
                'label' => 'SEO (motori di ricerca)',
                'fields' => [
                    'meta_title' => ['label' => 'Titolo pagina (tag title)', 'type' => 'text', 'default' => "Test di Livello Gratuito — Iscriviti a un Corso di Lingue a Roma"],
                    'meta_description' => ['label' => 'Meta description', 'type' => 'textarea', 'default' => "Prenota il tuo test di livello inglese (o altra lingua) gratuito a Roma. Iscriviti a un corso personalizzato A&A Language Center: ti ricontattiamo entro 24 ore."],
                    'meta_keywords' => ['label' => 'Meta keywords', 'type' => 'textarea', 'default' => "test livello inglese gratuito Roma, test livello lingue Roma, prenota test inglese Roma, iscrizione corso lingue Roma, iscriviti scuola di lingue Roma, entrance test inglese gratuito"],
                ],
            ],
            'hero' => [
                'label' => 'Testata',
                'fields' => [
                    'hero_title' => ['label' => 'Titolo', 'type' => 'text', 'default' => "Prenota il tuo Test di Livello Gratuito"],
                    'hero_text' => ['label' => 'Testo', 'type' => 'textarea', 'default' => "Test scritto e orale gratuito per i corsi di lingue a Roma. Compila il modulo — ti ricontattiamo entro 24 ore per trovare il corso più adatto a te."],
                ],
            ],
            'form' => [
                'label' => 'Modulo',
                'fields' => [
                    'form_title' => ['label' => 'Titolo modulo', 'type' => 'text', 'default' => "Modulo di contatto"],
                ],
            ],
        ],
    ],

    /* ═══════════════════════ GRAZIE ═══════════════════════ */
    'grazie' => [
        'label' => 'Grazie (dopo il modulo)',
        'sections' => [
            'contenuto' => [
                'label' => 'Contenuto',
                'fields' => [
                    'grazie_title' => ['label' => 'Titolo', 'type' => 'text', 'default' => "Richiesta inviata!"],
                    'grazie_text' => ['label' => 'Testo', 'type' => 'html', 'default' => "Grazie per averci contattato. Il nostro team ha ricevuto la tua richiesta\n            e ti risponderà entro <strong style=\"color:#001b3f;\">24 ore lavorative</strong>.<br>\n            Controlla anche la casella email — ti abbiamo inviato un riepilogo."],
                    'steps_title' => ['label' => 'Titolo passi successivi', 'type' => 'text', 'default' => "Cosa succede adesso?"],
                    'step1_text' => ['label' => 'Passo 1', 'type' => 'text', 'default' => "Il nostro staff esamina la tua richiesta"],
                    'step2_text' => ['label' => 'Passo 2', 'type' => 'text', 'default' => "Ti contattiamo via email o telefono per un colloquio conoscitivo"],
                    'step3_text' => ['label' => 'Passo 3', 'type' => 'text', 'default' => "Definiamo insieme il percorso di studio più adatto a te"],
                ],
            ],
        ],
    ],

    /* ═══════════════ LANDING: CORSI DI INGLESE ═══════════════ */
    'landing-inglese' => [
        'label' => 'Landing — Corsi di Inglese',
        'sections' => [
            'seo' => [
                'label' => 'SEO (motori di ricerca)',
                'fields' => [
                    'meta_title' => ['label' => 'Titolo pagina (tag title)', 'type' => 'text', 'default' => "Corsi di Inglese a Roma — Trinity, Cambridge, IELTS | A&A"],
                    'meta_description' => ['label' => 'Meta description', 'type' => 'textarea', 'default' => "Corsi di inglese a Roma San Paolo con docenti qualificati madrelingua e/o bilingue. Preparazione esami Trinity, Cambridge, IELTS, TOEFL. Lezioni individuali, mini gruppi, online. Test di livello gratuito."],
                ],
            ],
            'hero' => [
                'label' => 'Testata',
                'fields' => [
                    'hero_title' => ['label' => 'Titolo', 'type' => 'html', 'default' => "Corsi di <em>Inglese</em> a Roma"],
                    'hero_subtitle' => ['label' => 'Sottotitolo', 'type' => 'text', 'default' => "Trinity, Cambridge, IELTS, TOEFL. Lezioni con docenti qualificati madrelingua e/o bilingue. Tutti i livelli CEFR — dall'A1 al C2. Sede ufficiale esami Trinity College London n° 8241."],
                ],
            ],
            'faq' => [
                'label' => 'Domande frequenti (FAQ)',
                'fields' => [
                    'faq_title' => ['label' => 'Titolo FAQ', 'type' => 'text', 'default' => "Domande frequenti sui corsi di inglese a Roma"],
                    'faq_subtitle' => ['label' => 'Sottotitolo FAQ', 'type' => 'text', 'default' => "Le risposte ai dubbi più comuni di chi sta valutando di iscriversi a un corso di inglese a Roma."],
                    'faq_items' => ['label' => 'Domande e risposte', 'type' => 'faq', 'default' => [
                        ['q' => "Quanto costa un corso di inglese a Roma in A&A Language Center?", 'a' => "<p>I prezzi variano in base alla modalità (individuale, mini gruppo, online), al numero di ore e al livello target. Una lezione individuale parte da circa €30/ora, un mini gruppo da €15/ora. Puoi consultare il <a href=\"/corsi\">catalogo corsi</a> per i pacchetti completi con prezzo trasparente.</p>"],
                        ['q' => "Quanto dura un corso di inglese per ottenere una certificazione?", 'a' => "<p>Dipende dal tuo livello di partenza e dal target. Mediamente per passare da un livello CEFR al successivo servono 80–120 ore di studio. Per un Cambridge B2 First partendo da B1 si calcolano 4–6 mesi di corso a frequenza bisettimanale. Il test di livello gratuito è il punto di partenza per costruire il tuo piano.</p>"],
                        ['q' => "Avete corsi di inglese per docenti di ruolo?", 'a' => "<p>Sì. A&amp;A Language Center è ente accreditato MIUR come ente di formazione. Offriamo corsi di inglese per docenti, sia per la formazione personale sia per la preparazione di certificazioni linguistiche valide ai fini concorsuali.</p>"],
                        ['q' => "Sostenete gli esami Trinity direttamente nella vostra sede?", 'a' => "<p>Sì. Siamo <strong>Sede d'Esame ufficiale Trinity College London n° 8241</strong>. Organizziamo sessioni GESE (Graded Examinations in Spoken English) e ISE (Integrated Skills in English) durante tutto l'anno. Gli esami si sostengono direttamente nella nostra sede di Viale Leonardo da Vinci 193 a Roma.</p>"],
                        ['q' => "Dove si trova la scuola e con quali mezzi pubblici si raggiunge?", 'a' => "<p>Siamo in <strong>Viale Leonardo da Vinci 193, 00145 Roma</strong>, nel quartiere San Paolo. Siamo a pochi passi dalle fermate metro <strong>San Paolo</strong> (linea B) e <strong>Marconi</strong>, ben collegati anche con i quartieri EUR, Garbatella e Ostiense.</p>"],
                        ['q' => "Offrite corsi di inglese intensivi?", 'a' => "<p>Sì. Oltre ai corsi standard a frequenza bisettimanale, organizziamo corsi intensivi (anche tutti i giorni) e ultra-intensivi per chi ha esigenze rapide: preparazione last-minute IELTS, colloqui di lavoro, trasferimenti all'estero.</p>"],
                        ['q' => "Il test di livello è davvero gratuito?", 'a' => "<p>Sì, completamente gratuito e senza impegno. Il nostro Entrance Test si compone di una parte scritta (grammatica, lettura, comprensione) e una parte orale (5–10 minuti con un docente qualificato madrelingua o bilingue). Al termine ricevi una valutazione CEFR dettagliata e una proposta di corso. Puoi <a href=\"/iscriviti\">prenotarlo qui</a>.</p>"],
                        ['q' => "Fate corsi di inglese per bambini e ragazzi?", 'a' => "<p>Sì. Abbiamo corsi dedicati a bambini (5–10 anni) e ragazzi (11–17 anni), con metodologia ludica per i più piccoli e preparazione esami Trinity/Cambridge YLE per i ragazzi. I corsi sono in piccoli gruppi omogenei per età e livello.</p>"],
                    ]],
                ],
            ],
            'cta' => [
                'label' => 'Invito finale (CTA)',
                'fields' => [
                    'cta_label' => ['label' => 'Etichetta', 'type' => 'text', 'default' => "Prossimo step"],
                    'cta_title' => ['label' => 'Titolo', 'type' => 'text', 'default' => "Inizia il tuo corso di inglese a Roma"],
                    'cta_text' => ['label' => 'Testo', 'type' => 'textarea', 'default' => "Prenota un test di livello gratuito. In 30 minuti scopri il tuo livello CEFR e il percorso più adatto a te."],
                ],
            ],
        ],
    ],

    /* ═══════════ LANDING: ITALIANO PER STRANIERI ═══════════ */
    'landing-italiano-stranieri' => [
        'label' => 'Landing — Italiano per Stranieri',
        'sections' => [
            'seo' => [
                'label' => 'SEO (motori di ricerca)',
                'fields' => [
                    'meta_title' => ['label' => 'Titolo pagina (tag title)', 'type' => 'text', 'default' => "Italiano per Stranieri a Roma — Italian Courses in Rome | A&A"],
                    'meta_description' => ['label' => 'Meta description', 'type' => 'textarea', 'default' => "Corsi di italiano per stranieri a Roma: tutti i livelli, preparazione CILS e PLIDA. Italian courses in Rome for foreigners with mother-tongue Italian teachers. Free placement test."],
                ],
            ],
            'faq' => [
                'label' => 'Domande frequenti (FAQ)',
                'fields' => [
                    'faq_title' => ['label' => 'Titolo FAQ', 'type' => 'text', 'default' => "FAQ — Italian Courses & Italiano per Stranieri"],
                    'faq_items' => ['label' => 'Domande e risposte', 'type' => 'faq', 'default' => [
                        ['q' => "Quanto costa un corso di italiano per stranieri a Roma?", 'a' => "<p>I prezzi partono da €15/ora in mini gruppo e da €30/ora individuale. Offriamo pacchetti settimanali intensivi (15–20 ore/settimana) e corsi mensili a frequenza bisettimanale. <a href=\"/corsi\">Vedi i corsi disponibili</a>.</p>"],
                        ['q' => "How much does an Italian course in Rome cost?", 'a' => "<p>Prices start at €15/hour for small group classes and €30/hour for one-to-one lessons. We offer intensive weekly packages (15–20 hours/week) and monthly courses (2 lessons per week). <a href=\"/corsi\">See available courses</a>.</p>"],
                        ['q' => "Do you prepare for CILS B1 (Italian citizenship)?", 'a' => "<p>Yes. We have a specific course for CILS B1 Cittadinanza, the level required to apply for Italian citizenship. It is an intensive 30–60 hour preparation covering all four exam parts (listening, reading, writing, speaking) with mock exams.</p>"],
                        ['q' => "Preparate al CILS B1 per la cittadinanza italiana?", 'a' => "<p>Sì. Abbiamo un corso specifico per <strong>CILS B1 Cittadinanza</strong>, il livello richiesto per la domanda di cittadinanza italiana. È una preparazione intensiva (30–60 ore) che copre tutte e quattro le parti dell'esame con simulazioni d'esame complete.</p>"],
                        ['q' => "Can I take Italian classes online?", 'a' => "<p>Yes. We offer Italian classes online with the same quality as in-person lessons. Live video classes with native Italian teachers — perfect for students still abroad before moving to Italy.</p>"],
                        ['q' => "Where is the school? Is it easy to reach?", 'a' => "<p>We are at <strong>Viale Leonardo da Vinci 193, 00145 Rome</strong> — San Paolo district. Two minutes from the San Paolo metro station (Line B). About 15 minutes from Roma Termini and 10 minutes from EUR.</p>"],
                        ['q' => "Are your Italian teachers native speakers?", 'a' => "<p>Yes. All our Italian teachers are native Italian speakers, qualified to teach Italian as a second language (DITALS / CEDILS / DILS-PG certifications), with years of experience teaching adult foreign learners.</p>"],
                    ]],
                ],
            ],
            'cta' => [
                'label' => 'Invito finale (CTA)',
                'fields' => [
                    'cta_label' => ['label' => 'Etichetta', 'type' => 'text', 'default' => "Next step · Prossimo passo"],
                    'cta_title' => ['label' => 'Titolo', 'type' => 'text', 'default' => "Start your Italian journey in Rome"],
                    'cta_text' => ['label' => 'Testo', 'type' => 'html', 'default' => "Book your free placement test — discover your Italian level and the best course for you.<br>Prenota il test di livello gratuito."],
                ],
            ],
        ],
    ],

    /* ═══════════ LANDING: CORSI AZIENDALI ═══════════ */
    'landing-aziendali' => [
        'label' => 'Landing — Corsi Aziendali',
        'sections' => [
            'seo' => [
                'label' => 'SEO (motori di ricerca)',
                'fields' => [
                    'meta_title' => ['label' => 'Titolo pagina (tag title)', 'type' => 'text', 'default' => "Corsi di Inglese Aziendali a Roma — Business English | A&A"],
                    'meta_description' => ['label' => 'Meta description', 'type' => 'textarea', 'default' => "Corsi di inglese aziendali a Roma per dipendenti, manager e team. Business English, formazione linguistica B2B in sede o online. Clienti: MEF, Confcommercio, H10 Hotels."],
                ],
            ],
            'hero' => [
                'label' => 'Testata',
                'fields' => [
                    'hero_title' => ['label' => 'Titolo', 'type' => 'html', 'default' => "Corsi di <em>Inglese Aziendali</em> a Roma"],
                    'hero_subtitle' => ['label' => 'Sottotitolo', 'type' => 'text', 'default' => "Formazione linguistica B2B in sede o online. Business English, lingue per il lavoro. Clienti: MEF, Confcommercio, H10 Hotels."],
                ],
            ],
            'faq' => [
                'label' => 'Domande frequenti (FAQ)',
                'fields' => [
                    'faq_title' => ['label' => 'Titolo FAQ', 'type' => 'text', 'default' => "Domande frequenti — Corsi aziendali"],
                    'faq_subtitle' => ['label' => 'Sottotitolo FAQ', 'type' => 'text', 'default' => "Le risposte alle domande più comuni di HR manager, training manager e responsabili formazione."],
                    'faq_items' => ['label' => 'Domande e risposte', 'type' => 'faq', 'default' => [
                        ['q' => "Quanto costa un corso di inglese aziendale a Roma?", 'a' => "<p>I costi dipendono dal numero di partecipanti, dalle ore totali, dalla modalità (in sede o online) e dal livello del corso. Per un preventivo personalizzato <a href=\"/contattaci\">contattaci</a> — rispondiamo entro 24 ore con un'analisi dei fabbisogni e una proposta dettagliata.</p>"],
                        ['q' => "Quanto dura tipicamente un corso aziendale?", 'a' => "<p>I cicli tipici sono 40, 60 o 90 ore, distribuite su 3–9 mesi a seconda dell'intensità. Lavoriamo bene anche su programmi annuali e biennali con monitoraggio continuo del livello CEFR.</p>"],
                        ['q' => "Venite a fare lezione direttamente nella nostra sede?", 'a' => "<p>Sì. I nostri docenti si spostano nella sede aziendale a Roma e provincia. Per sedi fuori Roma valutiamo modalità mista (in presenza + online) per ottimizzare i costi.</p>"],
                        ['q' => "Rilasciate certificazioni utili per la formazione finanziata?", 'a' => "<p>Sì. Rilasciamo attestati nominativi validi per concorsi pubblici, aggiornamento professionale, formazione del personale finanziata (Fondimpresa, Fondoprofessioni, Fondirigenti) e crediti formativi.</p>"],
                        ['q' => "Possiamo fare un test di livello per tutti i dipendenti?", 'a' => "<p>Sì. Effettuiamo un Entrance Test scritto + colloquio orale individuale per ciascun dipendente, completamente gratuito. Forniamo poi un report aggregato con la distribuzione CEFR del team e suggerimenti di clusterizzazione.</p>"],
                        ['q' => "Possiamo usare fondi formativi per finanziare il corso?", 'a' => "<p>Sì. Lavoriamo con i principali fondi interprofessionali (Fondimpresa, Fondoprofessioni, Fondirigenti). Ti aiutiamo nella preparazione della documentazione necessaria per la formazione finanziata.</p>"],
                        ['q' => "Quali aziende avete già formato?", 'a' => "<p>Tra i nostri clienti: <strong>MEF</strong> (Ministero dell'Economia e delle Finanze), <strong>Confcommercio</strong>, <strong>H10 Hotels</strong>, oltre a numerosi studi professionali, PMI, scuole pubbliche e private, università ed enti pubblici nazionali e locali.</p>"],
                    ]],
                ],
            ],
            'cta' => [
                'label' => 'Invito finale (CTA)',
                'fields' => [
                    'cta_label' => ['label' => 'Etichetta', 'type' => 'text', 'default' => "Prossimo step"],
                    'cta_title' => ['label' => 'Titolo', 'type' => 'text', 'default' => "Chiedi un preventivo aziendale"],
                    'cta_text' => ['label' => 'Testo', 'type' => 'textarea', 'default' => "Compila il modulo o chiamaci al 06 5743734. Analizziamo i fabbisogni del tuo team e prepariamo una proposta su misura entro 48 ore."],
                ],
            ],
        ],
    ],

];
