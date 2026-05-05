# ScuoleLive — Report Pre-Lancio & Checklist
**Data analisi:** 04/05/2026 | **Revisione:** 2 (post batch fix 1+2)
**Stack:** Laravel 12 · Filament 3 · MySQL · cPanel (aeacenter) · ~100 utenti · Single-tenant

---

## STATO ATTUALE: FIX APPLICATI IN QUESTA SESSIONE

| # | Fix | File principali | Stato |
|---|-----|-----------------|-------|
| 1 | SoftDeletes su Contract, Lesson, Student + cascade + modal conferma delete | migration, Contract.php, Lesson.php, Student.php, EditContract.php | ✅ |
| 2 | Password reset multi-panel (era hardcoded 'docente') | AppServiceProvider.php | ✅ |
| 3 | `.env.example` produzione-sicuro (APP_DEBUG=false, SESSION_ENCRYPT, chiavi live) | .env.example | ✅ |
| 4 | spatie/laravel-backup: schedule giornaliero, retention, monitor, disco local-backups | config/backup.php, filesystems.php, console.php, SystemStatusWidget | ✅ |
| 5 | Fix overlap docente in LessonRecoveryService (era solo student check) | LessonRecoveryService.php | ✅ |
| 6 | Ruoli granulari: Amministrazione/Segreteria (GestioneOperazioni, TeacherPay/HoursReport, ReportLinks blade) | GestioneOperazioni, TeacherPayReport, TeacherHoursReport, report-links.blade | ✅ |
| 7 | Promemoria rate scadute: toggle+giorni in ImpostazioniScuola, comando installments:notify-overdue, schedule, migration template email | NotifyOverdueInstallments.php, ImpostazioniScuola.php, migration | ✅ |
| 8 | Widget prossima lezione pannello studente (data, ora, lingua, docente, Meet link) | ProssimaLezioneWidget.php, prossima-lezione.blade.php | ✅ |

---

## A — ARCHITETTURA & CONFIGURAZIONE

### ✅ Punti solidi
- 4 panel Filament separati (`superadmin`, `admin`, `docente`, `studente`) — isolamento ottimo
- `DB::afterCommit` + `Cache::lock` nel pipeline contratti — concorrenza gestita
- spatie/permission + Shield + Policy per ogni modello critico
- SoftDeletes ora attivi su Contract, Lesson, Student con cascade
- Webhook Stripe verificato con firma HMAC — sicuro
- Webhook PayPal verificato tramite API `verify-webhook-signature` — sicuro (era raw cURL, ora Http facade nel controller)
- `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true` nel .env.example
- Cron schedule: backup 02:00, promemoria 09:00, CRM lead 08:00, monitor backup 08:30

### ⚠️ Problemi aperti architettura

**[P0] `.cpanel.yml` non esegue `php artisan migrate`**
Il deploy automatico cPanel esegue optimize, config:clear, queue:restart ma **NON le migration**. Ogni deploy con nuove migration richiede un passaggio manuale da Terminal cPanel.

```yaml
# DA AGGIUNGERE in .cpanel.yml prima del passo optimize:
- cd $APP_PATH && /usr/bin/php artisan migrate --force 2>&1 | /usr/bin/tee -a $DEPLOY_LOG || true
```

**[P1] Cron `schedule:run` non configurato nel deploy**
Il `.cpanel.yml` fa `queue:restart` ma non documenta il cron entry per lo scheduler Laravel. Senza cron attivo: nessun backup notturno, nessun promemoria rate, nessun CRM follow-up.
Entry cron cPanel da aggiungere (ogni minuto):
```
* * * * * /usr/bin/php /home/aeacenter/scuole_app/artisan schedule:run >> /dev/null 2>&1
```

**[P1] `optimize-autoloader: false` in composer.json**
In produzione dovrebbe essere `true` per precaricare la classmap e ridurre I/O.

---

## B — SICUREZZA

### ✅ Punti solidi
- CSRF escluso solo per `/webhook/stripe` e `/webhook/paypal` — corretto
- `APP_DEBUG=false` in .env.example
- `BCRYPT_ROUNDS=12`
- OTP contratto: 6 cifre, 5 tentativi, 15 min scadenza
- Firma Stripe con `Webhook::constructEvent()` — HMAC verificato
- SoftDeletes protegge da perdita accidentale dati
- Tutte le route admin richiedono `auth` + `role:`

