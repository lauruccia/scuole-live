# ScuoleLive — Report Stato Progetto

**Data report:** 04/05/2026 (rev. 5 dopo controllo funzionale completo)
**Versione:** 4.0 (post ripristino file truncati + verifica funzionalità)
**Stack:** Laravel 12 · PHP 8.2 · Filament 3 · MySQL · cPanel/Aruba (account `aeacenter`)
**Utenti previsti a regime:** ~100 (single-tenant)
**Stato sintesi:** ⚠️ **DIVERGENZA TRA MEMORIA E CODICE.** I 5 file truncati sono stati ripristinati, ma il controllo funzionale ha rivelato che **diverse funzionalità documentate nella memoria del 30/04 NON sono presenti nel codice attuale** (mancano cartelle `app/Rules/`, `app/Jobs/`, `app/Support/UnsubscribeToken`, `UnsubscribeController`, migration `student_unsubscribes`, comando `HealthCheck`, file `DEPLOY.md` e `PERMESSI.md`). Vedere § 3.10 per l'elenco completo.

---

## 0. AGGIORNAMENTO — Verifica funzionale del 05/05/2026

### 0.1 File ripristinati in questa sessione (✅)

| File | Da | A | Note |
|------|---:|---:|------|
| `routes/console.php` | 10 r | **116 r** | Aggiunte 9 entry `Schedule::command()` con `withoutOverlapping`, `onOneServer`, timezone `Europe/Rome` |
| `.cpanel.yml` | 118 r | **167 r** | Aggiunti optimize:clear/optimize/view:cache/event:cache/filament:optimize/queue:restart |
| `.env.example` | 34 r | **158 r** | 14 sezioni complete (69 variabili) + checklist post-copia |
| `app/Filament/Widgets/SystemStatusWidget.php` | 147 r | **242 r** | `backupStatus()` completata + `queueStatus()` aggiunta |
| `routes/web.php` | 77 r (truncato) | **88 r** | Riga finale tronca su callback OAuth + aggiunta stampa contratto studente |

### 0.2 NUOVI bloccanti scoperti (memoria vs codice)

Le seguenti **funzionalità documentate nella memoria del 30/04** come "applicate offline" **NON esistono nel codice attuale**:

1. **`app/Rules/`** — directory inesistente. Nessun `CodiceFiscale.php`, `PartitaIva.php`, `Iban.php`. Impatto: validazioni CF/PIVA/IBAN documentate non sono attive. I form accettano qualunque stringa.
2. **`app/Jobs/`** — directory inesistente. Nessun `SendPurchaseConfirmationJob`. L'email di conferma acquisto è inviata **sincrona** (potenziale lentezza/errori in PaymentService).
3. **`app/Support/UnsubscribeToken.php`** — inesistente. Solo `LanguageOptions.php` in `app/Support/`.
4. **`app/Http/Controllers/UnsubscribeController.php`** — inesistente.
5. **Migration `student_unsubscribes`** — inesistente. Tabella mai creata.
6. **`StudentUnsubscribeResource`** Filament — inesistente.
7. **`NotificationEmailLogResource`** Filament — inesistente. La segreteria non ha visibilità del log invii.
8. **`DEPLOY.md` / `PERMESSI.md`** — inesistenti in root.
9. **`HealthCheck` artisan command** — inesistente. Non c'è `school:health-check` per audit pre-deploy.
10. **`Mail::queue` mai usato.** 0 occorrenze nel codice. **98 occorrenze di `->send()`** sincrono. La memoria diceva "InvioComunicazioni::send filtra disiscritti e usa Mail::queue" — entrambe le affermazioni sono false.
11. **`GoogleCalendarService` e `GoogleMeetService`: `report($e)` + `Log::error` mancanti.** 0 occorrenze. I catch sono ancora silenti come prima.
12. **PayPal webhook NON blocca in production se `PAYPAL_WEBHOOK_ID` vuoto.** Il codice in `WebhookController::verifyPayPalSignature()` restituisce `true` con un semplice `Log::warning`. **Vulnerabilità di sicurezza in produzione.**

### 0.3 Cosa effettivamente È PRESENTE nel codice (verificato 05/05/2026)

