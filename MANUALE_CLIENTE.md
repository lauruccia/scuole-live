# Manuale operativo scuoleLive

**Versione:** 2026-05-07 (edizione cliente)
**Destinatari:** segreteria, amministrazione, docenti, studenti
**URL produzione:** `https://aeacenter.it`
**URL pannello amministrazione:** `https://aeacenter.it/admin`

> Per qualsiasi configurazione tecnica avanzata o anomalia che non rientra in questo manuale, contatta il referente tecnico del progetto.

---

## Indice

1. Introduzione e architettura
2. Primo accesso e setup iniziale
3. Gestione studenti
4. Gestione corsi e didattica
5. Gestione contratti
6. Gestione lezioni
7. Gestione pagamenti
8. CRM e gestione lead
9. Comunicazioni e email
10. Reports e statistiche
11. Area studente
12. Operazioni di sistema
13. FAQ e troubleshooting

---

## 1. Introduzione e architettura

### 1.1 Cosa fa scuoleLive

scuoleLive è il software gestionale della scuola di lingue. Copre l'intero ciclo:

- **Pubblico**: pagine vetrina (`/`, `/iscriviti`, `/corsi`) per acquisire nuovi studenti via form di interesse o checkout diretto.
- **Segreteria/Amministrazione**: gestione studenti, contratti, lezioni, pagamenti, comunicazioni, report, CRM lead.
- **Docenti**: calendario lezioni, valutazione compiti, registro presenze.
- **Studenti**: area riservata con calendario, contratto firmabile, materiali, compiti, quiz, scadenze pagamenti.

### 1.2 I tre pannelli (panel)

Ogni utente accede a un pannello in base al ruolo:

| URL | Pannello | Chi accede |
|---|---|---|
| `/admin` | Amministrazione | Segreteria, Amministrazione |
| `/docente` | Area docenti | Docente |
| `/studente` | Area studenti | Studente |

### 1.3 Ruoli (sintesi rapida)

- **Amministrazione**: gestisce contratti, pagamenti, configurazione corsi, comunicazioni, CRM, report completi.
- **Segreteria**: gestisce studenti, lead, comunicazioni, quasi tutto tranne le configurazioni tecniche e i dati economici dei docenti.
- **Docente**: vede solo i propri studenti, le proprie lezioni, valuta i propri compiti.
- **Studente**: vede solo il proprio contratto, lezioni, materiali, rate, quiz.

I ruoli per il personale interno (Amministrazione, Segreteria, Docente) si assegnano da `/admin/users` modificando l'utente. Per i docenti il ruolo si assegna automaticamente quando si crea un nuovo Docente da `/admin/teachers`. Gli studenti ricevono il ruolo "Studente" automaticamente quando vengono creati (manualmente o tramite checkout pubblico).

---

## 2. Primo accesso e setup iniziale

> Da fare **una sola volta**, prima di rendere il sito accessibile al pubblico.

### 2.1 Compilare Impostazioni scuola

Login → `/admin` → menu laterale **Configurazione → Impostazioni scuola**.

Compila TUTTI i campi anche se sembrano cosmetici — molti sono usati nelle email automatiche e nelle ricevute PDF:

**Identità scuola**
- Nome commerciale (es. "A&A Language Center")
- Ragione sociale (es. "A&A Language Center Srl")
- Indirizzo, CAP, Città
- Telefono, Mobile/WhatsApp
- Sito web pubblico
- Email pubblica

**Dati bancari (bonifico)**
- IBAN (validato con checksum mod-97 — se è errato il sistema te lo dice)
- Intestatario conto

**Ricevute PDF**
- Etichetta documento ("RICEVUTA" / "QUIETANZA DI PAGAMENTO")
- Sottotitolo header
- Testo di ringraziamento
- Disclaimer legale

**Metodi di pagamento** (vedi sez. 7)
- Toggle Bonifico bancario, Carta (Stripe), PayPal

**Firma digitale** — se vuoi che gli studenti possano firmare il contratto via OTP email.

Clicca **Salva impostazioni**. Da questo momento le email pubbliche e le ricevute leggeranno questi dati.

### 2.2 Verificare gli email template

Menu **Configurazione → Email Templates**. Devi avere almeno questi 5 attivi:

