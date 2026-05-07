# Manuale operativo scuoleLive

**Versione:** 2026-05-07 (aggiornata dopo audit + fix bloccanti)
**Destinatari:** segreteria, amministrazione, gestori della scuola
**URL produzione:** `https://scuolelive.aealingue.it`
**URL pannello admin:** `https://scuolelive.aealingue.it/admin`

---

## Indice

1. Introduzione e architettura
2. Primo accesso e setup iniziale
3. Gestione studenti
4. Gestione corsi
5. Gestione contratti
6. Gestione lezioni
7. Gestione pagamenti
8. Comunicazioni e email
9. Reports e statistiche
10. Operazioni di sistema
11. FAQ e troubleshooting
12. Roadmap migliorie consigliate

---

## 1. Introduzione e architettura

### 1.1 Cosa fa scuoleLive

scuoleLive è il software gestionale della scuola di lingue. Copre l'intero ciclo:

- **Pubblico**: pagine vetrina (`/`, `/iscriviti`, `/corsi`) per acquisire nuovi studenti via form di interesse o checkout diretto.
- **Segreteria/Amministrazione**: gestione studenti, contratti, lezioni, pagamenti, comunicazioni, report.
- **Docenti**: calendario lezioni, valutazione compiti, registro presenze.
- **Studenti**: area riservata con calendario, contratto firmabile, materiali, compiti, quiz.

### 1.2 I quattro pannelli (panel)

Ogni utente accede a un pannello in base al ruolo:

| URL | Pannello | Chi accede |
|---|---|---|
| `/admin` | Amministrazione | Segreteria, Amministrazione, Superadmin |
| `/superadmin` | Configurazione avanzata | Superadmin (te) |
| `/docente` | Area docenti | Docente |
| `/studente` | Area studenti | Studente |

Un superadmin può accedere a tutti.

### 1.3 Ruoli (sintesi rapida)

- **Superadmin**: accesso totale, vede e modifica tutto.
- **Amministrazione**: gestisce contratti, pagamenti, configurazione corsi.
- **Segreteria**: gestisce studenti, lead, comunicazioni, quasi tutto tranne configurazione tecnica.
- **Docente**: vede solo i propri studenti, le proprie lezioni, valuta i propri compiti.
- **Studente**: vede solo il proprio contratto, lezioni, materiali.

I ruoli si assegnano dal pannello superadmin → Roles oppure (per i docenti) automaticamente quando si crea un Docente da `/admin/teachers`.

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

Se vuoi che le lezioni online creino automaticamente un Google Meet:

1. `/superadmin/google-settings` → clicca **Collega account Google**
2. Si apre la finestra OAuth Google → scegli l'account scuola → consenti
3. Tornerai sulla pagina con scritto "Account Google collegato correttamente"
4. Imposta `GOOGLE_CALENDAR_ID` nel `.env.production` (oppure in `.env` se sei in locale) — tipicamente `primary`

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