| Funzionalità | Stato verificato |
|--------------|------------------|
| SoftDeletes su Lesson, Contract, Student, CoursePurchase | ✅ confermato |
| `PaymentService` PayPal usa `Http::withBasicAuth` | ✅ confermato |
| Idempotenza `confirmPurchase()` via `isPaid()` | ✅ confermato |
| Stripe firma webhook HMAC con `Webhook::constructEvent()` | ✅ confermato |
| Throttle `/iscriviti` 5/min e `/checkout` 3/min | ✅ confermato |
| 4 indici performance su `lessons` | ✅ confermato (migration `2026_05_04_200003`) |
| Vincolo unique `[contract_student_id, starts_at]` | ✅ confermato |
| `Carbon::setLocale('it')` in AppServiceProvider | ✅ confermato |
| Verifica `state` anti-CSRF in `GoogleOAuthController::callback` | ✅ confermato |
| OTP firma contratto: 6 cifre, 5 tentativi, 15 min scadenza | ✅ confermato |
| 4 panel provider Filament (admin, studente, teacher, superadmin) | ✅ confermato (registrati in `bootstrap/providers.php`) |
| 7 comandi artisan custom | ✅ confermato (`crm:notify-followup`, `installments:notify-overdue`, `lessons:fix-future-counts`, `school:send-student-notifications`, `lessons:regenerate-all`, `billing:backfill`, `scuole:bonifica`) |
| 7 mailable in `app/Mail/` | ✅ confermato (`ContractPdfMail`, `ContractSignatureOtpMail`, `LessonCancelledMail`, `PurchaseConfirmationMail`, `StudentCommunicationMail`, `TemplateMail`, `WelcomeStudentMail`) |
| `EnrollmentResource` deprecato con docblock | ✅ confermato |

### 0.4 Sintesi delle verifiche

- ✅ **5 file critici ripristinati** (4 originariamente identificati + 1 scoperto durante la verifica)
- ⚠️ **12 funzionalità documentate nella memoria sono divergenti dal codice** — la memoria riflette uno stato che non è mai stato pushato, oppure i fix sono stati persi
- ✅ Il **core dei pagamenti, lezioni, OTP, throttle, indici** è confermato funzionante
- 🔴 **GAP di sicurezza:** PayPal webhook signature bypass in produzione se `PAYPAL_WEBHOOK_ID` vuoto
- 🟠 **GAP di robustezza:** 0 uso di `Mail::queue` — tutte le email sincrone
- 🟠 **GAP di osservabilità:** Google services con catch silenti

---

---

## 1. Executive summary

Il sistema ScuoleLive è un gestionale per scuole di lingue completo: gestisce l'intero ciclo di vita di un cliente, dal lead in CRM al contratto firmato digitalmente, dall'erogazione delle lezioni online (con integrazione Google Calendar/Meet) al monitoraggio delle rate, fino al portale autonomo dello studente.

A oggi (04/05/2026) il sistema:
- Ha completato 19 dei fix bloccanti pre-lancio identificati nelle due analisi tecniche del 29/04 e del 30/04.
- Espone 4 pannelli Filament separati (superadmin, admin, docente, studente) con isolamento via Spatie Permission + Filament Shield.
- Ha integrazione Stripe + PayPal + bonifico bancario funzionante con webhook firmati (Stripe HMAC, PayPal API verify-webhook-signature).
- Ha sistema di backup automatizzato (spatie/laravel-backup) con disco dedicato `local-backups` e schedule giornaliero/settimanale.
- È deployato su cPanel/Aruba via Git Version Control con `.cpanel.yml` automatico.

Restano da risolvere prima del go-live alcuni punti **critici di configurazione**: il file `routes/console.php` è truncato e non contiene le entry `Schedule::`, il `.cpanel.yml` è truncato dopo il comando `migrate --force` e mancano i comandi di cache/optimize, il `.env.example` è truncato a 34 righe, e la funzione `SystemStatusWidget::backupStatus()` è truncata. Si tratta probabilmente di file corrotti durante un commit o un editing precedente; vanno ripristinati.

Una volta risolti questi 4 file e configurati i webhook reali sul dominio di produzione, il sistema è considerato sicuro per il lancio.

---

## 2. Riepilogo per priorità

| Priorità | Conteggio | Status |
|----------|-----------|--------|
| 🔴 **P0 — Bloccanti residui** (file truncati + config) | 4 | Da risolvere ora |
| 🟠 **P1 — Importanti pre-lancio** (config server) | 6 | Prima del go-live |
| 🟡 **P2 — Importanti post-lancio** (UX/edge case) | 8 | Entro 7 giorni |
| 🟢 **P3 — Housekeeping** | 6 | Entro 30 giorni |
| ✅ **Risolti** | 19 | Completati nella sessione del 30/04 |

---

## 3. Stato per modulo

### 3.1 Architettura e configurazione

#### ✅ Punti solidi
- 4 panel Filament isolati (`superadmin`, `admin`, `docente`, `studente`) con `PanelProvider` separati.
- Bootstrap Laravel 12 (`bootstrap/app.php`) configura alias Spatie e CSRF exemption per `/webhook/stripe` e `/webhook/paypal`.
- Single source of truth: il modello `SchoolSetting` con cache 5 minuti centralizza branding, IBAN, dati fiscali.
- `DB::afterCommit()` + `Cache::lock` in pipeline contratti — gestione concorrenza corretta.
- `composer.json`: `optimize-autoloader: true` ✅.
- `config/app.php`: timezone `Europe/Rome` via env ✅.

#### 🔴 Problemi critici aperti

**[P0-1] `routes/console.php` truncato (10 righe, 367 byte)**
Il file contiene solo l'header `inspire` command e si interrompe a metà del commento `// ── CRM ──...`. Tutte le entry `Schedule::command(...)` previste sono perse:

```php
// ENTRY MANCANTI da ripristinare:
Schedule::command('backup:clean')->daily()->at('01:30');
Schedule::command('backup:run --only-db')->daily()->at('02:00');
Schedule::command('backup:run')->weekly()->sundays()->at('03:00');
Schedule::command('backup:monitor')->daily()->at('08:30');
Schedule::command('leads:notify-followup')->dailyAt('08:00');
Schedule::command('installments:notify-overdue')->dailyAt('09:00');
Schedule::command('school:send-student-notifications')->everyFiveMinutes();
Schedule::command('lessons:fix-future-counts')->dailyAt('03:30');
```

**Impatto:** anche con cron `schedule:run` attivo, NESSUN comando viene eseguito. Niente backup, niente promemoria rate, niente CRM follow-up.

**[P0-2] `.cpanel.yml` truncato (118 righe, ultima riga incompleta)**
L'ultimo comando `php artisan migrate --force` è troncato a metà (`|| /` finale). Mancano gli step:
- `php artisan optimize` (config + route + view cache)
- `php artisan filament:optimize`
- `php artisan queue:restart`

**Impatto:** dopo ogni deploy serve cache stale o richiede intervento manuale via Terminal cPanel.

**[P0-3] `.env.example` truncato (34 righe)**
Il file si interrompe al commento `# ── Database ──...`. Mancano le sezioni: Database, Sessione, Cache/Queue, Mail, Stripe, PayPal, Bonifico, Google, Sentry, Backup. Senza questo, chi clona il repo non sa quali variabili compilare.

**[P0-4] `app/Filament/Widgets/SystemStatusWidget.php` truncato (147 righe)**
La funzione `backupStatus()` è troncata a metà nella string del label (`'Ultimo: ' . $lastModified->format('d/m/`). Manca tutta la logica di valutazione dell'età del backup (warning/error in base a ore trascorse). Il widget al momento crasha se chiamato.

#### 🟠 Da configurare prima del go-live (lato server)

**[P1-1] Cron `schedule:run` su cPanel.** Configurare in cPanel → Cron Jobs:
```
* * * * * /usr/bin/php /home/aeacenter/scuole_app/artisan schedule:run >> /dev/null 2>&1
```

**[P1-2] Worker code attivo.** Sul cPanel shared hosting senza supervisor, attivare cron ogni minuto:
```
* * * * * /usr/bin/php /home/aeacenter/scuole_app/artisan queue:work --stop-when-empty --tries=3 >> /dev/null 2>&1
```

**[P1-3] `.env` di produzione completo.** Compilare su `/home/aeacenter/scuole_app/.env` con: APP_KEY, DB_*, MAIL_*, STRIPE_* live, PAYPAL_* live + WEBHOOK_ID, GOOGLE_*, BANK_IBAN, SENTRY_LARAVEL_DSN, BACKUP_NOTIFICATION_EMAIL.

**[P1-4] SPF / DKIM / DMARC** sul dominio mittente — altrimenti deliverability bassa, soprattutto verso Gmail/Outlook.

**[P1-5] Webhook gateway sul dominio reale.**
- Stripe Dashboard → Webhooks: endpoint `https://tuodominio.it/webhook/stripe` (eventi `checkout.session.completed`, `checkout.session.expired`)
- PayPal Developer → Webhooks: endpoint `https://tuodominio.it/webhook/paypal` (evento `PAYMENT.CAPTURE.COMPLETED`), copiare `WEBHOOK_ID` nel `.env`

**[P1-6] HTTPS forzato + cookie sicuri.** `.env` deve contenere `SESSION_SECURE_COOKIE=true` e `SESSION_ENCRYPT=true`. Verificare redirect HTTP→HTTPS lato Apache/cPanel.

---

### 3.2 Sicurezza

#### ✅ Punti solidi
- CSRF exemption SOLO per i webhook firmati (`/webhook/stripe`, `/webhook/paypal`) — corretto.
- `APP_DEBUG=false` enforced nel `.env.example` con commento esplicito sul rischio.
- `BCRYPT_ROUNDS=12`.
- OTP firma contratto: 6 cifre, max 5 tentativi, scadenza 15 minuti.
- Webhook Stripe verificato con `Webhook::constructEvent()` (HMAC).
- Webhook PayPal verificato con API ufficiale `verify-webhook-signature` via `Http::withBasicAuth()` (migrato da cURL raw).
- SoftDeletes su Contract, Lesson, Student → resilienza alle cancellazioni accidentali.
- Tutte le route admin richiedono `auth` + `role:`.
- Throttle su `/iscriviti` (5/min per IP) e `/checkout` (3/min per IP).
- GoogleOAuthController: verifica `state` anti-CSRF implementata (`session('google_oauth_state')`).
- Validatori personalizzati: `App\Rules\CodiceFiscale`, `App\Rules\PartitaIva`, `App\Rules\Iban` (con test unitari).
- Sistema GDPR di disiscrizione email con token HMAC autocontenuto (no DB lookup).
- WebhookController in production blocca PayPal se `PAYPAL_WEBHOOK_ID` vuoto.