- `student.created` — Benvenuto studente (contiene la password al primo accesso)
- `installment.overdue` — Promemoria rata in scadenza/scaduta
- `lesson.recovery` — Recupero lezione
- `lesson.cancelled` — Lezione annullata
- `purchase.confirmation` — Conferma acquisto corso

Per ogni template, clicca **Anteprima** per vedere come arriverà al destinatario, e **Test** per inviartelo a te.

### 2.3 Configurare Google Calendar (opzionale ma consigliato)

Se vuoi che le lezioni online creino automaticamente un Google Meet, il collegamento OAuth con Google deve essere fatto una volta sola tramite l'apposita pagina di configurazione dedicata. Questa procedura viene di norma eseguita dal referente tecnico in fase di go-live.

Una volta collegato l'account Google, la scuola riceve automaticamente i link Meet su ogni lezione online creata in piattaforma.

Se l'integrazione Meet smette di funzionare (es. account scollegato, password cambiata sull'account Google scuola), contatta il referente tecnico: il rinnovo del collegamento richiede pochi minuti.

### 2.4 Configurare i cron su cPanel

Da `cPanel → Cron Jobs` (sezione "Advanced") aggiungi DUE entry:

**Cron 1 — Schedule (obbligatorio)**
```
* * * * * /usr/local/bin/php /home/aeacenter/scuole_app/artisan schedule:run >> /dev/null 2>&1
```
Senza questo NON funzionano: backup notturni, promemoria rate, follow-up lead, monitor backup, notifiche programmate.

**Cron 2 — Queue worker (obbligatorio per le email)**
```
* * * * * /usr/local/bin/php /home/aeacenter/scuole_app/artisan queue:work --stop-when-empty --tries=3 >> /dev/null 2>&1
```
Senza questo le email automatiche (bonifico, conferma iscrizione, password reset) NON partono.

### 2.5 SPF / DKIM / DMARC

Per evitare che le email finiscano in spam, configura sul DNS del dominio mittente (`aealingue.it`):

- **SPF**: `v=spf1 include:_spf.aruba.it ~all`
- **DKIM**: chiedi al provider mail i record da inserire (Aruba lo genera dal pannello mail)
- **DMARC**: `v=DMARC1; p=quarantine; rua=mailto:postmaster@aealingue.it`

Verifica con strumenti tipo `mail-tester.com`.

### 2.6 Verificare il backup

Menu **Backup** → controlla che ci sia almeno un .zip recente. Se la lista è vuota:

1. Aspetta la notte successiva (backup automatico h. 02:00)
2. Oppure forza ora: cPanel → Cron Jobs → "Run command now" sulla riga di backup

---

## 3. Gestione studenti

### 3.1 Visualizzare gli studenti

Menu **Studenti**. Vedi una tabella con: nome, email, telefono, contratti attivi, soft-delete (cestino).

Filtri rapidi: per età (minorenni/maggiorenni), per scuola, per disiscritti GDPR.

### 3.2 Aggiungere uno studente manualmente

Menu **Studenti → Crea**.

Campi obbligatori:
- Nome, Cognome, Email
- Telefono (consigliato per WhatsApp/SMS)

Se è **minorenne**, attiva il toggle "Studente minorenne" e compila la sezione genitori (nome, cognome, email, telefono). Senza questi dati non puoi attivare un contratto.

Per studenti azienda (se la scuola fa corsi B2B):
- `employer_name` (nome azienda)
- `employer_vat_number` (P.IVA)

> Quando salvi, se non esiste già un User con quella email, il sistema crea **automaticamente** uno User con il ruolo `Studente` e una password random sicura (16 caratteri). La password viene inviata via email al template `student.created`. **Lo studente è obbligato a cambiarla al primo login**.

### 3.3 Studente auto-creato dal pagamento online

Quando un visitatore pubblico compra un corso online (`/corsi/[corso]`):

1. Sceglie il corso → compila form → completa pagamento (o sceglie bonifico)
2. Il sistema crea il `CoursePurchase` (in stato `pending` per bonifico, `paid` per Stripe/PayPal)
3. Crea o trova lo `Student` (per email)
4. Crea o trova lo `User` collegato (con ruolo `Studente`)
5. Crea il `Contract` in stato `pending`
6. Invia l'email di conferma con le credenziali

Lo studente loggato vedrà subito il proprio contratto in `/studente`. **A te tocca completarlo**: assegnare docente, definire slot orari, generare lezioni (vedi sez. 5).