### 🔴 Problemi sicurezza

**[P0] Nessun rate limiting sui form pubblici**
Le route `/iscriviti` (POST) e `/corsi/{course}/checkout` (POST) non hanno throttle. Vulnerabili a:
- Spam di lead fittizi
- Creazione massiva di `CoursePurchase` pending
- Abuse di Stripe checkout session creation

```php
// Da aggiungere in routes/web.php:
Route::post('/iscriviti', ...)->middleware('throttle:5,1');
Route::post('/corsi/{course}/checkout', ...)->middleware('throttle:3,1');
```

**[P1] `PaymentService::getPaypalToken()` usa cURL raw senza error handling**
Se cURL fallisce (timeout, DNS), `curl_exec()` restituisce `false`, `json_decode(false)` restituisce `null`, `getPaypalToken()` restituisce stringa vuota `''`. Il pagamento PayPal viene allora tentato con token vuoto e può fallire silenziosamente senza eccezione.

```php
// Problema in PaymentService.php ~riga 144:
$res = json_decode(curl_exec($ch), true);
// curl_exec può restituire false — nessun check
return $res['access_token'] ?? ''; // '' → Bearer  → 401 silenzioso
```

Fix: migrare `paypalPost()` e `getPaypalToken()` a `Http::withBasicAuth()` (già usato nel WebhookController per lo stesso scopo).

**[P2] `GoogleOAuthController::callback()` senza verifica `state` CSRF**
Il callback OAuth non verifica il parametro `state` per prevenire CSRF sul flusso OAuth. Verificare che Google SDK gestisca internamente o aggiungere verifica manuale.

---

## C — RUOLI E ACCESSI (matrice aggiornata)

| Funzione | Superadmin | Admin | Amministrazione | Segreteria | Docente | Studente |
|----------|-----------|-------|-----------------|------------|---------|---------|
| GestioneOperazioni | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Comandi (SuperadminCommands) | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| TeacherPayReport (Paghe) | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| TeacherHoursReport (Ore) | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| TeacherResource (dati fiscali) | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| StudentResource | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| ContractResource | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| LessonResource | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| PaymentsReport | ✅ | ❌* | ❌* | ❌ | ❌ | ❌ |
| AnomalyReport | ✅ | ❌* | ❌* | ❌ | ❌ | ❌ |
| StudentHoursReport | ✅ | ❌* | ❌* | ❌ | ❌ | ❌ |
| ImpostazioniScuola | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |

> ❌* = visibile solo a superadmin o tramite permesso Shield granulare. **Verificare** se Amministrazione deve vedere PaymentsReport, AnomalyReport, StudentHoursReport — attualmente non può.

### [P1] EnrollmentResource hardcoded `return false`
`EnrollmentResource::canAccess()` restituisce sempre `false`. La risorsa è inaccessibile a tutti. Decisione necessaria: deprecare definitivamente (rimuovere dal nav, aggiungere commento) o completare l'implementazione.

---

## D — DATABASE & PERFORMANCE

### ✅ Punti solidi
- Indice composto `[contract_id, number]` su installments
- Indice `[start_date, end_date]` su closure_days — query chiusure O(log n)
- Unique constraint `[contract_student_id, starts_at]` su lessons — previene doppioni
- Cache 5 minuti per SchoolSetting — evita query ripetute

### 🔴 Problemi performance

**[P0] Nessun indice su `lessons.starts_at` e `lessons.teacher_id`**
Tutte le query di overlap (`getBusyLessonForSlot`), calendario e widget studente filtrano per `starts_at` e/o `teacher_id`. Con 1000+ lezioni, full table scan garantito.

```php
// Migration da creare:
$table->index(['starts_at']);
$table->index(['teacher_id', 'starts_at']);
$table->index(['student_id', 'starts_at']);
$table->index(['cancelled_at']); // per query whereNull('cancelled_at')
```

