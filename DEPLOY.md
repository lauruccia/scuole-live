# ScuoleLive — Guida operativa al deploy

> Aggiornata 05/05/2026 — Stack: Laravel 12 / PHP 8.2 / Filament 3 / cPanel Aruba (account `aeacenter`)

---

## 1. Topologia server

| Cosa | Path |
|---|---|
| Pannello cPanel | https://stella.svrsh.com:2083 |
| Repo Git cPanel | `/home/aeacenter/repositories/scuole-live/` |
| Codice Laravel | `/home/aeacenter/scuole_app/` |
| Document root | `/home/aeacenter/public_html/` |
| GitHub | https://github.com/lauruccia/scuole-live |
| Branch deployata | `main` |
| DB MySQL | `aealingue_scuole` su `127.0.0.1:3306` |

**Importante:** la repository cPanel è separata dalla cartella applicativa. Il `.cpanel.yml` usa `rsync` per copiare i file e `sed` per riscrivere `public_html/index.php` con i path assoluti di `scuole_app`.

---

## 2. Procedura standard di deploy (uso quotidiano)

1. Lavora su un branch feature (`feat/...`).
2. Apri PR su GitHub verso `main`.
3. **Pusha tutti i commit prima di mergiare** (errore comune: PR mergiata senza commit recenti — il `.cpanel.yml` finisce per non essere visibile sul server).
4. Merge PR.
5. cPanel → **Git Version Control** → trova `scuole-live` → **Manage** → tab **Pull or Deploy**.
6. Click **Update from Remote** (tira il merge da GitHub).
7. Ricarica pagina (F5) → click **Deploy HEAD Commit**.
8. Il `.cpanel.yml` esegue automaticamente:
   - rsync codice in `scuole_app`, escludendo `.env`, node_modules, tests, docx, ecc.
   - rsync `public/` in `public_html`
   - patch `public_html/index.php` con `sed` (path assoluti)
   - ricrea symlink `storage`
   - chmod 755 / 775
   - `composer install --no-dev --optimize-autoloader`
   - `php artisan migrate --force`
   - `php artisan optimize:clear` + `optimize`
   - `php artisan view:cache`
   - `php artisan event:cache`
   - `php artisan filament:optimize`
   - `php artisan queue:restart`
9. **Log deploy:** `/home/aeacenter/scuole_app/storage/logs/deploy.log` (cerca `===== DEPLOY OK ===`).

---

## 3. Setup iniziale (prima volta sul server)

> Esegui questi step UNA VOLTA SOLA, da Terminal cPanel.

### 3.1 Clone repo cPanel
Già fatto via SSH deploy key. Repo già clonato in `/home/aeacenter/repositories/scuole-live/`.

### 3.2 Compilare `.env`
```bash
cp /home/aeacenter/scuole_app/.env.example /home/aeacenter/scuole_app/.env
nano /home/aeacenter/scuole_app/.env
# Compilare TUTTI i valori — vedere README.md sezione 8
```

### 3.3 Generare APP_KEY
```bash
cd /home/aeacenter/scuole_app
php artisan key:generate --force
```

### 3.4 Migrate + seed iniziali
```bash
php artisan migrate --force
php artisan db:seed --force         # se vuoi i dati di default (ruoli, template email, ecc.)
```

### 3.5 Storage link manuale
Se il `.cpanel.yml` non lo crea (verifica):
```bash
ls -la /home/aeacenter/public_html/storage   # deve essere simlink
```
Se non lo è:
```bash
ln -nfs /home/aeacenter/scuole_app/storage/app/public /home/aeacenter/public_html/storage
```

### 3.6 Configurare Cron Jobs (cPanel → Cron Jobs)

**Schedule (obbligatorio — ogni minuto):**
```
* * * * * /usr/local/bin/php /home/aeacenter/scuole_app/artisan schedule:run >> /dev/null 2>&1
```