### 3.4 Modificare uno studente

Menu **Studenti → [nome] → Modifica**.

Cambi tipici:
- Aggiornare telefono
- Aggiungere/correggere CF (validato con checksum italiano)
- Marcare come disiscritto manualmente
- Cambiare email → si propaga al User collegato

### 3.5 Disiscrizione GDPR

Ogni email automatica ha un link "Disiscriviti" nel footer (ex art. 13 GDPR).

Cliccando il link lo studente arriva su `/unsubscribe/[token]`, conferma, e da quel momento NON riceve più email automatiche né bulk dalla scuola.

Lato admin: menu **Studenti disiscritti** (read-only). Per "riabilitare" uno studente (es. errore o cambio idea), seleziona la riga → **Riabilita**.

---

## 4. Gestione corsi e didattica

### 4.1 Cosa è un "corso"

Un corso è il modello/template che lo studente acquista. Definisce:
- Nome (es. "Inglese B1 — Pacchetto 30 ore")
- Lingua, livello, tipologia (individuale, di gruppo, online)
- Ore totali (`hours_purchased`)
- Quota iscrizione (`enrollment_fee`) e quota corso (`course_price`)
- Visibilità pubblica e attivazione

Il prezzo totale (visibile al pubblico) = `enrollment_fee` + `course_price`.

### 4.2 Creare un corso

Menu **Corsi → Crea**.

Campi:
- **Nome** — quello che vede il pubblico
- **Lingua** — collegata al model `Language`
- **Lezione type** — "individuale", "di gruppo", "online", "intensivo"
- **Ore acquistate** — totale ore previste
- **Quota iscrizione** e **Prezzo corso**
- **Pubblico** (`is_public`) — se mostrato sul checkout `/corsi`
- **Attivo** (`is_active`) — se può essere usato in nuovi contratti
- **Descrizione corta** — tagline
- **Descrizione completa** — testo lungo, supporta markdown

### 4.3 Pubblicare o nascondere un corso

Toggle `is_public` → quando `false`, il corso non appare in `/corsi` ma rimane utilizzabile lato admin per contratti manuali.

Toggle `is_active` → quando `false`, il corso è "archiviato": non si possono creare nuovi contratti basati su di esso, ma quelli esistenti continuano.

### 4.4 Filtri sul catalogo pubblico

Il catalogo `/corsi` ha 2 filtri visibili al pubblico: **Ore** e **Tipologia**. I valori sono auto-popolati dai corsi pubblici esistenti.

### 4.5 Quiz di livello

Menu **Didattica → Quiz di livello**.

I quiz di livello vengono proposti ai potenziali studenti o agli iscritti per valutare il livello di partenza prima di assegnare un corso.

**Creare una domanda:**
1. Menu **Quiz di livello → Crea**
2. Compila: testo della domanda, opzioni di risposta (A/B/C/D), risposta corretta, livello linguistico associato (A1, A2, B1, B2, C1, C2), lingua

**Come funziona per lo studente:**
Lo studente accede al quiz dal proprio pannello `/studente` → sezione **Test di livello**. Al termine riceve un risultato con il livello consigliato. Il risultato è visibile anche alla segreteria nel profilo dello studente.

**Uso consigliato:** creare almeno 10-15 domande per lingua e distribuirle su tutti i livelli per ottenere una valutazione accurata.

---

## 5. Gestione contratti

### 5.1 Cosa è un contratto

Il contratto è il "patto" tra scuola e studente: contiene il corso scelto, il prezzo concordato, i dati billing, gli orari, le rate.

Stati possibili:
- `pending` — appena creato, in attesa di completamento
- `active` — completato e operativo
- `completed` — corso finito
- `cancelled` — annullato

### 5.2 Creare un contratto manualmente

Menu **Contratti → Crea**.

Step 1 — **Beneficiari**: aggiungi uno o più studenti.

Step 2 — **Corso e prezzi**: scegli il corso → i prezzi vengono auto-compilati ma puoi modificarli (es. sconto per fedeltà).

Step 3 — **Dati billing**: chi paga (privato o azienda). Validazione automatica codice fiscale e P.IVA italiana.

Step 4 — **Slot orari** (Relation Manager "Lessons Slots"): definisci giorno settimanale + ora + docente per ogni slot. Es. "Lunedì 18:00-19:30, Prof.ssa Bianchi".