**[P1] 57 occorrenze di `Schema::hasColumn()` nei modelli e servizi**
`Schema::hasColumn()` esegue una query `INFORMATION_SCHEMA` per ogni chiamata. Con 57 occorrenze nei modelli/servizi (Contract, ContractStudent, LessonGenerator, ecc.), ogni richiesta che tocca questi modelli esegue decine di query extra.
Questi check erano sicurezza per colonne aggiunte iterativamente — da rimuovere dopo deploy stabile su produzione.
**File interessati:** Contract.php, ContractStudent.php, ContractService.php, LessonGeneratorService.php, PublicController.php, StudentObserver.php

---

## E — PAGAMENTI

### ✅ Punti solidi
- Stripe: SDK ufficiale, firma webhook verificata, idempotenza su `isPaid()`
- PayPal webhook: verifica API ufficiale (`verify-webhook-signature`) nel WebhookController
- Bonifico: manual confirm con log — corretto per un flusso non automatizzato
- `confirmPurchase()` idempotente: `if ($purchase->isPaid()) return`

### ⚠️ Problemi pagamenti

**[P1] PaymentService usa cURL raw (senza Http facade) per PayPal createOrder**
Il `WebhookController::verifyPayPalSignature()` usa già `Http::withBasicAuth()` (corretto), ma `PaymentService::getPaypalToken()` e `paypalPost()` usano ancora `curl_init` raw senza:
- gestione timeout
- gestione errori SSL
- gestione `curl_errno()`

**[P2] Nessun retry su fallimento PayPal**
Se la chiamata `createOrder` PayPal fallisce per timeout di rete, l'utente vede errore 500. Nessun retry automatico né messaggio user-friendly.

**[P2] `CoursePurchase` non ha soft delete**
Un acquisto cancellato viene eliminato fisicamente. Valutare se aggiungere `deleted_at` per audit trail.

---

## F — GOOGLE CALENDAR / MEET

### ✅ Punti solidi
- Service account con refresh token — non dipende da OAuth utente
- `isExpired()` + warning nel SystemStatusWidget
- Rinnovo automatico access token alla prossima operazione
- Try/catch su tutte le chiamate Google API

### ⚠️ Problemi

**[P2] Meet URL non viene rigenerato sul reschedule**
Quando una lezione viene spostata, il `meet_url` originale rimane. Se il link è scaduto o associato al vecchio orario in Google Calendar, gli studenti potrebbero trovare una riunione non aggiornata.

**[P2] `google_event_id` non viene pulito su soft-delete lezione**
Se una lezione viene soft-deleted, l'evento Google Calendar rimane attivo. Al restore della lezione, `google_event_id` è ancora presente ma l'evento potrebbe non essere più sincronizzato.

---

## G — EMAIL & NOTIFICHE

### ✅ Punti solidi
- `EmailTemplateService` con layout responsive, firma dinamica da SchoolSetting
- Template per: welcome studente, lezione annullata (3 tipologie), contratto PDF, comunicazione generica
- `NotificationEmailLog` per deduplicazione promemoria
- Nuovi: `installment_overdue` per promemoria rate (questa sessione)
- Nuovo comando `installments:notify-overdue` con `--dry-run`

### ⚠️ Problemi

**[P1] Nessun template per la lezione di recupero creata automaticamente**
Quando `LessonRecoveryService` crea un recupero automatico, nessuna email viene inviata allo studente. Lo studente non sa che è stato programmato un recupero.

**[P2] `MAIL_MAILER=smtp` nel .env.example ma senza fallback**
Se il mail server è irraggiungibile, le email falliscono silenziosamente (catch+log in EmailTemplateService). Valutare queue per email (`Mail::to()->queue()`) invece di invio sincrono.

**[P2] `BACKUP_NOTIFICATION_EMAIL` — se non configurato, le notifiche backup vanno a `admin@example.com`**
Il default nel `config/backup.php` è `env('BACKUP_NOTIFICATION_EMAIL', env('MAIL_FROM_ADDRESS', 'admin@example.com'))`. Verificare che sia configurato in produzione.

---

## H — UX OPERATIVA