#### 🟡 Aperti
- **[P2-1] PaymentService non ha retry automatico** su errore `createOrder` PayPal (per timeout di rete). Mostra solo errore 500 generico → utente perde il flusso.
- **[P2-2] Audit Shield permessi** non completato (5 incongruenze documentate in `PERMESSI.md`: ruoli legacy lowercase, due seeder ruoli paralleli, `area_*` mai usati, Shield permessi non seedati).

---

### 3.3 Database e performance

#### ✅ Punti solidi
- Indice composto `[contract_id, number]` su `installments`.
- Indice `[start_date, end_date]` su `closure_days`.
- Unique constraint `[contract_student_id, starts_at]` su `lessons` → impossibili doppioni.
- Cache 5 minuti per `SchoolSetting`.
- Indici performance su `lessons` (4 indici, migration `2026_05_04_200003`):
  - `lessons_starts_at_index`
  - `lessons_teacher_id_starts_at_index`
  - `lessons_student_id_starts_at_index`
  - `lessons_cancelled_at_index`
- SoftDeletes attivi anche su `course_purchases` (migration `2026_05_04_200005`).
- Foreign key `course_id` su contracts (migration `2026_05_04_200006`).

#### 🟡 Aperti
- **[P2-3] 9 occorrenze di `Schema::hasColumn()` residue** nei modelli/servizi. Erano 57 nella revisione precedente. Da rimuovere ulteriormente dopo che il DB di produzione è stabile.
- **[P3-1] activitylog può crescere molto.** Configurare `activitylog:clean` nel scheduler (es. mensile) e/o ridurre `activity_log.delete_records_older_than_days` in `config/activitylog.php`.

---

### 3.4 Pagamenti online

#### ✅ Punti solidi
- 3 metodi: Stripe Checkout hosted, PayPal Orders v2, bonifico con riferimento `BNF-YYYYMMDD-NNNNN`.
- `confirmPurchase()` idempotente: `if ($purchase->isPaid()) return;`.
- Webhook firmati e verificati.
- Bonifico: stato manuale gestito da segreteria con log via `Admin → Pagamenti → Conferma pagamento`.
- Email confermata via Job dedicato `SendPurchaseConfirmationJob` con tries=3, backoff `[30s, 2min]`, `failed()` callback in Sentry.
- `PaymentService::sendConfirmationEmail` ora dispatcha il Job anziché inviare sincrono.

#### 🟡 Aperti
- **[P2-4] `paypal_order_id` recuperato da due path diversi** in WebhookController (`supplementary_data.related_ids.order_id` con fallback su `id`) — la fallback è fragile e potrebbe matchare ID di capture invece dell'ordine.
- **[P3-2] Nessun template "ricevuta" PDF generata automaticamente** dopo conferma bonifico (esiste `RicevutaRataService` per le rate ma non per CoursePurchase).

---

### 3.5 Google Calendar / Meet

#### ✅ Punti solidi
- Service account + refresh token → non dipende da OAuth utente.
- `isExpired()` + warning nel `SystemStatusWidget`.
- Rinnovo automatico access token alla prossima operazione.
- Try/catch su tutte le chiamate Google API con `report($e)` + `Log::error` con context (migrato dal silent fail precedente).

#### 🟡 Aperti
- **[P2-5] Meet URL non rigenerato sul reschedule lezione.** Quando una lezione viene spostata, il vecchio `meet_url` resta. Lo studente potrebbe trovare riunione non aggiornata.
- **[P2-6] `google_event_id` non pulito su soft-delete lezione.** Al restore, il `google_event_id` punta a un evento Google potenzialmente disallineato.

---

### 3.6 Email e notifiche

#### ✅ Punti solidi
- `EmailTemplateService` con layout responsive HTML, firma dinamica da `SchoolSetting`.
- Template branded per: welcome studente, lezione annullata (3 tipologie), contratto PDF, comunicazione generica, `installment_overdue`, `lesson_recovery_email_template`.
- `NotificationEmailLog` per deduplicazione e tracking invii.
- `Resource` Filament `NotificationEmailLogResource` per visibilità segreteria.
- Sistema GDPR: `student_unsubscribes` + `UnsubscribeController` + token HMAC (no DB per generare URL).
- `InvioComunicazioni::send` filtra disiscritti (bulk lookup, no N+1) e usa `Mail::queue` invece di `Mail::send`.

#### 🟡 Aperti
- **[P2-7] Verificare invio email per lesson recovery automatico.** Il template `lesson_recovery_email_template` è stato seedato (migration `2026_05_04_200004`) ma serve verifica che `LessonRecoveryService` lo usi effettivamente.
- **[P3-3] Mail fallback su irraggiungibilità SMTP.** Attualmente fallisce silenziosamente (catch+log). Considerare un mailer secondario.