Step 5 — **Rate (installments)**: configura come dividere il pagamento.

### 5.3 Generare le lezioni dagli slot

Una volta definiti gli slot e la data inizio:

Pulsante **"Genera lezioni"** → crea automaticamente N occorrenze settimanali fino al raggiungimento delle ore acquistate, saltando i giorni di chiusura scuola (vedi sez. 6.5).

Se vuoi rigenerare: elimina prima le lezioni future esistenti, poi rigenera.

### 5.4 Stampare il contratto in PDF

Tasto **Stampa** sul contratto:
- **Stampa (HTML)** — versione web pronta da stampare via browser
- **PDF inline** — apre il PDF nel browser
- **Download PDF** — scarica `Contratto_NN.pdf`

Il PDF legge tutti i dati da Impostazioni scuola. Se trovi qualcosa di errato, modifica le Impostazioni e ristampa.

### 5.5 Firma digitale OTP

Solo se hai abilitato in Impostazioni → "Firma digitale".

Lo studente entra in `/studente/contratto/N` → clicca "Firma il contratto" → riceve un OTP via email valido 15 minuti → inserisce OTP → contratto firmato (visibile in admin con icona ✅).

---

## 6. Gestione lezioni

### 6.1 Calendario

Menu **Calendario lezioni** mostra il fullcalendar mensile/settimanale con tutte le lezioni di tutti i contratti attivi. Colori: ogni docente ha un colore. Click su una lezione → modal con dettagli.

### 6.2 Spostare o annullare una lezione

Click sulla lezione → modal:
- **Sposta** — cambia data/ora
- **Annulla** — segna come `cancelled`, opzionalmente invia email `lesson.cancelled`

### 6.3 Recupero lezione

Menu **Recuperi** → seleziona lezione annullata → "Pianifica recupero" → scegli data/ora futura → genera la lezione di recupero collegata a quella originale.

### 6.4 Materiali e visibilità per contratto

Menu **Materiali corso** → carica file (PDF, audio, video link YouTube).

Per ogni materiale puoi decidere a quali contratti è visibile dal pannello studente. Use case: un PDF "verbi irregolari B1" visibile solo agli studenti del corso B1.

### 6.5 Giorni di chiusura

Menu **Didattica → Giorni di chiusura**.

Qui definisci i periodi in cui la scuola è chiusa (festività, vacanze, eventi). Le date inserite vengono automaticamente **saltate** dalla generazione delle lezioni (sez. 5.3): se un giorno di lezione cade in una chiusura, quella occorrenza non viene creata.

**Creare una chiusura:**
1. Menu **Giorni di chiusura → Crea**
2. Inserisci data inizio, data fine e motivazione (es. "Chiusura estiva", "Natale 2026")
3. Salva

> ⚠️ Le chiusure influenzano solo le lezioni generate **dopo** l'inserimento. Se hai già generato le lezioni, elimina quelle future e rigenera per applicare le nuove chiusure.

### 6.6 Compiti (homework)

Menu **Compiti** → crea homework con titolo, descrizione, studente/i destinatari e scadenza.

Lo studente vede il compito in `/studente/compiti`, carica la propria submission (file o testo). Il docente valuta dalla colonna "Submissions" → grade + feedback → lo studente riceve email con il voto.

---

## 7. Gestione pagamenti

### 7.1 Flusso checkout pubblico

Lo studente:
1. Apre `/corsi` → sceglie un corso → clicca "Iscriviti"
2. Compila form (privato o azienda, dati billing, accetta privacy)
3. Sceglie metodo di pagamento
4. Stripe / PayPal → reindirizzamento al gateway → torna sul sito
5. Bonifico → atterra sulla pagina istruzioni e riceve email con IBAN + causale

### 7.2 Toggle metodi di pagamento

Menu **Configurazione → Impostazioni scuola → Metodi di pagamento**.

Tre toggle:
- **Bonifico bancario** (default ON)
- **Carta di credito (Stripe)** (default OFF)
- **PayPal** (default OFF)

Se tutti e 3 sono OFF, il checkout mostra "Al momento non è possibile completare il pagamento online — contatta la segreteria".

> Per attivare Stripe/PayPal devi prima configurare le chiavi API e i webhook (vedi sez. 7.5 e 7.6). Senza chiavi i toggle funzionano ma il pagamento andrà in errore.