### ✅ Punti solidi
- SystemStatusWidget mostra stato Google, email, queue, backup in real-time
- Modal di conferma su delete contratti, studenti, lezioni (questa sessione)
- Widget prossima lezione nel pannello studente (questa sessione)
- Promemoria rate configurabili da ImpostazioniScuola (questa sessione)
- Firma OTP digitale contratti funzionante
- LessonRecoveryService trova slot con skip chiusure e overlap (ora anche teacher overlap)

### ⚠️ Problemi UX

**[P2] Nessuna email di conferma creazione recupero allo studente**
Vedi sezione G — quando viene creato un recupero automatico, lo studente non riceve notifica.

**[P2] Widget studente: `Carbon::translatedFormat('l d F Y')` richiede locale it**
Il metodo `translatedFormat` richiede che Carbon abbia le traduzioni italiane caricate. Laravel imposta `APP_LOCALE=it` ma Carbon usa la propria locale separatamente. Da verificare che `Carbon::setLocale('it')` sia in AppServiceProvider o che le traduzioni siano installate.

**[P3] EnrollmentResource nascosta ma accessibile via URL diretto**
`$shouldRegisterNavigation = false` e `canAccess() = false` — ma le pagine Pages/CreateEnrollment.php e Pages/EditEnrollment.php esistono. Un utente con il link diretto riceve 403 (corretto) ma il codice è confusionario.

---

## I — EDGE CASE & ROBUSTEZZA

### ✅ Punti solidi
- `LessonGeneratorService`: cache lock, retry, skip chiusure, overlap student+teacher
- `LessonRecoveryService`: ricerca fino a 104 settimane, ora con overlap teacher (fix questa sessione)
- `Contract::deleting()`: cascade soft-delete su lezioni; `Contract::restored()`: ripristino
- OTP: 5 tentativi, 15 min scadenza, lock dopo tentativi esauriti
- `confirmPurchase()`: idempotente, DB transaction

### ⚠️ Edge case aperti

**[P1] `getPaypalToken()` restituisce `''` su errore cURL — pagamento silenziosamente fallito**
Già descritto in sezione B/E.

**[P2] `backupStatus()` nel SystemStatusWidget: logica errata**
```php
if ($age > 25) { return error; }
if ($age > 26) { return warning; } // ← IRRAGGIUNGIBILE: 26 > 25 sempre vero
```
Il controllo `$age > 26` per il warning non verrà mai eseguito perché il controllo precedente `$age > 25` lo intercetta prima. Il warning per backup tra 25 e 26 ore è di fatto un errore.

**[P2] Lezione di recupero non controlla se il contratto è attivo**
`LessonRecoveryService::cancelAndCreateAutoRecovery()` non verifica se il contratto associato è ancora `status = 'active'`. È possibile creare recuperi su contratti terminati/cancellati.

**[P3] `CoursePurchase` PayPal: `paypal_order_id` recuperato da due campi diversi**
Nel WebhookController: `data_get($resource, 'supplementary_data.related_ids.order_id') ?? data_get($resource, 'id')` — la fallback su `resource.id` è fragile e potrebbe matchare l'ID della capture invece dell'ordine.

---

## J — TECNICO / CODICE

### ✅ Punti solidi
- PSR-4 autoload corretto
- Migrations con `down()` definiti correttamente
- Tutti i servizi iniettati via constructor DI
- Policy per ogni modello critico registrate

### ⚠️ Problemi tecnici

**[P2] `composer.json`: `optimize-autoloader: false`**
Rallenta il caricamento classi in produzione. Impostare `true`.

**[P3] `Schema::hasColumn()` — 57 occorrenze — rimuovere post-deploy**
Sono tutti check di compatibilità aggiunti durante lo sviluppo iterativo. Ora che il DB è stabile, vanno rimossi.

**[P3] `README.md` è il README default Laravel**
Non contiene informazioni sul progetto. Da aggiornare con: struttura cartelle, setup locale, credenziali di test, link cPanel.

**[P3] Commento in contracts migration**
`// $table->foreign('course_id')` — foreign key commentata. Verificare se è intenzionale o dimenticata.

---

## K — CHECKLIST PRE-LANCIO

### 🔴 BLOCCANTI — Da fare PRIMA del go-live