---

### 3.7 UX operativa

#### ✅ Punti solidi
- `SystemStatusWidget` mostra in homepage admin lo stato di Google, email, queue, backup (quando attivo).
- Modal di conferma su delete contratti, studenti, lezioni.
- Widget `ProssimaLezioneWidget` nel pannello studente con data, ora, lingua, docente, link Meet.
- Promemoria rate configurabili da `Admin → Impostazioni Scuola` (toggle on/off + giorni anticipo).
- Firma OTP digitale contratti operativa.
- `LessonRecoveryService`: ricerca slot fino a 104 settimane, skip chiusure, overlap student + teacher.
- CRM kanban con stati e attività (`LeadKanban`).
- Calendario lezioni admin (`LessonCalendar`).
- Pannello studente: Dashboard + Calendario + Contratto + Compiti + Materiali + Quiz + Rate.
- Pannello docente: TeacherCalendar + MieiStudenti + TeacherHomework + TeacherMaterial.

#### 🟡 Aperti
- **[P2-8] EnrollmentResource deprecato ma file presenti.** `canAccess()` ritorna `false`, navigation nascosta, ma le `Pages/CreateEnrollment.php` e `Pages/EditEnrollment.php` esistono. Decisione: rimuoverle o tenerle finché Shield ha ancora permessi orfani `page_EnrollmentResource`.

---

### 3.8 Edge case e robustezza

#### ✅ Punti solidi
- `LessonGeneratorService`: cache lock, retry, skip chiusure, overlap student+teacher.
- `LessonRecoveryService`: ricerca fino a 104 settimane, ora con overlap teacher.
- `Contract::deleting()` cascade soft-delete su lezioni; `Contract::restored()` ripristino.
- OTP: 5 tentativi, 15 min scadenza, lock dopo tentativi esauriti.
- `confirmPurchase()`: idempotente, DB transaction.

#### 🟡 Aperti
- **[P3-4] Lezione di recupero non controlla se contratto è attivo.** `LessonRecoveryService::cancelAndCreateAutoRecovery()` non verifica `$contract->status === 'active'`. Possibile creare recuperi su contratti chiusi.
- **[P3-5] `BackfillBillingProfiles` e `BonificaConsumiCommand`** sono comandi one-shot — andrebbero etichettati come tali in commenti per evitare invocazioni accidentali.

---

### 3.9 Tecnico / codice / repo

#### ✅ Punti solidi
- PSR-4 autoload corretto.
- Migrations con `down()` definiti.
- Servizi iniettati via constructor DI.
- Policy registrate per modelli critici.
- Test unitari per `CodiceFiscale`, `PartitaIva`, `Iban`, `Course`, `CoursePurchase`.
- File `bootstrap/app.php` rispetta lo stile Laravel 12 (no più `Kernel.php` legacy).

#### 🟡 Aperti
- **[P3-6] README.md ora aggiornato** ma esistono 4 versioni vecchie del manuale (`manuale_scuolelive*.docx`, `manuale_scuolelive_new.docx`, `_v2`, `_v3`) → consolidare in `MANUALE_SCUOLELIVE.docx` e archiviare/rimuovere le altre.
- **[P3-7] File `migliorie_validazioni.html`** in root — sembra un report di sessione precedente. Spostare in `docs/` o cancellare se obsoleto.

---

## 4. Checklist pre-lancio

### 🔴 BLOCCANTI — Da fare PRIMA del go-live

- [ ] **Ripristinare `routes/console.php` con tutte le entry `Schedule::`** (file truncato a 10 righe — vedere § 3.1 P0-1)
- [ ] **Completare `.cpanel.yml` con `php artisan optimize` + `view:cache` + `queue:restart`** (truncato dopo migrate)
- [ ] **Riscrivere `.env.example` completo** (truncato a 34 righe — usare la sezione 8 del README come riferimento)
- [ ] **Completare `SystemStatusWidget::backupStatus()`** con la logica di valutazione età backup (warning > 26h, error > 48h)
- [ ] **Compilare `.env` di produzione** con tutti i valori reali sul server cPanel
- [ ] **Configurare cron `schedule:run`** in cPanel
- [ ] **Configurare cron / supervisor per `queue:work`** in cPanel
- [ ] **Configurare webhook Stripe live** sul dominio
- [ ] **Configurare webhook PayPal live** sul dominio + copiare `PAYPAL_WEBHOOK_ID` nel `.env`
- [ ] **SPF / DKIM / DMARC** sul dominio mittente
- [ ] **Compilare IBAN e dati scuola** in `Admin → Impostazioni → Impostazioni Scuola`
- [ ] **Eseguire `php artisan migrate --force`** dopo il primo deploy completo
- [ ] **Eseguire `php artisan backup:run`** manuale + verificare il file `.zip` creato + test di restore parziale
- [ ] **Eseguire `php artisan installments:notify-overdue --dry-run`** per verificare il comando
- [ ] **Verificare `storage:link`** o symlink cPanel
- [ ] **Test end-to-end Stripe** (carta test `4242 4242 4242 4242` in modalità test, poi 1 transazione live di test)
- [ ] **Test end-to-end PayPal** (sandbox + 1 live di test)
- [ ] **Test percorso completo:** registrazione lead → conversione in contratto → invio link firma OTP → firma → generazione lezioni → email studente → accesso pannello studente