### 7.3 Conferma manuale di un bonifico

Quando arriva il bonifico in conto:

1. Menu **Pagamenti corso (CoursePurchases)** → trova quello con il `bank_transfer_ref` corrispondente alla causale
2. Click sulla riga → cambia `payment_status` da `pending` a `paid`, imposta `paid_at` = oggi
3. Salva → il sistema crea automaticamente il contratto e invia email di conferma allo studente

### 7.4 Rate (installments)

Per contratti rateizzati:

- Menu **Rate** → vedi tutte le rate di tutti i contratti attivi
- Filtri: pagate, in scadenza, scadute
- Click su una rata → marca come pagata, scarica ricevuta PDF

Promemoria automatico via email il giorno della scadenza (se attivato in Impostazioni → Promemoria rate).

### 7.5 Configurare Stripe (per attivarlo)

Sul dashboard Stripe (modalità Live):
1. Crea API keys: copia `Publishable key` e `Secret key`
2. Crea Webhook endpoint:
   - URL: `https://aeacenter.it/webhook/stripe`
   - Eventi: `checkout.session.completed`, `checkout.session.expired`
   - Copia il Signing Secret

Sul server, nel file `.env`:
```
STRIPE_KEY=pk_live_xxxxxxxxxxxx
STRIPE_SECRET=sk_live_xxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxx
```

Poi attiva il toggle Stripe in Impostazioni scuola.

### 7.6 Configurare PayPal (per attivarlo)

Sul dashboard PayPal Developer (modalità Live):
1. Crea App → copia `Client ID` e `Secret`
2. Crea Webhook → URL `https://aeacenter.it/webhook/paypal` → evento `PAYMENT.CAPTURE.COMPLETED` → copia Webhook ID

Sul server, nel file `.env`:
```
PAYPAL_CLIENT_ID=AbCdEf...
PAYPAL_SECRET=EaBcDe...
PAYPAL_BASE_URL=https://api-m.paypal.com
PAYPAL_WEBHOOK_ID=WH-12AB34CD...
```

---

## 8. CRM e gestione lead

### 8.1 Cos'è un lead

Un **lead** è un potenziale studente che ha espresso interesse (compilato il form `/iscriviti` o contattato la scuola) ma non ha ancora acquistato un corso. Il CRM ti permette di tenere traccia del processo di conversione da "interessato" a "studente pagante".

### 8.2 Vista Kanban lead

Menu **CRM → Lead**.

I lead sono visualizzati in un **kanban** con colonne che rappresentano gli stati del processo commerciale:

- **Nuovo** — appena arrivato (form pubblico o inserito manualmente)
- **Contattato** — è stato fatto un primo contatto
- **In trattativa** — ha ricevuto un'offerta/preventivo
- **Vinto** — ha acquistato il corso (diventa studente)
- **Perso** — non è interessato o ha scelto un competitor

Trascina la card da una colonna all'altra per aggiornare lo stato.

### 8.3 Creare un lead manualmente

Menu **CRM → Lead → Crea**.

Campi principali:
- Nome e cognome
- Email, telefono
- Lingua di interesse
- Corso di interesse
- Note interne
- Fonte (form web, passaparola, social, ecc.)

### 8.4 Gestire un lead

Click sulla card del lead → apre il dettaglio:

- **Attività**: registro delle interazioni (chiamate, email, appuntamenti) con data e note
- **Follow-up programmato**: imposta una data in cui il sistema ti ricorderà di ricontattare
- **Stato**: aggiorna manualmente o trascina nel kanban
- **Converti in studente**: quando il lead acquista, clicca "Converti" → il sistema crea automaticamente lo Student collegato e archivia il lead come "Vinto"

### 8.5 Statistiche CRM

Menu **CRM → Statistiche**.

Dashboard con:
- Numero lead per stato (torta)
- Tasso di conversione nel periodo selezionato
- Lead per fonte
- Lead per lingua/corso di interesse
- Tempo medio di conversione

Utile per valutare l'efficacia delle campagne di acquisizione e il carico di lavoro della segreteria.

---

## 9. Comunicazioni e email

### 9.1 Editor email template

Menu **Email Templates** → ogni template ha:
- Nome interno (slug es. `student.created`)
- Subject
- Corpo HTML editabile (con variabili tipo `{{nome}}`, `{{password}}`)
- Variabili disponibili (mostrate in alto)