- [ ] **Aggiungere `php artisan migrate --force` nel `.cpanel.yml`** (step 7.5, dopo composer install)
- [ ] **Configurare il cron cPanel per `schedule:run`** — senza questo: nessun backup, nessun promemoria, nessun CRM
- [ ] **Aggiungere throttle sui form pubblici** `/iscriviti` e `/corsi/*/checkout` (max 5/min e 3/min)
- [ ] **Creare migration per indici su `lessons`**: `starts_at`, `teacher_id`, `student_id`, `cancelled_at`
- [ ] **Fix `PaymentService::getPaypalToken()`**: sostituire cURL raw con `Http::withBasicAuth()` + gestione errori
- [ ] **Compilare il `.env` di produzione** con tutti i valori reali (APP_KEY, DB_*, MAIL_*, STRIPE_*, PAYPAL_*, GOOGLE_*, SENTRY_DSN, BACKUP_NOTIFICATION_EMAIL)
- [ ] **Eseguire `php artisan migrate`** dopo il primo deploy
- [ ] **Eseguire `php artisan backup:run`** manualmente per verificare che il backup funzioni
- [ ] **Eseguire `php artisan installments:notify-overdue --dry-run`** per verificare il comando
- [ ] **Eseguire `php artisan storage:link`** se non già fatto (il `.cpanel.yml` usa symlink manuale — verificare)

### 🟡 IMPORTANTI — Da risolvere entro 7 giorni dal lancio

- [ ] **Decidere la sorte di `EnrollmentResource`**: deprecare (rimuovere file) o completare
- [ ] **Verificare accessi a `PaymentsReport`, `AnomalyReport`, `StudentHoursReport` per Amministrazione** — attualmente visibili solo a superadmin
- [ ] **Aggiungere email di notifica per recupero automatico** — studente non sa del recovery creato
- [ ] **Fix logica backup warning/error** in `SystemStatusWidget::backupStatus()` (controllo `> 26` irraggiungibile)
- [ ] **Verificare `Carbon::setLocale('it')`** in AppServiceProvider per `translatedFormat()` nel widget studente
- [ ] **Verificare verifica `state` OAuth** in `GoogleOAuthController::callback()`
- [ ] **Aggiungere `composer.json` `optimize-autoloader: true`**
- [ ] **Configurare `SENTRY_LARAVEL_DSN`** per monitoraggio errori in produzione
- [ ] **Test manuale Stripe webhook** con `stripe listen --forward-to` (o Stripe Dashboard test)
- [ ] **Test manuale PayPal sandbox** — fare un acquisto completo

### 🟢 POST-LANCIO — Entro 30 giorni

- [ ] **Rimuovere i 57 `Schema::hasColumn()`** — creare migration per rendere il DB schema stabile e togliere tutti i check runtime
- [ ] **Aggiornare `README.md`** con documentazione del progetto
- [ ] **Valutare queue per invio email** (`Mail::queue()`) invece di invio sincrono per non bloccare le richieste
- [ ] **Valutare `CoursePurchase` soft delete** per audit trail pagamenti
- [ ] **Fix Meet URL su reschedule lezione** — rigenerare o aggiornare evento Google Calendar
- [ ] **Pulizia `google_event_id` su soft-delete lezione**
- [ ] **Retry su errore PayPal createOrder** con messaggio utente appropriato
- [ ] **Aggiungere indice su `lessons.contract_id`** se non presente (per query `->where('contract_id', ...)`)

---

## RIEPILOGO SEVERITÀ

| Priorità | Conteggio | Stato |
|----------|-----------|-------|
| 🔴 P0 — Bloccanti | 3 | Da fare ora |
| 🟠 P1 — Importanti pre-lancio | 5 | Da fare prima del go-live |
| 🟡 P2 — Importanti post-lancio | 9 | Entro 7 giorni |
| 🟢 P3 — Housekeeping | 7 | Entro 30 giorni |
| ✅ Risolti in questa sessione | 8 | Completati |

**Stima:** con i 3 P0 e i 5 P1 risolti, il sistema è **sicuro per il lancio in produzione** con ~100 utenti.
