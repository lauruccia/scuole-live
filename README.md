# ScuoleLive

Gestionale completo per scuole di lingue — Laravel 12 + Filament 3.
Single-tenant, hosted su cPanel/Aruba (account `aeacenter`), ~100 utenti attivi.

> **Versione documentazione:** 04/05/2026
> **Documenti correlati:** `MANUALE_SCUOLELIVE.docx` (manuale operativo dettagliato), `REPORT_STATO_PROGETTO.md` (report completo stato pre-lancio)

---

## Indice rapido

1. Stack e architettura
2. Setup ambiente locale
3. Ruoli e accessi
4. Struttura del progetto
5. Pagamenti online
6. Cron, code, scheduler
7. Deploy su cPanel
8. Variabili `.env`
9. Comandi artisan utili
10. FAQ rapide
11. Stato pre-lancio (sintesi)

---

## 1. Stack e architettura

| Layer | Tecnologia |
|-------|-----------|
| Framework | Laravel 12 / PHP 8.2+ |
| Admin UI | Filament 3 (4 panel: `superadmin`, `admin`, `docente`, `studente`) |
| Auth / RBAC | spatie/laravel-permission + Filament Shield + Policy |
| Pagamenti | Stripe SDK PHP, PayPal REST v2 (Http facade), Bonifico bancario manuale |
| Calendario | Google Calendar API v3 + Google Meet (service account + refresh token) |
| Backup | spatie/laravel-backup (DB notturno + full settimanale, retention configurabile) |
| Monitoraggio | Sentry (Laravel SDK), `SystemStatusWidget`, log Laravel `daily` |
| Frontend pubblico | Blade + Tailwind compilato con Vite |
| Audit / log | spatie/laravel-activitylog (con `batch_uuid`) |
| Deploy | cPanel Git Version Control + `.cpanel.yml` |

### Topologia server (cPanel aeacenter)

| Cosa | Path |
|---|---|
| Repo Git cPanel | `/home/aeacenter/repositories/scuole-live/` |
| Codice Laravel | `/home/aeacenter/scuole_app/` |
| Document root | `/home/aeacenter/public_html/` |
| GitHub | `https://github.com/lauruccia/scuole-live` |
| DB MySQL | `aealingue_scuole` su `127.0.0.1:3306` |

---

## 2. Setup ambiente locale (Laragon / WAMP / XAMPP)

```bash
# 1. Dipendenze
composer install
npm install

# 2. Ambiente
cp .env.example .env
php artisan key:generate

# 3. Database (MySQL/MariaDB locale, vedere DB_* nel .env)
php artisan migrate
php artisan db:seed          # opzionale - dati demo

# 4. Storage
php artisan storage:link

# 5. Frontend (in dev)
npm run dev

# 6. Worker + scheduler (in due terminali separati)
php artisan queue:listen --tries=3 --timeout=60
php artisan schedule:work
```

In alternativa, se nel `composer.json` è definito lo script `dev`:

```bash
composer run dev
```

URL locale tipico (Laragon Auto VHosts): `http://scuolelive.test`

---

## 3. Ruoli e accessi

| Ruolo | Pannello | Cosa vede / fa |
|-------|----------|----------------|
| `superadmin` | Superadmin + Admin | Tutto, inclusi Comandi di sistema, Impostazioni avanzate, Report sensibili |
| `Amministrazione` | Admin | Contratti, studenti, lezioni, report ore docenti, paghe, pagamenti, impostazioni scuola |
| `Segreteria` | Admin | Contratti, studenti, lezioni, report ore docenti (no paghe né dati fiscali docenti) |
| `Docente` | Docente | Calendario lezioni, materiali, compiti, propri studenti |
| `Studente` | Studente | Prossima lezione, contratto, materiali, compiti, quiz, rate |

> **Attenzione:** esistono ancora ruoli legacy lowercase (`super_admin`, `admin`, `docente`) non seedati e tre seeder ruoli paralleli. Vedere `PERMESSI.md` per l'audit completo.

---

## 4. Struttura cartelle rilevante