> ⚠️ Usa SEMPRE `/usr/local/bin/php` (CLI). NON `/usr/bin/php` (CGI: ignora gli argomenti, è la causa #2 dell'incident del 2026-05-06).

### 2.5 SPF / DKIM / DMARC

Per evitare che le email finiscano in spam, configura sul DNS del dominio mittente (`aealingue.it`):

- **SPF**: `v=spf1 include:_spf.aruba.it ~all` (o l'include giusto del provider mail)
- **DKIM**: chiedi al provider mail i record da inserire (Aruba lo genera dal pannello mail)
- **DMARC**: `v=DMARC1; p=quarantine; rua=mailto:postmaster@aealingue.it`

Verifica con strumenti tipo `mail-tester.com` (mandi una mail al test e ti dà il punteggio).

### 2.6 Verificare il backup

Menu **Backup** → controlla che ci sia almeno un .zip recente. Se la lista è vuota:

1. Aspetta la notte successiva (backup automatico h. 02:00)
2. Oppure forza ora: cPanel → Cron Jobs → "Run command now" sulla riga di backup

Imposta anche `BACKUP_NOTIFICATION_EMAIL` se non l'hai (`Impostazioni → Backup`) — riceverai un alert se un backup fallisce.

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

> Quando salvi, se non esiste già un User con quella email, il sistema crea **automaticamente** uno User con il ruolo `Studente` e una password random sicura (16 caratteri). La password viene inviata via email al template `student.created`. **Lo studente è obbligato a cambiarla al primo login** (vedi sez. 11 FAQ).

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
- Marcare come disiscritto manualmente (raro — di solito si auto-iscrivono dal link unsubscribe in email)
- Cambiare email → si propaga al User collegato

### 3.5 Disiscrizione GDPR

Ogni email automatica ha un link "Disiscriviti" nel footer (ex art. 13 GDPR).

Cliccando il link lo studente arriva su `/unsubscribe/[token]`, conferma, e da quel momento NON riceve più email automatiche né bulk dalla scuola.

Lato admin: menu **Studenti disiscritti** (read-only). Per "riabilitare" uno studente (es. errore o cambio idea), seleziona la riga → **Riabilita** (cancella la riga).

> Il link di disiscrizione è valido a tempo indeterminato (token HMAC autocontenuto, non scade — finché la `APP_KEY` non cambia).

---

## 4. Gestione corsi

### 4.1 Cosa è un "corso"

Un corso è il modello/template che lo studente acquista. Definisce:
- Nome (es. "Inglese B1 — Pacchetto 30 ore")
- Lingua, livello, tipologia (lezione_type: individuale, di gruppo, online)
- Ore totali (`hours_purchased`)
- Quota iscrizione (`enrollment_fee`)
- Quota corso (`course_price`)
- Visibilità pubblica e attivazione

Il prezzo totale (visibile al pubblico) = `enrollment_fee` + `course_price`.

### 4.2 Creare un corso

Menu **Corsi → Crea**.

Campi:
- **Nome** — quello che vede il pubblico
- **Lingua** — collegata al model `Language`
- **Lezione type** — "individuale", "di gruppo", "online", "intensivo" (libero)
- **Ore acquistate** — totale ore previste
- **Ore "full"** — ore di lezione di gruppo intero, se applicabile (le rimanenti sono "personali")
- **Quota iscrizione** — fee una tantum
- **Prezzo corso** — costo del pacchetto
- **Pubblico** (`is_public`) — se mostrato sul checkout `/corsi`
- **Attivo** (`is_active`) — se può essere acquistato/usato in nuovi contratti
- **Descrizione corta** — tagline (max 1 riga)
- **Descrizione completa** — testo lungo, supporta markdown

### 4.3 Pubblicare o nascondere un corso

Toggle `is_public` → quando `false`, il corso non appare in `/corsi` ma rimane utilizzabile lato admin (per contratti gestiti manualmente).

Toggle `is_active` → quando `false`, il corso è "archiviato": non si possono creare nuovi contratti basati su di esso, ma quelli esistenti continuano a esistere.

Use case: "voglio togliere temporaneamente Inglese B2 dal sito ma non eliminarlo" → metti `is_public=false`.

### 4.4 Filtri sul catalogo pubblico

Il catalogo `/corsi` ha 2 filtri visibili al pubblico:
- **Ore** (15h, 30h, ecc.)
- **Tipologia** (individuale, di gruppo, online)

I valori dei filtri sono auto-popolati dai corsi pubblici esistenti. Per offrire una nuova tipologia basta crearne uno con quel valore.

---

## 5. Gestione contratti

### 5.1 Cosa è un contratto

Il contratto è il "patto" tra scuola e studente: contiene il corso scelto, il prezzo concordato, i dati billing, gli orari, le rate.

Stati possibili:
- `pending` — appena creato (es. da pagamento online), in attesa di completamento dati
- `active` — completato e operativo
- `completed` — corso finito
- `cancelled` — annullato

### 5.2 Creare un contratto manualmente

Menu **Contratti → Crea**.

Step 1 — **Beneficiari**: aggiungi uno o più studenti. Se è un corso "azienda paga, dipendente fruisce", il pagatore è il `billing_*` del contratto e il fruitore è uno studente nei "Beneficiari".

Step 2 — **Corso e prezzi**: scegli il corso → i prezzi vengono auto-compilati ma puoi modificarli (es. sconto per fedeltà).

Step 3 — **Dati billing**: chi paga (privato o azienda). Validazione automatica:
- Codice fiscale italiano (16 char + checksum)
- P.IVA italiana (11 cifre + checksum mod-10)

Step 4 — **Slot orari** (Relation Manager "Lessons Slots"): definisci giorno settimanale + ora + docente per ogni slot. Es. "Lunedì 18:00-19:30, Prof.ssa Bianchi". Più studenti possono condividere lo stesso slot.

Step 5 — **Rate (installments)**: configura come dividere il pagamento (es. 3 rate da X€ con scadenza mensile). Le rate sono indipendenti dal pagamento online: se uno studente paga online tutto in un colpo, marchi le rate come pagate manualmente.

### 5.3 Generare le lezioni dagli slot

Una volta definiti gli slot e la data inizio:

Pulsante **"Genera lezioni"** (in alto sul contratto) → crea automaticamente N occorrenze settimanali fino al raggiungimento delle ore acquistate, saltando giorni di chiusura scuola.

Se vuoi rigenerare (es. cambiata la data inizio): elimina prima le lezioni future esistenti, poi rigenera.

### 5.4 Stampare il contratto in PDF

Tasto **Stampa** sul contratto:
- **Stampa (HTML)** — apre la versione web pronta da stampare via browser
- **PDF inline** — apre il PDF nel tab del browser
- **Download PDF** — scarica il file `Contratto_NN.pdf`

Il PDF legge tutti i dati da Impostazioni scuola (logo, indirizzo, IBAN, ragione sociale): se trovi qualcosa di errato, modifica le Impostazioni e ristampa.

### 5.5 Firma digitale OTP

Solo se hai abilitato in Impostazioni → "Firma digitale".

Lo studente entra in `/studente/contratto/N` → vede il pulsante "Firma il contratto" → riceve un OTP via email valido 15 minuti → inserisce OTP → contratto firmato (visibile in admin con icona ✅).

---

## 6. Gestione lezioni

### 6.1 Calendario

Menu **Calendario lezioni** mostra il fullcalendar mensile/settimanale con tutte le lezioni di tutti i contratti attivi.

Colori: ogni docente ha un colore. Click su una lezione → modal con dettagli (studente, contratto, slot, link Meet).

### 6.2 Spostare o annullare una lezione

Click sulla lezione → modal:
- **Sposta** — cambia data/ora. Se cambia il docente, il sistema chiede conferma.
- **Annulla** — segna come `cancelled`, libera l'ora dello slot, opzionalmente invia email di scuse via template `lesson.cancelled`.

### 6.3 Recupero lezione

Le lezioni annullate possono essere recuperate. Menu **Recuperi** → seleziona lezione annullata → "Pianifica recupero" → scegli data/ora futura → genera la lezione di recupero (collegata a quella originale).

### 6.4 Materiali e visibilità per contratto

Menu **Materiali corso** → carica file (PDF, audio, video link YouTube).

Per ogni materiale puoi decidere a quali contratti è visibile dal pannello studente (toggle on/off). Use case: hai un PDF "verbi irregolari B1" — vuoi mostrarlo solo agli studenti che hanno acquistato il corso B1.

### 6.5 Compiti (homework)

Menu **Compiti** → crea homework con:
- Titolo, descrizione
- Studente o studenti destinatari (filtro per contratto/corso)
- Scadenza

Lo studente vede il compito in `/studente/compiti`, carica la propria submission (file o testo).

Tu (o il docente) valuta dalla colonna "Submissions" → grade + feedback. Lo studente riceve email con il voto.

> **Sicurezza**: solo il docente del contratto può valutare i compiti dei suoi studenti. Staff (Amministrazione/Segreteria) può valutare qualsiasi compito.

---

## 7. Gestione pagamenti

### 7.1 Flusso checkout pubblico

Lo studente:
1. Apre `/corsi` → sceglie un corso → clicca "Iscriviti"
2. Compila form (privato o azienda, dati billing, accetta privacy)
3. Sceglie metodo di pagamento (vedi 7.2)
4. Stripe / PayPal → reindirizzamento al gateway → torna sul sito
5. Bonifico → atterra sulla pagina istruzioni e riceve email con IBAN + causale

### 7.2 Toggle metodi di pagamento

Menu **Configurazione → Impostazioni scuola → Metodi di pagamento**.

Tre toggle:
- **Bonifico bancario** (default ON)
- **Carta di credito (Stripe)** (default OFF)
- **PayPal** (default OFF)

Se disabilitati, il radio relativo non appare sul checkout. Se tutti e 3 sono OFF, il checkout mostra "Al momento non è possibile completare il pagamento online — contatta la segreteria".

> Per attivare Stripe/PayPal devi prima configurare le chiavi API e i webhook (vedi sez. 7.5). Senza chiavi i toggle "funzionano" ma il pagamento andrà in errore.

### 7.3 Conferma manuale di un bonifico

Quando arriva il bonifico in conto:

1. Menu **Pagamenti corso (CoursePurchases)** → trova quello con il `bank_transfer_ref` corrispondente alla causale
2. Click sulla riga → cambia `payment_status` da `pending` a `paid`, imposta `paid_at` = oggi
3. Salva → il sistema crea automaticamente il contratto e invia email di conferma allo studente

In alternativa, dal contratto stesso: tab "Rate" → marca la prima rata come `paid`.

### 7.4 Rate (installments)

Per contratti rateizzati:

- Menu **Rate** → vedi tutte le rate di tutti i contratti attivi
- Filtri: pagate, in scadenza, scadute
- Click su una rata → marca come pagata, scarica ricevuta PDF (con disclaimer "non ha valore fiscale")

Promemoria automatico via email il giorno della scadenza (se attivato in Impostazioni → Promemoria rate).

### 7.5 Configurare Stripe (per attivarlo)

Sul dashboard Stripe (modalità Live):
1. Crea API keys: copia `Publishable key` (pk_live_...) e `Secret key` (sk_live_...)
2. Crea Webhook endpoint:
   - URL: `https://scuolelive.aealingue.it/webhook/stripe`
   - Eventi da inviare: `checkout.session.completed`, `checkout.session.expired`
   - Copia il Signing Secret (whsec_...)

Sul server, modifica `.env.production`:
```
STRIPE_KEY=pk_live_xxxxxxxxxxxx
STRIPE_SECRET=sk_live_xxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxx
```

Riesegui deploy o fai `optimize:clear` da cron one-shot, poi attiva il toggle Stripe in Impostazioni scuola.

### 7.6 Configurare PayPal (per attivarlo)

Sul dashboard PayPal Developer (modalità Live):
1. Crea App → copia `Client ID` e `Secret`
2. Crea Webhook → URL `https://scuolelive.aealingue.it/webhook/paypal` → eventi `PAYMENT.CAPTURE.COMPLETED` → copia Webhook ID

Sul server `.env.production`:
```
PAYPAL_CLIENT_ID=AbCdEf...
PAYPAL_SECRET=EaBcDe...
PAYPAL_BASE_URL=https://api-m.paypal.com
PAYPAL_WEBHOOK_ID=WH-12AB34CD...
```

Senza `PAYPAL_WEBHOOK_ID` configurato in produzione, il sistema BLOCCA tutti i webhook PayPal (anti-fraud). È intenzionale.

---

## 8. Comunicazioni e email

### 8.1 Editor email template

Menu **Email Templates** → ogni template ha:
- Nome interno (slug es. `student.created`)
- Subject
- Corpo HTML editabile (con variabili tipo `{{nome}}`, `{{password}}`)
- Variabili disponibili (mostrate in alto)

Pulsanti:
- **Anteprima** — modal con la mail renderizzata sui dati di esempio
- **Test** — invia a te stesso una mail di prova

### 8.2 Invio comunicazioni di massa

Menu **Comunicazioni → Invio comunicazioni** (o pagina dedicata).

Step:
1. Selezioni i destinatari (filtro per contratto, corso, scuola, ruolo)
2. Componi il messaggio (oggetto + corpo)
3. Anteprima
4. Invia

Il sistema **filtra automaticamente** gli studenti disiscritti (GDPR) — non li include nemmeno se sono nel filtro.

L'invio è asincrono in coda: vedi lo stato in **Notification Email Logs**.

### 8.3 Notifiche programmate

Per inviare una comunicazione in un momento futuro:

Menu **Notifiche programmate → Crea**.
- Destinatari (singolo studente o filtro)
- Oggetto + corpo
- Data/ora di invio
- Salva

Il cron `school:send-student-notifications` (ogni 5 minuti) processa le notifiche schedulate quando arriva l'orario.

Use case: "voglio mandare un promemoria a tutti gli studenti del corso B1 il 15 settembre alle 09:00 con info sull'inizio anno didattico" → crei una notifica programmata.

### 8.4 Gestione disiscritti

Menu **Studenti disiscritti**.

Read-only per design — chi vuole disiscriversi lo fa cliccando il link in un'email.

Per "riabilitare" qualcuno (es. errore, cambio idea): seleziona riga → **Elimina/Riabilita** (rimuove la riga dalla tabella). Da quel momento riceve di nuovo le email.

---

## 9. Reports e statistiche

### 9.1 Report pagamenti

Menu **Reports → Pagamenti**.

Filtri:
- Periodo (date range)
- Stato (paid/pending/cancelled)
- Metodo (bonifico/stripe/paypal)
- Studente
- Corso

Output: tabella con totali in fondo, esportazione CSV.

### 9.2 Report ore studenti

Menu **Reports → Ore studenti**.

Mostra per ogni studente:
- Ore acquistate
- Ore consumate (lezioni svolte)
- Ore residue
- Stato contratto

Use case: "quali studenti hanno meno di 5 ore residue?" → filtri per "residue < 5", invii email di proposta rinnovo.

### 9.3 Report ore docenti (per pagamento docenti)

Menu **Reports → Ore docenti**.

Mostra per ogni docente, in un periodo dato:
- N° lezioni svolte
- Ore totali insegnate
- Importo lordo (ore × tariffa oraria)

Esportazione PDF (per buste paga / ricevute) e CSV.

---

## 10. Operazioni di sistema

### 10.1 Health-check

Comando `php artisan school:health-check` (o lanciato dal cron `.cpanel.yml` ad ogni deploy).

Verifica:
- APP_KEY, APP_DEBUG, timezone
- Connessione DB
- Tabelle critiche presenti
- Storage scrivibile
- Mail configurata
- Stripe / PayPal / Google chiavi
- Backup disco e ultima copia
- Schedule popolato

Output: ✅ OK / ⚠️ WARN / ❌ FAIL.

In produzione l'output è scritto nel `deploy.log` ad ogni deploy. Per vederlo: cPanel → File Manager → `/home/aeacenter/scuole_app/storage/logs/deploy.log`.

### 10.2 Backup verificare

Menu **Backup** (sezione Spatie Laravel Backup).

Lista dei .zip presenti, dimensione, data. Il sistema fa:
- Backup giornaliero del DB ore 02:00
- Backup full settimanale (DB + file) la domenica ore 03:00
- Pulizia backup vecchi (retention) ore 01:30

Per scaricare un backup → click sulla riga → Download.

> Conserva una copia recente OFFLINE (es. su Google Drive personale o NAS). Il provider può sempre avere problemi.

### 10.3 Logs e Sentry

**Logs Laravel:** `/home/aeacenter/scuole_app/storage/logs/laravel-AAAA-MM-GG.log` via File Manager. Cerca `ERROR` o `WARNING`.

**Sentry:** se configurato (DSN nel `.env.production`), tutti gli errori non gestiti (eccezioni) vanno automaticamente a `sentry.io` → progetto scuoleLive. Riceverai email per ogni nuovo errore unico.

### 10.4 Deploy di nuove versioni

Workflow:
1. Sviluppatore commit + push su GitHub `main`
2. cPanel → Git Version Control → `scuole-live` → Manage → Pull or Deploy → **Update from Remote** → ricarica → **Deploy HEAD Commit**
3. Aspetta 1-2 minuti
4. File Manager → `deploy.log` → cerca `===== DEPLOY OK ...` alla fine

Il `.cpanel.yml` esegue automaticamente in sequenza:
- Pull codice
- `composer install --no-dev`
- `php artisan migrate --force`
- `php artisan optimize:clear`
- `php artisan optimize`
- `php artisan filament:optimize`
- `php artisan queue:restart`
- `php artisan school:health-check`

---

## 11. FAQ e troubleshooting

### Q1. Il sito è giù — Error 500

**Causa più probabile:** cache stale dopo modifica `.env` o aggiornamento codice.

**Rescue path (in ordine, ti fermi al primo che funziona):**

1. **File Manager → `bootstrap/cache/`** → cancella `config.php`, `services.php`, `packages.php`, `routes-v7.php`, `events.php` (lascia `.gitignore`).
2. Apri il sito in finestra incognito → spesso basta questo.
3. Se non funziona: cPanel → **LiteSpeed Cache Manager** → "Flush All".
4. Se ancora non va: cPanel → File Manager → `storage/logs/laravel-AAAA-MM-GG.log` → leggi le ultime righe → cerca "Class ... not found" o "SQLSTATE" → segnala allo sviluppatore con tutto il messaggio.

### Q2. L'email bonifico non arriva

**Cause possibili:**
- Cron `queue:work` non attivo → controlla cPanel → Cron Jobs.
- SMTP non configurato → menu Impostazioni email o `.env.production` (`MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`).
- Mail finita in spam → controlla cartella Spam, attiva SPF/DKIM/DMARC (sez. 2.5).
- Errore SMTP → File Manager → `storage/logs/laravel-*.log` → cerca "BonificoInstructionsMail" o "queue".

**Verifica veloce:**
- phpMyAdmin → tabella `jobs`: ci sono righe in attesa? Allora il queue worker non gira.
- phpMyAdmin → tabella `failed_jobs`: ci sono fallimenti? Leggi il campo `exception` per la causa.

### Q3. Lo studente non riesce a loggarsi (password non corretta)

**Cause:**
- Lo studente sta usando la password di prima della modifica forzata.
- L'email di benvenuto è finita in spam → password mai vista.
- L'utente non esiste (Student creato senza email valida).

**Soluzioni:**
1. Menu Studenti → trova lo studente → tab "User collegato" → click **Reset password** → Lui riceve email di reset password.
2. In alternativa: lui clicca "Password dimenticata?" sulla pagina login.
3. Se proprio nulla: chiedi conferma email, poi cambia tu la password manualmente (Admin → Users → modifica).

### Q4. Il sistema mi forza a cambiare password al login

È **previsto**: per gli studenti auto-creati il sistema usa una password random sicura, e al primo accesso devi cambiarla. È un requisito di sicurezza (GDPR).

Cambiala con una tua scelta → da quel momento entri normalmente.

### Q5. Sul checkout pubblico non vedo i metodi di pagamento

**Cause:**
- Tutti i toggle in Impostazioni scuola → Metodi di pagamento sono OFF → vedi banner "contatta segreteria".
- Cache config stale → File Manager → cancella `bootstrap/cache/config.php`.
- Toggle attivo ma chiavi mancanti (Stripe/PayPal): l'opzione appare ma il pagamento dà errore al click.

### Q6. Il PDF del contratto è vuoto o corrotto

- Verifica che `storage/app/public` sia scrivibile (chmod 775).
- Se mostra "indirizzo non disponibile" o campi vuoti: vai su Impostazioni scuola e compila.
- Errore "DomPDF": probabilmente font missing → controlla `vendor/barryvdh/laravel-dompdf` presente.

### Q7. La lezione Google Meet non si crea

- Account Google non collegato: `/superadmin/google-settings` → ricollega.
- Token scaduto: il sistema rinnova automaticamente al prossimo uso, ma se non funziona, ricollega l'account.
- Nel laravel.log cerca "Google" → leggi l'errore.

### Q8. Le rate scadute non mandano promemoria

- Cron `schedule:run` non attivo: cPanel → Cron Jobs → verifica.
- Toggle "Promemoria rate scadute" disattivo in Impostazioni scuola.
- Comando fallito: log in `storage/logs/laravel-*.log` → cerca "installments:notify-overdue".

### Q9. Ho cambiato l'IBAN in Impostazioni ma le email vecchie hanno il vecchio IBAN

Le email già inviate sono storiche, non si aggiornano. Le email FUTURE useranno il nuovo IBAN.

Se devi correggere un bonifico già inviato con IBAN sbagliato: scrivi una mail manuale allo studente con i dati corretti.

### Q10. Voglio rimborsare uno studente

scuoleLive **NON** gestisce rimborsi automatici. Devi:
1. Marcare il `CoursePurchase` come `cancelled` (manualmente)
2. Marcare il `Contract` come `cancelled`
3. Eseguire il rimborso a mano dal dashboard del gateway (Stripe / PayPal) o tramite bonifico
4. Documentare nelle note del contratto

### Q11. Come cancello uno studente per GDPR (right to be forgotten)

1. Menu Studenti → modifica lo studente → tab "Anonimizza"
2. Sostituisci nome/cognome/email/telefono con valori fittizi (es. `STUDENTE-CANCELLATO-N`)
3. Cancella i dati ridondanti
4. Marca come `disabled`
5. NON cancellare il record fisicamente: i contratti, le rate e le ricevute fiscali (se ci sono) richiedono lo storico per legge italiana 10 anni.

### Q12. Il dominio mittente delle email mi dà errore

Probabilmente SPF/DKIM mancanti. Vedi sez. 2.5. Test: `mail-tester.com` → invia una mail al loro indirizzo → ti danno il punteggio (sopra 8/10 va bene).

---

## 12. Roadmap migliorie consigliate

Funzionalità che NON sono ancora implementate ma che renderebbero il sistema più professionale e meno rischioso. In ordine di priorità.

### 12.1 Riconciliazione automatica bonifici (alta)

**Cosa fa:** integrazione con API banca (es. PSD2, Banking API di Aruba) → il sistema verifica ogni notte i bonifici in entrata e marca automaticamente come `paid` quelli con causale corrispondente a un `bank_transfer_ref`.

**Beneficio:** elimina il lavoro manuale di "controllo bonifico → marca pagato → invia email conferma". Riduce ritardi nell'attivazione del corso (oggi può passare un giorno tra ricezione bonifico e conferma manuale).

**Costo stimato:** 2-3 giornate di sviluppo + costi banca per accesso API (variabile).

### 12.2 Two-Factor Authentication per admin (alta)

**Cosa fa:** chi accede a `/admin` o `/superadmin` deve inserire, oltre alla password, un codice OTP da Google Authenticator (o SMS).

**Beneficio:** riduce drasticamente il rischio se la password admin viene compromessa (phishing, riuso password, ecc.). Particolarmente importante per il pannello che vede dati sensibili di tutti gli studenti.

**Costo stimato:** 1 giornata. Pacchetto già pronto per Filament: `filament/2fa`.

### 12.3 Audit log per ogni risorsa (media)

**Cosa fa:** registra ogni modifica fatta da ogni utente su ogni record (chi ha cambiato cosa, quando, valore prima/dopo). Già parzialmente presente via `spatie/laravel-activitylog`, ma non esposto in UI.

**Beneficio:** trasparenza interna ("chi ha cancellato la lezione di Lunedì?"), conformità GDPR, debug rapido.

**Costo stimato:** 1 giornata per esporre i log esistenti in una pagina Filament read-only.

### 12.4 Dashboard self-service avanzata per studenti (media)

**Cosa fa:** lo studente vede:
- Saldo ore residue con grafico a torta
- Calendario lezioni con possibilità di richiesta spostamento
- Storico pagamenti con download ricevute
- Materiali didattici filtrabili
- Quiz e progressi

**Beneficio:** riduce richieste alla segreteria del 30-40%, migliora retention.

**Costo stimato:** 3-4 giornate.

### 12.5 App mobile (PWA) (bassa-media)

**Cosa fa:** versione mobile-first del pannello studente, installabile come "app" sul telefono (Progressive Web App, no store).

**Beneficio:** notifiche push (reminder lezione tra 1h), accesso offline ai materiali.

**Costo stimato:** 1 settimana.

### 12.6 Sistema di referral / sconti (bassa)

**Cosa fa:** lo studente ottiene un codice univoco da condividere → chi lo usa ha sconto X% → lo studente ottiene Y€ di credito.

**Beneficio:** marketing organico, retention.

**Costo stimato:** 2-3 giornate.

### 12.7 Sondaggi e NPS (bassa)

**Cosa fa:** invia automaticamente alla fine di un corso un questionario di gradimento (1-10), feedback testuale, suggerimenti per docente.

**Beneficio:** dati per migliorare l'offerta, identificare docenti bisognosi di formazione.

**Costo stimato:** 1-2 giornate.

### 12.8 Integrazione fatturazione elettronica (media-alta)

**Cosa fa:** la ricevuta non è solo un PDF ma una vera fattura elettronica B2B/B2C inviata via SDI tramite servizi come `Aruba Fatturazione` o `Fatture in Cloud`.

**Beneficio:** elimina la fase manuale del commercialista che deve riemettere la fattura.

**Costo stimato:** 3-5 giornate. Costo provider fatturazione (~10€/mese).

### 12.9 Integrazione contabilità (media)

**Cosa fa:** ogni rata pagata produce automaticamente una scrittura contabile esportabile in formato XML/CSV per il commercialista (o sync diretto con Fatture in Cloud, Aruba Fatturazione, TeamSystem ecc.).

**Beneficio:** elimina riconciliazione mensile manuale.

**Costo stimato:** 2-3 giornate.

### 12.10 Blocco accesso studente per morosità (bassa)

**Cosa fa:** se uno studente ha rate scadute oltre N giorni, vede un banner "Regolarizza il pagamento" e perde temporaneamente accesso a materiali/lezioni online.

**Beneficio:** incentivo al pagamento, riduce sofferenze.

**Costo stimato:** 1 giornata.

---

## Appendici

### A. Comandi PHP utili (per chi ha terminale)

```bash
# Rigenera tutto (dopo cambi .env)
php artisan optimize:clear
php artisan optimize

# Health-check
php artisan school:health-check

# Backup manuale
php artisan backup:run --only-db
php artisan backup:run

# Verifica scheduling
php artisan schedule:list

# Pulisci sessioni scadute
php artisan session:gc
```

### B. Tabelle DB principali

| Tabella | Cosa contiene |
|---|---|
| `users` | Tutti gli utenti (studenti, docenti, admin) con auth |
| `students` | Profilo studente (un User può non avere uno Student) |
| `courses` | Catalogo corsi |
| `course_purchases` | Acquisti pubblici (anche bonifici pending) |
| `contracts` | Contratti studenti↔scuola |
| `lessons` | Lezioni effettive |
| `installments` | Rate dei contratti |
| `homework`, `homework_submissions` | Compiti e consegne |
| `school_settings` | Configurazione scuola (key/value) |
| `email_templates` | Template email modificabili |
| `student_unsubscribes` | Disiscritti GDPR |
| `jobs`, `failed_jobs` | Coda email/job |
| `activity_log` | Log delle modifiche (chi ha fatto cosa) |

### C. Glossario

- **CoursePurchase** = acquisto di un corso da parte di uno studente (può essere pending o paid).
- **Contract** = contratto effettivo studente-scuola (creato dopo il pagamento).
- **Installment** = rata di un contratto.
- **Slot** = orario settimanale ricorrente di un contratto (es. "Lunedì 18:00").
- **Lesson** = singola occorrenza di lezione (es. "Lunedì 6 maggio 18:00").
- **Beneficiary** = studente che fruisce delle lezioni di un contratto (può differire dal pagatore).
- **Lead** = potenziale studente che ha compilato il form di interesse, non ancora cliente.

---

*Manuale generato il 2026-05-07. Per aggiornamenti, modifiche o chiarimenti, segnala via Sentry o apri una issue su GitHub.*