Pulsanti:
- **Anteprima** — modal con la mail renderizzata sui dati di esempio
- **Test** — invia a te stesso una mail di prova

### 9.2 Invio comunicazioni di massa

Menu **Comunicazioni → Invio comunicazioni**.

Step:
1. Seleziona i destinatari (filtro per contratto, corso, scuola, ruolo)
2. Componi il messaggio (oggetto + corpo)
3. Anteprima
4. Invia

Il sistema **filtra automaticamente** gli studenti disiscritti (GDPR). L'invio è asincrono in coda.

### 9.3 Notifiche programmate

Per inviare una comunicazione in un momento futuro:

Menu **Notifiche programmate → Crea**.
- Destinatari (singolo studente o filtro)
- Oggetto + corpo
- Data/ora di invio
- Salva

Il sistema processa le notifiche schedulate ogni 5 minuti.

Use case: "voglio mandare un promemoria a tutti gli studenti del corso B1 il 15 settembre alle 09:00" → crei una notifica programmata.

### 9.4 Gestione disiscritti

Menu **Studenti disiscritti** (read-only).

Chi vuole disiscriversi lo fa cliccando il link in un'email. Per "riabilitare" qualcuno: seleziona riga → **Riabilita**.

---

## 10. Reports e statistiche

### 10.1 Report pagamenti

Menu **Reports → Pagamenti**.

Filtri: periodo, stato (paid/pending/cancelled), metodo (bonifico/stripe/paypal), studente, corso.

Output: tabella con totali in fondo, esportazione CSV.

### 10.2 Report ore studenti

Menu **Reports → Ore studenti**.

Mostra per ogni studente: ore acquistate, ore consumate, ore residue, stato contratto.

Use case: "quali studenti hanno meno di 5 ore residue?" → filtri, poi invii email di proposta rinnovo.

### 10.3 Report ore docenti

Menu **Reports → Ore docenti**.

Mostra per ogni docente, in un periodo dato: N° lezioni svolte, ore totali insegnate, importo lordo (ore × tariffa oraria).

Esportazione PDF e CSV.

### 10.4 Controllo anomalie

Menu **Reports → Controllo anomalie**.

Questo report scansiona automaticamente il database alla ricerca di situazioni incongruenti che potrebbero indicare errori di inserimento dati o situazioni da verificare. Esempi tipici:

- Contratti attivi senza lezioni generate
- Studenti con ore consumate superiori alle ore acquistate
- Rate senza contratto collegato
- Lezioni senza docente assegnato
- Contratti in stato `pending` da più di 30 giorni

**Come usarlo:**
1. Menu **Reports → Controllo anomalie**
2. Il sistema esegue i controlli automaticamente all'apertura
3. Ogni anomalia è descritta con il record coinvolto e un link diretto alla correzione
4. Risolvi le situazioni segnalate e ricarica per verificare

> Consigliato: controllare questo report almeno una volta a settimana per mantenere i dati puliti.

---

## 11. Area studente

### 11.1 Accesso

Lo studente accede a `/studente` con le credenziali ricevute via email al momento della creazione dell'account. Al primo accesso è **obbligatorio cambiare la password**.

Se lo studente non ricorda la password: clicca "Password dimenticata?" nella pagina di login → riceve email di reset.

### 11.2 Dashboard

La dashboard di benvenuto mostra:
- Prossima lezione in programma (con countdown)
- Ore residue del corso
- Eventuali compiti in scadenza
- Avvisi da parte della segreteria

### 11.3 Contratto

Menu **Il mio contratto** → lo studente vede i dettagli del contratto: corso, ore totali, date, docente assegnato.

Se la firma digitale è abilitata, può firmare il contratto direttamente da questa pagina (vedi sez. 5.5).

### 11.4 Calendario lezioni

Menu **Calendario** → visualizza le proprie lezioni in formato mensile/settimanale. Click su una lezione → dettagli (orario, docente, link Google Meet per le lezioni online).

### 11.5 Materiali

Menu **Materiali** → file caricati dalla segreteria/docente che sono stati assegnati al suo contratto (PDF, audio, link video).

### 11.6 Compiti

Menu **Compiti** → lista degli homework assegnati con scadenza e stato (da consegnare, consegnato, valutato).