**Worker code (raccomandato — ogni minuto, lavora finché c'è coda):**
```
* * * * * /usr/local/bin/php /home/aeacenter/scuole_app/artisan queue:work --stop-when-empty --tries=3 >> /dev/null 2>&1
```

> ⚠️ **Path PHP:** usare `/usr/local/bin/php` (CLI) e NON `/usr/bin/php` (CGI). Su Aruba/cPanel `/usr/bin/php` è la versione CGI: ignora gli argomenti e fa fallire silenziosamente il cron stampando l'help di artisan. È stata la causa #2 dell'incident del 2026-05-06.

### 3.7 Configurare i Webhook gateway

**Stripe** (Dashboard → Developers → Webhooks):
- URL: `https://tuodominio.it/webhook/stripe`
- Eventi: `checkout.session.completed`, `checkout.session.expired`
- Copiare il "Signing secret" → in `.env` come `STRIPE_WEBHOOK_SECRET`

**PayPal** (Developer Dashboard → app → Webhooks):
- URL: `https://tuodominio.it/webhook/paypal`
- Eventi: `PAYMENT.CAPTURE.COMPLETED`
- Copiare il "Webhook ID" → in `.env` come `PAYPAL_WEBHOOK_ID`
- ⚠️ In production senza questo ID, il webhook viene **bloccato** per sicurezza.

### 3.8 Configurare DNS Mailing (SPF/DKIM/DMARC)

Sul pannello DNS (Aruba/Cloudflare):

```
TXT  @           v=spf1 include:tuomaildomain.com ~all
TXT  default._domainkey  <chiave DKIM dal provider SMTP>
TXT  _dmarc      v=DMARC1; p=quarantine; rua=mailto:postmaster@tuodominio.it
```

### 3.9 Audit pre-go-live
```bash
cd /home/aeacenter/scuole_app
php artisan school:health-check --strict
```
Se restituisce exit code != 0, leggi i FAIL e risolvi prima del lancio.

---

## 4. Cosa fare a OGNI deploy con cambi schema

```bash
cd /home/aeacenter/scuole_app
php artisan migrate --force         # se non già nel .cpanel.yml
php artisan optimize:clear
php artisan optimize
php artisan filament:optimize
php artisan queue:restart
```

> Il `.cpanel.yml` esegue già questi step in coda al deploy. Se per qualche motivo (es. composer non in PATH) il deploy si interrompe a metà, ripeti manualmente.

---

## 5. Backup & restore

### 5.1 Backup manuale immediato
```bash
php artisan backup:run --only-db    # solo DB (veloce)
php artisan backup:run              # full (codice + storage + DB)
php artisan backup:list             # elenco backup esistenti
```

### 5.2 Posizione backup
`/home/aeacenter/scuole_app/storage/app/Laravel/` (o nome configurato in `BACKUP_NAME`)

### 5.3 Restore (procedura disaster recovery)
```bash
cd /tmp
unzip /home/aeacenter/scuole_app/storage/app/Laravel/2026-XX-XX-HH-MM-SS.zip -d restore_temp/
# 1. Database
mysql -u <user> -p aealingue_scuole < restore_temp/db-dumps/mysql-aealingue_scuole.sql
# 2. Storage
cp -r restore_temp/storage/app/public/* /home/aeacenter/scuole_app/storage/app/public/
# 3. Verifica
cd /home/aeacenter/scuole_app && php artisan migrate:status
```

> **Esegui un restore di prova almeno una volta al mese** su DB di staging per essere sicuro che il backup sia funzionante.

---

## 6. Gotcha noti

### "Bottone Deploy HEAD Commit grigio"
Verifica:
- `.cpanel.yml` presente nella branch `main` su GitHub.
- Working tree pulito sul server: cPanel → File Manager → Show Hidden Files → `/home/aeacenter/repositories/scuole-live/` → cancella eventuali file untracked (specialmente `.cpanel.yml` orfano).

### "Errore: untracked working tree files would be overwritten"
È il classico errore quando esiste un `.cpanel.yml` già committato sul server prima del primo merge che lo introduce. Soluzione: cancella manualmente quel file dal File Manager e ritenta `Update from Remote`.

### Asset Vite (CSS/JS) non caricano
La cartella `public/build/` deve essere committata (è stata rimossa dal `.gitignore`). Lo hosting NON ha node/npm. Workflow:
```bash
# In locale, prima del push
npm run build
git add public/build/
git commit -m "build: rebuild assets"
git push
```

### "composer non disponibile in deploy"
Se il `.cpanel.yml` logga questo messaggio, lancia manualmente:
```bash
cd /home/aeacenter/scuole_app
composer install --no-dev --optimize-autoloader
```

### "migrate fallito"
Verifica:
- credenziali DB nel `.env`
- migration sintatticamente valide (`php artisan migrate:status`)
- chiusure migration `down()` definite

### Cache stale dopo deploy
```bash
php artisan optimize:clear
php artisan optimize
php artisan filament:optimize
```

### Worker code non gira
- Verifica cron `queue:work` attivo.
- Verifica che la tabella `jobs` esista (`php artisan migrate:status`).
- Restart manuale: `php artisan queue:restart` (richiede worker già attivi che sentano il segnale).

---

## 7. Permessi file e cartelle

```bash
chmod -R 755 /home/aeacenter/scuole_app
chmod -R 775 /home/aeacenter/scuole_app/storage
chmod -R 775 /home/aeacenter/scuole_app/bootstrap/cache
```

---

## 8. Rollback rapido

In caso di deploy con bug critico:
1. Su GitHub: revert commit incriminato.
2. cPanel → Git Version Control → Update from Remote → Deploy HEAD Commit.
3. Da Terminal cPanel:
   ```bash
   cd /home/aeacenter/scuole_app
   php artisan migrate:rollback --step=N    # se la migration è rollbackabile
   php artisan optimize:clear && php artisan optimize
   ```
4. Se serve un restore DB, vedere § 5.3.

---

## 9. Checklist di go-live

- [ ] `.env` produzione compilato (vedere README.md § 8)
- [ ] `php artisan school:health-check --strict` → exit 0
- [ ] Cron `schedule:run` attivo
- [ ] Cron `queue:work` attivo
- [ ] Webhook Stripe configurato (live)
- [ ] Webhook PayPal configurato + `PAYPAL_WEBHOOK_ID` nel `.env`
- [ ] SPF / DKIM / DMARC sul dominio mittente
- [ ] HTTPS forzato (redirect HTTP → HTTPS)
- [ ] `php artisan backup:run` manuale OK
- [ ] Restore di prova OK su staging
- [ ] Test end-to-end Stripe (carta test → live)
- [ ] Test end-to-end PayPal (sandbox → live)
- [ ] Test invio email a 3 indirizzi diversi (Gmail, Outlook, mail aziendale) — non in spam
- [ ] Compilazione IBAN + dati scuola in Admin → Impostazioni Scuola
- [ ] Sentry DSN configurato e ricezione errori test verificata