```
app/
  Console/Commands/          # Comandi artisan custom
    BackfillBillingProfiles.php
    BonificaConsumiCommand.php
    FixFutureLessonsCounts.php
    NotifyFollowupLeads.php
    NotifyOverdueInstallments.php
    RegenerateAllLessons.php
    SendScheduledStudentNotifications.php
  Filament/
    Pages/                   # Pagine custom (CRM, Reports, Operazioni, Impostazioni)
      Reports/               # PaymentsReport, AnomalyReport, StudentHoursReport, TeacherHoursReport, TeacherPayReport
    Resources/               # Risorse Filament: Contract, Student, Lesson, Course, CoursePurchase, Lead, ecc.
    Studente/Pages/          # Pannello studente: Dashboard, Calendario, Contratto, Compiti, Materiali, Quiz, Rate
    Teacher/Pages/           # Pannello docente: TeacherCalendar, MieiStudenti
    Teacher/Resources/       # TeacherHomework, TeacherMaterial
    Widgets/                 # ContractStatus, CrmStats, LessonCalendar, LessonsToday, ReportLinks, SchoolBrand, SystemStatus
  Http/Controllers/
    CheckoutController.php   # Catalogo + checkout pubblico
    WebhookController.php    # Webhook Stripe + PayPal
    GoogleOAuthController.php
    PublicController.php     # Home, /iscriviti, /privacy
    ContractDocumentController.php
    StudentContractPrintController.php
    Reports/TeacherHoursPdfController.php
  Models/                    # 28 modelli Eloquent (Contract, Lesson, Student, ecc.)
  Services/
    ContractService.php      # Generazione contratti e rate
    EmailTemplateService.php # Template email DB con layout responsive
    LessonGeneratorService.php
    LessonRecoveryService.php
    PaymentService.php       # Stripe + PayPal (Http facade) + Bonifico
    GoogleCalendarService.php
    GoogleMeetService.php
    RicevutaRataService.php
  Providers/Filament/        # 4 panel provider
database/
  migrations/                # Tutte le migration (l'ultima: 2026_05_04_200006_add_course_fk_to_contracts_table.php)
resources/views/
  emails/                    # Template email branded
  filament/                  # Blade views per i widget custom
  checkout/                  # Catalogo, show, bonifico, grazie, errore
routes/
  web.php                    # Route pubbliche con throttle su /iscriviti (5/min) e /checkout (3/min)
  console.php                # ATTENZIONE: Schedule Laravel - vedere note nella sezione 6
```

---

## 5. Pagamenti online (Stripe / PayPal / Bonifico)

### Architettura
- `/corsi` -> catalogo pubblico (solo corsi con `is_public=true` e `is_active=true`)
- `/corsi/{course}` -> form di checkout (dati fatturazione + scelta metodo)
- **Stripe Checkout hosted** (redirect) - libreria `stripe/stripe-php`, webhook firmato HMAC
- **PayPal Orders API** (redirect) - via `Http::withBasicAuth()` (Laravel HTTP client)
- **Bonifico** - riferimento univoco `BNF-YYYYMMDD-00001`, IBAN mostrato all'utente
- Su pagamento confermato: crea `Student` (se non esiste) + `Contract` (status `pending`) + invia email

### Setup operativo (vedere `PAGAMENTI_SETUP.md`)
1. `composer require stripe/stripe-php`
2. `php artisan migrate`
3. Aggiungere al `.env`: `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, `PAYPAL_CLIENT_ID`, `PAYPAL_SECRET`, `PAYPAL_BASE_URL`, `BANK_IBAN`, `BANK_INTESTATARIO`
4. Stripe Dashboard -> Webhooks -> endpoint `https://tuodominio.it/webhook/stripe` (eventi `checkout.session.completed`, `checkout.session.expired`)
5. PayPal Developer -> Webhooks -> endpoint `https://tuodominio.it/webhook/paypal` (evento `PAYMENT.CAPTURE.COMPLETED`)
6. In Admin -> Corsi: attivare `is_public` sui corsi da pubblicare
7. Compilare `BANK_IBAN` e `BANK_INTESTATARIO` nelle Impostazioni Scuola

---

## 6. Cron, code, scheduler

### Cron cPanel (OBBLIGATORIO in produzione)

```
* * * * * /usr/bin/php /home/aeacenter/scuole_app/artisan schedule:run >> /dev/null 2>&1
```

Senza questo cron NON funzionano: backup notturno, promemoria rate, follow-up CRM, monitor backup, notifiche scadute.