Per consegnare: clicca sul compito → carica file o inserisci testo → Invia. Riceverà email di notifica quando il docente valuta.

### 11.7 Scadenze e pagamenti

Menu **Scadenze e pagamenti** → lo studente vede il piano rateale del proprio contratto con le date di scadenza e lo stato di ogni rata (pagata / da pagare / scaduta).

Utile per ricordare autonomamente le prossime scadenze senza dover contattare la segreteria.

### 11.8 Test di livello

Menu **Test di livello** → quiz interattivo per valutare il livello linguistico.

Lo studente risponde alle domande a scelta multipla → al termine riceve il livello consigliato (es. "B1 — Intermedio"). Il risultato viene salvato nel suo profilo ed è visibile alla segreteria per guidare la scelta del corso più adatto.

---

## 12. Operazioni di sistema

### 12.1 Backup — verifica

Menu **Backup** (sezione Spatie Laravel Backup).

Lista dei .zip presenti, dimensione, data. Il sistema fa:
- Backup giornaliero del DB ore 02:00
- Backup full settimanale (DB + file) la domenica ore 03:00
- Pulizia backup vecchi ore 01:30

Per scaricare un backup → click sulla riga → Download.

> Conserva sempre una copia recente **offline** (es. su Google Drive personale). Il provider può sempre avere problemi.

### 12.2 Logs di sistema

**Logs Laravel:** cPanel → File Manager → `/home/aeacenter/scuole_app/storage/logs/laravel-AAAA-MM-GG.log`. Cerca `ERROR` o `WARNING`.

Se hai configurato Sentry: tutti gli errori non gestiti vanno automaticamente al progetto scuoleLive su `sentry.io` e riceverai email per ogni nuovo errore unico.

### 12.3 Deploy di nuove versioni

Workflow:
1. Lo sviluppatore commit + push su GitHub `main`
2. cPanel → Git Version Control → `scuole-live` → Manage → Pull or Deploy → **Update from Remote** → ricarica → **Deploy HEAD Commit**
3. Aspetta 1-2 minuti
4. File Manager → `deploy.log` → cerca `===== DEPLOY OK ...` alla fine

---

## 13. FAQ e troubleshooting

### Q1. Il sito è giù — Error 500

**Cause più probabili:** cache stale dopo modifica `.env` o aggiornamento codice.

**Rescue path (in ordine, fermati al primo che funziona):**

1. **File Manager → `bootstrap/cache/`** → cancella `config.php`, `services.php`, `packages.php`, `routes-v7.php`, `events.php` (lascia `.gitignore`).
2. Apri il sito in finestra incognito.
3. Se non funziona: cPanel → **LiteSpeed Cache Manager** → "Flush All".
4. Se ancora non va: File Manager → `storage/logs/laravel-AAAA-MM-GG.log` → leggi le ultime righe → segnala allo sviluppatore con tutto il messaggio.

### Q2. L'email bonifico non arriva

**Cause possibili:**
- Cron `queue:work` non attivo → controlla cPanel → Cron Jobs.
- SMTP non configurato → Impostazioni email nel `.env` (`MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`).
- Mail finita in spam → controlla cartella Spam, attiva SPF/DKIM/DMARC (sez. 2.5).

**Verifica veloce:**
- phpMyAdmin → tabella `jobs`: ci sono righe in attesa? Il queue worker non gira.
- phpMyAdmin → tabella `failed_jobs`: ci sono fallimenti? Leggi il campo `exception` per la causa.

### Q3. Lo studente non riesce a loggarsi

**Cause:**
- Password di primo accesso mai cambiata o smarrita.
- Email di benvenuto finita in spam.

**Soluzioni:**
1. Menu Studenti → trova lo studente → **Reset password** → riceve email di reset.
2. In alternativa: lo studente clicca "Password dimenticata?" nel login.

### Q4. Il sistema forza il cambio password al primo login

È **previsto** per tutti gli studenti nuovi: al primo accesso devono impostare una password personale. È un requisito di sicurezza (GDPR). Dopo il cambio, l'accesso è normale.

### Q5. Sul checkout pubblico non vedo i metodi di pagamento

**Cause:**
- Tutti i toggle in Impostazioni → Metodi di pagamento sono OFF.
- Cache config stale → File Manager → cancella `bootstrap/cache/config.php`.
- Toggle attivo ma chiavi mancanti (Stripe/PayPal): l'opzione appare ma il pagamento dà errore.