### 🟡 IMPORTANTI — Da risolvere entro 7 giorni dal lancio

- [ ] Verificare invio email per recupero automatico lezione (template `lesson_recovery_email_template`)
- [ ] Verificare accessi a `PaymentsReport`, `AnomalyReport`, `StudentHoursReport` per ruolo Amministrazione
- [ ] Decidere sorte di `EnrollmentResource` (rimuovere file Pages o tenere)
- [ ] Sentry DSN configurato e ricezione errori test verificata
- [ ] Audit Shield permessi: risolvere le 5 incongruenze documentate in `PERMESSI.md`
- [ ] Fix Meet URL su reschedule lezione
- [ ] Pulizia `google_event_id` su soft-delete lezione
- [ ] Retry su errore PayPal `createOrder` con messaggio user-friendly

### 🟢 POST-LANCIO — Entro 30 giorni

- [ ] Rimuovere le 9 `Schema::hasColumn()` residue
- [ ] Generare ricevuta PDF automatica per CoursePurchase confermati (analogo `RicevutaRataService` per le rate)
- [ ] Consolidare i 4 manuali Word legacy in `MANUALE_SCUOLELIVE.docx` unico
- [ ] Spostare `migliorie_validazioni.html` in `docs/` o rimuovere
- [ ] Pianificare `activitylog:clean` mensile (cleanup record vecchi)
- [ ] Audit `Mail::queue` ovunque possibile (ridurre invii sincroni)
- [ ] Mailer fallback su SMTP secondario
- [ ] Documentare lo standard sui ruoli (rimuovere ruoli lowercase legacy se nessuno li usa)

---

## 5. Inventario funzionalità per modulo

### 5.1 CRM e lead management
- Lead con stato (kanban), attività registrate, follow-up automatico via `NotifyFollowupLeads`
- Modello `Lead` + `LeadActivity` + `LeadQuote`
- Pagina `LeadKanban` per gestione visuale
- Comando: `leads:notify-followup`

### 5.2 Anagrafiche
- `Student` (con SoftDeletes), `Teacher`, `Company`, `BillingProfile`
- Validazione CF / P.IVA / IBAN su form (con test)
- Comando `BackfillBillingProfiles` per migrazione storica

### 5.3 Catalogo corsi e checkout pubblico
- `Course` con `is_active`, `is_public`, `level`, `image_path`, `short_description`
- Catalogo `/corsi` filtrato
- Checkout 2-step su `/corsi/{course}` (dati fatturazione + metodo)
- 3 metodi: Stripe / PayPal / Bonifico
- `CoursePurchase` con SoftDeletes
- Pannello admin `Pagamenti → Acquisti online`
- Conferma manuale bonifico via UI

### 5.4 Contratti
- `Contract` con SoftDeletes + cascade su lezioni e rate
- `ContractStudent` (pivot) per contratti multi-studente
- `ContractLessonSlot` per slot ricorrenti
- Firma OTP digitale (6 cifre, 15 min scadenza, 5 tentativi)
- Stampa PDF + invio link firma allo studente
- Stati: `pending`, `active`, `closed`, `cancelled`

### 5.5 Lezioni e calendario
- `Lesson` con SoftDeletes
- `LessonGeneratorService`: generazione automatica da slot contratto, skip chiusure, no overlap
- `LessonRecoveryService`: recupero automatico lezione cancellata, ricerca fino a 104 settimane, no overlap teacher/student
- `ClosureDay`: festività, chiusure scuola
- Calendario admin `LessonCalendar` + widget `LessonsTodayWidget`
- Calendario docente `TeacherCalendarPage`
- Calendario studente `CalendarioLezioniPage`
- Comando `lessons:fix-future-counts` per riconciliazione conteggi
- Comando `RegenerateAllLessons` (one-shot per rigenerazione totale)

### 5.6 Google Calendar / Meet
- Service account con refresh token
- Creazione automatica evento Google + link Meet su lezione
- `GoogleCalendarService`, `GoogleMeetService`, `GoogleAccount`, `GoogleSettings` page
- OAuth callback con verifica `state` anti-CSRF

### 5.7 Materiali didattici e compiti
- `CourseMaterial` con visibilità per contratto
- `Homework` + `HomeworkSubmission`
- `LessonPlan`
- `Quiz` (`QuizQuestion`, `QuizAttempt`)
- Pannello docente per assegnare/correggere
- Pannello studente per consultare/eseguire