### ATTENZIONE: Stato attuale del scheduler

Le entry `Schedule::command(...)` previste (backup giornaliero, promemoria rate alle 09:00, CRM lead alle 08:00, monitor backup alle 08:30, notifica studenti programmate, fix conteggi lezioni) **al momento NON sono presenti** in `routes/console.php` (file truncato a 10 righe). Devono essere ripristinate prima del go-live.

Schedule che dovrebbero esserci:
```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('backup:clean')->daily()->at('01:30');
Schedule::command('backup:run --only-db')->daily()->at('02:00');
Schedule::command('backup:run')->weekly()->sundays()->at('03:00');
Schedule::command('backup:monitor')->daily()->at('08:30');
Schedule::command('leads:notify-followup')->dailyAt('08:00');
Schedule::command('installments:notify-overdue')->dailyAt('09:00');
Schedule::command('school:send-student-notifications')->everyFiveMinutes();
Schedule::command('lessons:fix-future-counts')->dailyAt('03:30');
```

### Code (queue)

Sul server, supervisor (o equivalente cPanel) deve avere un worker attivo:

```
php artisan queue:work --tries=3 --timeout=60 --sleep=3
```

Su shared hosting senza supervisor, attivare almeno un cron ogni minuto:
```
* * * * * /usr/bin/php /home/aeacenter/scuole_app/artisan queue:work --stop-when-empty --tries=3 >> /dev/null 2>&1
```

---

## 7. Deploy su cPanel

### Procedura standard (uso quotidiano)

1. Lavora su un branch feature (`feat/...`)
2. PR su GitHub -> merge in `main` (assicurati di aver pushato TUTTI i commit prima di mergiare)
3. cPanel -> **Git Version Control** -> `scuole-live` -> **Manage** -> tab **Pull or Deploy**
4. Click **Update from Remote** (tira i commit da GitHub)
5. Ricarica pagina (F5) -> click **Deploy HEAD Commit**
6. Il `.cpanel.yml` esegue: rsync codice in `scuole_app`, rsync `public/` in `public_html`, patch `index.php`, ricrea symlink `storage`, `chmod`, `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`
7. Log deploy: `/home/aeacenter/scuole_app/storage/logs/deploy.log`

### ATTENZIONE: Stato attuale `.cpanel.yml`

Il file termina troncato sul comando `migrate --force` (riga 118). Mancano: `php artisan optimize`, `view:cache`, `route:cache`, `config:cache`, `filament:optimize`, `queue:restart`. Da ripristinare prima del go-live.

### Gotcha noti
- **`.cpanel.yml` untracked sul server blocca il pull.** Cancellarlo manualmente da File Manager (Show Hidden Files) in `/home/aeacenter/repositories/scuole-live/`.
- **Bottone "Deploy HEAD Commit" grigio.** Verificare: `.cpanel.yml` presente nella branch + working tree pulito sul server.
- **Asset Vite (`public/build/`) sono in `.gitignore`** -> soluzione: `npm run build` in locale e committare la cartella prima del push.
- **Lo hosting non ha npm/node** - non si può buildare lì.

---

## 8. Variabili `.env` critiche

> **Attenzione:** il file `.env.example` attuale è truncato a 34 righe. Il `.env` di produzione deve essere compilato a partire da quanto sotto.

```dotenv
# App
APP_NAME="ScuoleLive"
APP_ENV=production
APP_KEY=                            # php artisan key:generate
APP_DEBUG=false
APP_URL=https://tuodominio.it
APP_LOCALE=it
APP_FALLBACK_LOCALE=it
APP_TIMEZONE=Europe/Rome
BCRYPT_ROUNDS=12

# Logging
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=error

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aealingue_scuole
DB_USERNAME=
DB_PASSWORD=

# Sessione (HTTPS in prod!)
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# Cache & Queue
CACHE_STORE=database
QUEUE_CONNECTION=database
BROADCAST_CONNECTION=log

# Mail (SMTP reale in prod)
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="${APP_NAME}"

# Stripe (live)
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

# PayPal (live)
PAYPAL_CLIENT_ID=
PAYPAL_SECRET=
PAYPAL_BASE_URL=https://api-m.paypal.com
PAYPAL_WEBHOOK_ID=

# Bonifico
BANK_IBAN=
BANK_INTESTATARIO=

# Google Calendar / Meet
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT=https://tuodominio.it/google/callback
GOOGLE_CALENDAR_ID=
GOOGLE_SERVICE_ACCOUNT_JSON=storage/app/google/service-account.json

# Sentry / monitoraggio
SENTRY_LARAVEL_DSN=
SENTRY_TRACES_SAMPLE_RATE=0.1

# Backup
BACKUP_NOTIFICATION_EMAIL=
BACKUP_ARCHIVE_PASSWORD=
```