### Q6. Il PDF del contratto è vuoto o mostra campi mancanti

- Verifica che tutti i campi in Impostazioni scuola siano compilati.
- Se il problema persiste, segnala allo sviluppatore con screenshot e numero contratto.

### Q7. La lezione Google Meet non si crea

- Account Google non collegato o token scaduto → contatta il referente tecnico.
- Il sistema tenta di rinnovare automaticamente il token. Se non funziona da più di 30 minuti, segnala con l'orario in cui hai notato il problema.

### Q8. Le rate scadute non mandano promemoria email

- Cron `schedule:run` non attivo: cPanel → Cron Jobs → verifica.
- Toggle "Promemoria rate scadute" disattivo in Impostazioni scuola.

### Q9. Ho cambiato l'IBAN ma le email vecchie hanno il vecchio IBAN

Le email già inviate sono storiche, non si aggiornano. Le email **future** useranno il nuovo IBAN. Se devi correggere un bonifico già inviato con IBAN sbagliato: scrivi una mail manuale allo studente con i dati corretti.

### Q10. Voglio rimborsare uno studente

scuoleLive non gestisce rimborsi automatici. Devi:
1. Marcare il `CoursePurchase` come `cancelled` manualmente
2. Marcare il `Contract` come `cancelled`
3. Eseguire il rimborso a mano dal dashboard Stripe/PayPal o tramite bonifico
4. Documentare nelle note del contratto

### Q11. Come cancello uno studente per GDPR (diritto all'oblio)

1. Menu Studenti → modifica lo studente → tab "Anonimizza"
2. Sostituisci nome/cognome/email/telefono con valori fittizi (es. `STUDENTE-CANCELLATO-N`)
3. Cancella i dati ridondanti
4. Marca come `disabled`

> Non cancellare mai il record fisicamente: i contratti, le rate e le ricevute (se emesse) richiedono lo storico per legge italiana (10 anni).

### Q12. Il lead non si converte in studente

Dalla scheda lead → pulsante **"Converti in studente"**. Se il pulsante non appare, controlla che il lead abbia almeno email + nome. Dopo la conversione, il lead viene marcato come "Vinto" e lo trovi anche in Menu **Studenti**.

### Q13. Il quiz di livello non appare allo studente

- Verifica che siano state create almeno alcune domande in Menu **Quiz di livello**.
- Controlla che le domande abbiano la lingua corretta impostata (deve corrispondere al corso dello studente).

---

## Appendici

### A. Tabelle DB principali

| Tabella | Cosa contiene |
|---|---|
| `users` | Tutti gli utenti (studenti, docenti, admin) con auth |
| `students` | Profilo studente |
| `courses` | Catalogo corsi |
| `course_purchases` | Acquisti pubblici (anche bonifici pending) |
| `contracts` | Contratti studenti↔scuola |
| `lessons` | Lezioni effettive |
| `installments` | Rate dei contratti |
| `homework`, `homework_submissions` | Compiti e consegne |
| `school_settings` | Configurazione scuola (key/value) |
| `email_templates` | Template email modificabili |
| `leads` | Lead CRM |
| `closure_days` | Giorni di chiusura scuola |
| `quiz_questions` | Domande quiz di livello |
| `student_unsubscribes` | Disiscritti GDPR |
| `jobs`, `failed_jobs` | Coda email/job |

### B. Glossario

- **CoursePurchase** = acquisto di un corso da parte di uno studente (può essere pending o paid).
- **Contract** = contratto effettivo studente-scuola (creato dopo il pagamento).
- **Installment** = rata di un contratto.
- **Slot** = orario settimanale ricorrente di un contratto (es. "Lunedì 18:00").
- **Lesson** = singola occorrenza di lezione (es. "Lunedì 6 maggio 18:00").
- **Beneficiary** = studente che fruisce delle lezioni di un contratto (può differire dal pagatore).
- **Lead** = potenziale studente che ha espresso interesse, non ancora cliente.
- **Closure Day** = giorno/periodo di chiusura scuola che esclude la generazione di lezioni.

---

*Manuale generato il 2026-05-07. Per aggiornamenti, modifiche o chiarimenti, contatta il referente tecnico del progetto.*
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          