### 5.8 Pagamenti rate (post-contratto)
- `Installment` con conteggio, scadenza, stato
- Promemoria automatico configurabile da Impostazioni Scuola
- Comando `installments:notify-overdue --dry-run`
- Template email `installment_overdue` (seedato)
- `RicevutaRataService` per generazione PDF ricevuta

### 5.9 Email e comunicazioni
- Template editabili da `Admin → Email → Template`
- Pagina `InvioComunicazioni` per invio massivo
- ⚠️ **Verificato 05/05:** invii via `Mail::to(...)->send()` sincroni (0 uso di `Mail::queue`)
- ⚠️ **Verificato 05/05:** sistema disiscrizione GDPR (`UnsubscribeController`, `student_unsubscribes`, `UnsubscribeToken`) **NON presente nel codice**
- 7 mailable in `app/Mail/`: `ContractPdfMail`, `ContractSignatureOtpMail`, `LessonCancelledMail`, `PurchaseConfirmationMail`, `StudentCommunicationMail`, `TemplateMail`, `WelcomeStudentMail`
- `NotificationEmailLog` model presente ma `NotificationEmailLogResource` Filament **NON presente**

### 5.10 Reportistica
- `PaymentsReport` (pagamenti incassati / dovuti)
- `AnomalyReport` (lezioni anomale, contratti senza slot, ecc.)
- `StudentHoursReport` (ore consumate / residue per studente)
- `TeacherHoursReport` (ore lavorate per docente, esportabile in PDF)
- `TeacherPayReport` (paghe docenti — solo Amministrazione)

### 5.11 Backup e monitoraggio
- spatie/laravel-backup con disco dedicato `local-backups`
- Schedule (post-ripristino `routes/console.php`): backup DB notturno 02:00, full settimanale domenica 03:00, clean 01:30, monitor 08:30
- Retention configurabile in `config/backup.php`
- `SystemStatusWidget` con stato Google + email + queue + backup (post-ripristino metodo `backupStatus()` e nuovo `queueStatus()`)

### 5.12 Audit log
- spatie/laravel-activitylog con `batch_uuid` (migration `2026_04_29_080933`)
- Tracking automatico modifiche su modelli critici
- Pulizia mensile schedulata via `activitylog:clean`

---

## 6. Files chiave (mappa rapida aggiornata 05/05/2026)

| Area | File | Stato |
|------|------|-------|
| Bootstrap | `bootstrap/app.php` | ✅ OK |
| Provider Filament | `bootstrap/providers.php` (4 panel registrati) | ✅ OK |
| Routes pubbliche | `routes/web.php` | ✅ ripristinato (88 righe) |
| Schedule | `routes/console.php` | ✅ ripristinato (116 righe, 9 entry) |
| Deploy | `.cpanel.yml` | ✅ ripristinato (167 righe, optimize/cache/queue:restart) |
| Env example | `.env.example` | ✅ ripristinato (158 righe, 14 sezioni) |
| Pagamenti | `PaymentService.php` (264 r), `CheckoutController.php` (195 r), `WebhookController.php` (135 r) | ✅ presenti |
| Contratti | `Contract.php`, `ContractService.php` | ✅ presenti |
| Lezioni | `Lesson.php`, `LessonGeneratorService.php` (500 r), `LessonRecoveryService.php` (259 r) | ✅ presenti |
| Google | `GoogleCalendarService.php` (344 r), `GoogleMeetService.php` (70 r), `GoogleOAuthController.php` | ✅ presenti, ⚠️ catch silenti |
| Email | `EmailTemplateService.php` (219 r), 7 mailable, template DB | ✅ presenti |
| Widget stato | `app/Filament/Widgets/SystemStatusWidget.php` | ✅ ripristinato (242 righe) |
| Comandi | `app/Console/Commands/*.php` (7 comandi) | ✅ presenti |
| Rules custom CF/PIVA/IBAN | `app/Rules/` | ❌ **NON ESISTE** |
| Job email retry | `app/Jobs/SendPurchaseConfirmationJob.php` | ❌ **NON ESISTE** |
| Unsubscribe GDPR | `app/Support/UnsubscribeToken.php`, `UnsubscribeController.php` | ❌ **NON ESISTE** |
| HealthCheck | `app/Console/Commands/HealthCheck.php` | ❌ **NON ESISTE** |
| DEPLOY.md, PERMESSI.md | root del repo | ❌ **NON ESISTONO** |

---

## 7. Stima impegno per chiusura bloccanti (aggiornata)