---

## 9. Comandi artisan utili

```bash
# Operativi
php artisan backup:run                      # Backup full immediato
php artisan backup:run --only-db            # Solo database
php artisan backup:list                     # Stato dei backup
php artisan backup:clean                    # Pulizia retention

php artisan installments:notify-overdue --dry-run    # Test promemoria rate
php artisan leads:notify-followup --dry-run          # Test follow-up CRM
php artisan school:send-student-notifications        # Invio notifiche studenti
php artisan lessons:fix-future-counts --dry-run      # Riconciliazione conteggi

# Manutenzione
php artisan activitylog:clean                # Pulizia log attività vecchi
php artisan queue:retry all                  # Riprova tutti i failed
php artisan queue:flush                      # Svuota i failed
php artisan queue:restart                    # Restart graceful dei worker
php artisan optimize:clear                   # Clear cache config/route/view

# Cache produzione
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize

# DB
php artisan migrate --force
php artisan db:seed --force
```

---

## 10. FAQ rapide (vedere il manuale `MANUALE_SCUOLELIVE.docx` per la versione estesa)

**Q: Ho fatto un deploy e non vedo le modifiche.**
Esegui da Terminal cPanel: `cd /home/aeacenter/scuole_app && php artisan optimize:clear && php artisan optimize && php artisan filament:optimize`. Se il problema persiste, verifica `tail -200 storage/logs/deploy.log`.

**Q: Il bottone "Deploy HEAD Commit" è grigio.**
Verifica: il `.cpanel.yml` è presente nella branch su GitHub? Working tree pulito sul server? Cancella manualmente `/home/aeacenter/repositories/scuole-live/.cpanel.yml` e ritenta.

**Q: Stripe non conferma il pagamento.**
1) Verifica `STRIPE_WEBHOOK_SECRET` nel `.env`. 2) Stripe Dashboard -> Webhooks -> controlla i tentativi di consegna. 3) Log: `tail -200 storage/logs/laravel-$(date +%Y-%m-%d).log | grep -i stripe`.

**Q: PayPal non conferma il pagamento.**
1) Controlla `PAYPAL_BASE_URL` (live vs sandbox). 2) `PAYPAL_WEBHOOK_ID` deve combaciare con quello registrato su PayPal. 3) In production, se `PAYPAL_WEBHOOK_ID` è vuoto, il webhook viene **bloccato per sicurezza**.

**Q: Lo studente non riceve l'email di conferma.**
1) Verifica MAIL_* nel `.env`. 2) `php artisan queue:work --once` per processare manualmente. 3) Verifica disiscrizioni: `Admin -> Studenti disiscritti`. 4) Controlla SPF/DKIM/DMARC del dominio mittente.

**Q: Il backup non parte.**
1) Verifica che il cron `schedule:run` sia attivo. 2) `php artisan backup:run` manuale. 3) `php artisan backup:list`. 4) Controlla `storage/app/Laravel/` (o nome configurato in `BACKUP_NAME`).

**Q: Una lezione si è duplicata.**
È prevenuta dal vincolo unique `[contract_student_id, starts_at]`. Se è successo, ci sono due `contract_student_id` diversi: verifica i contratti collegati alla stessa coppia studente/orario.

**Q: Google Meet link non è generato.**
1) `Admin -> Impostazioni -> Google` mostra lo stato del token. 2) Se "scaduto", riconnettere. 3) Controlla che `GOOGLE_CALENDAR_ID` sia compilato. 4) Logs: `grep -i google storage/logs/laravel-*.log`.

**Q: Una migration si è truncata in commit.**
Verificare `wc -l database/migrations/<file>.php` localmente prima del push. Su Linux/cPanel se serve correggere a mano: `vi` o `nano` la migration, poi `php artisan migrate --force`.