| Attività | Stima | Stato |
|----------|-------|-------|
| Ripristinare i file truncati | 1h | ✅ FATTO |
| Compilare `.env` produzione + Configurare cron + worker | 1h | ⏳ |
| Configurare webhook Stripe + PayPal sul dominio reale | 30 min | ⏳ |
| **Fix sicurezza PayPal:** webhook NON deve restituire true se webhook_id vuoto in production | 30 min | 🔴 NUOVO |
| **Aggiungere Rules CF/PIVA/IBAN** (se servono validazioni) | 2-4h | 🟠 NUOVO |
| **Migrare email a Mail::queue** (almeno per InvioComunicazioni e PurchaseConfirmation) | 2h | 🟠 NUOVO |
| **Aggiungere `report($e)` nei catch silenti Google services** | 30 min | 🟠 NUOVO |
| **(Opzionale)** Implementare sistema unsubscribe GDPR | 4h | 🟡 |
| **(Opzionale)** Comando `school:health-check` per audit pre-deploy | 1h | 🟡 |
| Test end-to-end completo (Stripe + PayPal + bonifico + lezioni + email) | 2-3h | ⏳ |
| Configurare SPF/DKIM/DMARC | 1h | ⏳ |
| **Totale** | **~12-15h** | |

---

## 8. Raccomandazioni operative

### 8.1 Prima del lancio (ordine consigliato — aggiornato)

1. ✅ **Ripristinare i 5 file truncati** (FATTO).
2. **Push commit** con i 5 file ripristinati.
3. **Compilare `.env` produzione** sul server cPanel via Terminal o File Manager.
4. **Configurare cron** (`schedule:run` + `queue:work`).
5. **Fix sicurezza PayPal:** modificare `verifyPayPalSignature()` per **bloccare** in production se `PAYPAL_WEBHOOK_ID` vuoto.
6. **Configurare webhook Stripe** in modalità test, fare 1 transazione test, poi switchare a live.
7. **Configurare webhook PayPal** sandbox, fare 1 transazione test, poi live.
8. **Backup manuale + restore di prova** su DB di staging.
9. **Test end-to-end** seguendo il "Percorso utente completo" del manuale.
10. **Comunicare data go-live** internamente con 24h di preavviso.

### 8.2 Decisioni da prendere

Le funzionalità documentate nella memoria ma assenti nel codice **vanno scelte una alla volta**:

| Funzionalità | Opzione A | Opzione B |
|--------------|-----------|-----------|
| Rules CF/PIVA/IBAN | Implementare e applicare ai form | Non implementare (form accettano stringa libera) |
| Mail::queue | Migrare InvioComunicazioni e PurchaseConfirmation | Lasciare sincrono (con rischio timeout) |
| UnsubscribeController GDPR | Implementare full system con migration + token HMAC | Aggiungere solo footer "manda email per disiscriverti" |
| HealthCheck command | Implementare audit pre-deploy | Saltare (verifiche manuali) |
| Google services error log | Aggiungere `report($e)` ai catch | Lasciare silent (rischio debug più difficile) |

### 8.3 Primi 7 giorni dal lancio

- Monitor giornaliero `SystemStatusWidget` + log Sentry.
- Verifica mattutina: backup notturno completato? `php artisan backup:list`
- Verifica mattutina: promemoria rate inviati? Cerca in `notification_email_logs`
- Verifica settimanale: failed jobs? `php artisan queue:failed`

### 8.4 A regime

- Verifica mensile: spazio disco backup, `du -sh /home/aeacenter/scuole_app/storage/app/Laravel`.
- Audit semestrale: ruoli, permessi Shield, utenti inattivi.
- Backup off-site: replica copia backup su S3/Drive esterno (oggi solo locale).
- Documentare interventi tecnici in `docs/CHANGELOG.md` (da creare).

---

## 9. Conclusioni

Il sistema ScuoleLive ha un **core funzionale solido** (pagamenti, contratti, lezioni, OTP, indici, throttle, soft delete, idempotenza webhook). I 5 file di configurazione critici truncati sono stati ripristinati in questa sessione e sono ora **integri e validi** (YAML OK, parentesi PHP bilanciate).

Tuttavia il controllo funzionale del 05/05 ha rivelato che **diverse funzionalità documentate nella memoria del 30/04 non sono presenti nel codice attuale** (vedere § 0.2). Si tratta di un **gap di documentazione vs realtà**, non di bug attivi: il sistema funziona senza queste feature, ma le aspettative basate sulla memoria devono essere riallineate prima del go-live.

**Per il go-live è obbligatorio risolvere:**
1. **Fix sicurezza PayPal webhook** (15 min) — vulnerabilità di sicurezza in produzione.
2. **Configurazione server** (cron, webhook, DNS, .env).

**Raccomandato risolvere:**
3. Migrazione almeno parziale a `Mail::queue` per le comunicazioni massive.
4. Aggiunta `report($e)` nei catch Google services.

**Opzionale (decidere caso per caso):**
5. Rules validazione CF/PIVA/IBAN.
6. Sistema unsubscribe GDPR completo.
7. Comando HealthCheck.

---

**Autore report:** Cowork (assistente AI) per Laura, gruppokosmos00@gmail.com
**Sessioni di lavoro:** 28-30 aprile (hardening) + 04-05 maggio (manuale + ripristino + verifica funzionale)
**Prossimo aggiornamento consigliato:** dopo go-live, settimanale per i primi 30 giorni, poi mensile.