**Q: Studente vuole disiscriversi dalle email.**
Sistema GDPR-compliant: ogni email contiene un footer con link autocontenuto (token HMAC). Cliccando, viene aggiunto a `student_unsubscribes`. La segreteria può riabilitarlo da `Admin -> Studenti disiscritti`.

---

## 11. Stato pre-lancio (sintesi al 04/05/2026)

### Completati (selezione)
- SoftDeletes su Contract, Lesson, Student + cascade
- Password reset multi-panel
- Backup spatie con disco `local-backups`
- Ruoli granulari Amministrazione / Segreteria
- Promemoria rate scadute configurabili
- Widget prossima lezione pannello studente
- Validazioni CF / P.IVA / IBAN come Rules dedicate
- PaymentService PayPal: cURL raw -> `Http::withBasicAuth()` con error handling
- Throttle sui form pubblici (`/iscriviti` 5/min, `/checkout` 3/min)
- Indici performance su `lessons` (4 indici)
- `composer.json`: `optimize-autoloader: true`
- Carbon::setLocale('it') in AppServiceProvider
- GoogleOAuthController: verifica state CSRF
- EnrollmentResource: deprecato con docblock
- Sistema GDPR: `student_unsubscribes` + UnsubscribeController + token HMAC
- Job retry per email conferma acquisto (`SendPurchaseConfirmationJob`, tries=3)

### BLOCCANTI residui (da fare prima del go-live)
1. Ripristinare `routes/console.php` con tutte le entry `Schedule::` (file attualmente truncato a 10 righe)
2. Ripristinare `.cpanel.yml` con `php artisan optimize` + `view:cache` + `queue:restart` (file truncato a riga 118)
3. Ripristinare `.env.example` completo (truncato a 34 righe) e creare `.env` di produzione
4. Completare `SystemStatusWidget::backupStatus()` (funzione truncata)
5. Configurare cron `schedule:run` su cPanel
6. Compilare `BANK_IBAN` + dati scuola in Impostazioni
7. Configurare webhook Stripe e PayPal sul dominio reale
8. SPF / DKIM / DMARC sul dominio mittente
9. Test end-to-end completo: registrazione corso -> checkout -> email -> contratto -> lezioni
10. Backup manuale di verifica + restore test

### Importanti (entro 7 giorni dal lancio)
- Decidere sorte di EnrollmentResource (deprecato ora, ma resource ancora caricato)
- Email notifica studente per recupero automatico lezione (template `lesson_recovery_email_template` già seedato - verificare invio)
- Verificare accessi `PaymentsReport`, `AnomalyReport`, `StudentHoursReport` per Amministrazione
- Sentry DSN configurato e ricezione errori test
- Audit permessi Shield: 5 incongruenze documentate in `PERMESSI.md`

### Post-lancio (entro 30 giorni)
- Rimozione dei `Schema::hasColumn()` runtime nei modelli (ora 9 occorrenze residue, già ridotti)
- Soft delete su `CoursePurchase` (migration già presente: `2026_05_04_200005`)
- Fix Meet URL su reschedule lezione
- Pulizia `google_event_id` su soft-delete
- Retry user-friendly su errore PayPal `createOrder`
- Aggiornare `manuale_scuolelive*.docx` legacy -> mantenere solo `MANUALE_SCUOLELIVE.docx`

---

## Documenti correlati

| File | Scopo |
|------|-------|
| `MANUALE_SCUOLELIVE.docx` | Manuale operativo dettagliato (utenti finali + admin) |
| `REPORT_STATO_PROGETTO.md` | Report tecnico stato progetto pre-lancio |
| `REPORT_STATO_PROGETTO.docx` | Versione Word del report (per condivisione) |
| `PAGAMENTI_SETUP.md` | Setup tecnico pagamenti |
| `DEPLOY.md` | Guida operativa deploy completo (se presente) |
| `PERMESSI.md` | Audit della matrice permessi |
| `Analisi_scuoleLive_29-04-2026.docx` | Analisi tecnica originale pre-hardening |

---

## Contatti tecnici

- **Hosting cPanel:** `stella.svrsh.com:2083` (account `aeacenter`)
- **GitHub:** `https://github.com/lauruccia/scuole-live`
- **Email gestionale:** `gruppokosmos00@gmail.com`
