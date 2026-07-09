# Migrazione SEO — dal vecchio sito WordPress al nuovo sito Laravel

**Data analisi:** 9 luglio 2026
**Scenario deciso:** il nuovo sito verrà servito su **aealanguagecenter.it** (dominio storico, mantiene tutta l'autorità SEO). aeacenter.it farà redirect 301 verso di esso.

---

## 1. Fotografia del vecchio sito (aealanguagecenter.it — WordPress)

Piattaforma: WordPress + WooCommerce + Yoast SEO + Slider Revolution.

**Inventario URL indicizzabili** (da sitemap Yoast, rilevata il 09/07/2026):

| Sezione | Quante URL | Esempi |
|---|---|---|
| Pagine | 35 | /la-scuola/, /i-corsi/, /corsi-per-le-aziende/, /test-di-inglese/ |
| Articoli blog | 10 | /esami-trinity-college-2026/, /certificazioni-lingua-inglese-riconosciute-dal-mim/ |
| Corsi (custom post type) | 9 | /courses/corso-di-inglese/, /courses/corso-di-tedesco/ |
| Prodotti WooCommerce | 12 | /prodotto/esami-trinity/, /prodotto/lezione-di-prova/ |
| Tassonomie/archivi | ~10 sitemap | categorie, tag, insegnanti, livelli corso, autori |

**Qualità SEO del vecchio sito — debolezze rilevate:**

- Title homepage duplicato e generico: *"A&A Language Center - A&A Language Center | Scuola di lingue, Roma."*
- H1 homepage senza keyword: *"Clicca qui e fai il tuo test di lingua"*
- Meta description non orientata alla ricerca (parla degli insegnanti, non di "corsi di lingue Roma")
- Molte URL "spazzatura" in sitemap: carrello, checkout, il-mio-account, learning-compare (pagine transazionali che non dovrebbero essere indicizzate)
- Contenuti blog datati (post del 2022 sul "riaperto dopo Covid")
- Nessuna landing per keyword locali ("corsi inglese Roma", "italiano per stranieri Roma")

**Punti di forza da NON perdere:**

- **Anzianità del dominio** e storico di indicizzazione (backlink, trust accumulato dal 2002)
- Pagine "test di livello" (/test-di-inglese/, /test-di-francese/, ecc.) che intercettano ricerche informazionali tipo "test di inglese online"
- Post recenti su Trinity 2026 e certificazioni MIM (aggiornati a gennaio 2026, probabilmente portano traffico)

## 2. Fotografia del nuovo sito (oggi su aeacenter.it — Laravel)

**Qualità SEO — molto superiore:**

- Title ottimizzato: *"Scuola di Lingue a Roma | A&A Language Center San Paolo"*
- Meta description 155 caratteri orientata alle keyword, canonical corretto
- Open Graph completo (1200×630, locale it_IT + en_US), Twitter Card, geo meta (Roma San Paolo, coordinate)
- Schema.org JSON-LD nel layout (Organization + LanguageSchool + WebSite), FAQ schema, Course schema sul dettaglio corso
- 3 landing SEO locali: /corsi-inglese-roma, /corsi-italiano-stranieri-roma, /corsi-aziendali-roma
- Sitemap dinamica /sitemap.xml (statiche + landing + corsi + news dal DB, cache 6h)
- robots.txt pulito: indicizza solo le pagine pubbliche, blocca aree riservate/checkout/webhook — e **punta già a https://aealanguagecenter.it/sitemap.xml** (coerente con lo scenario finale)
- Modulo News per contenuti freschi (implementato 08/07/2026)

**Debolezze/rischi del nuovo sito:**

1. **URL corsi numeriche** (/corsi/12 invece di /corsi/corso-di-inglese-b2): funzionano ma sprecano keyword. Miglioria futura, non bloccante.
2. **Doppia indicizzazione temporanea**: aeacenter.it oggi è live e indicizzabile → finché esistono due siti, Google può indicizzare il nuovo dominio "provvisorio". Va risolto allo switch con redirect 301 a livello di host (vedi Fase 2).
3. Mancano ancora (dal check del 18/05): og-default.jpg reale, favicon/apple-touch-icon, GA4/GTM/verifiche in .env di produzione.
4. Non esiste una pagina "test di livello" equivalente a quelle vecchie.
5. Il blog nuovo (/news) parte vuoto: i contenuti Trinity/MIM del vecchio blog vanno ricreati.

## 3. Confronto sintetico

| Aspetto | Vecchio (WP) | Nuovo (Laravel) |
|---|---|---|
| Title/meta | Deboli, duplicati | Ottimizzati per keyword locali |
| Dati strutturati | Assenti/minimi | Organization, LanguageSchool, FAQ, Course |
| Landing keyword locali | Assenti | 3 landing dedicate |
| Sitemap | Yoast (include URL spazzatura) | Dinamica, solo pagine utili |
| Contenuti freschi | Blog datato | Modulo News (da popolare) |
| Autorità dominio | ✅ Storico dal 2002 | Zero (dominio nuovo) |
| Pagine test di livello | ✅ Presenti | ❌ Mancanti |

**Conclusione:** il nuovo sito è tecnicamente molto più forte; l'unica cosa che ha il vecchio e il nuovo no è **l'autorità del dominio e lo storico** — che si conserva servendo il nuovo sito su aealanguagecenter.it con i redirect 301 giusti.

---

## 4. Piano di migrazione (senza perdere l'indicizzazione)

### Fase 0 — PRIMA dello switch (da fare subito)

1. **Google Search Console**: verifica la proprietà di *entrambi* i domini.
   - Meglio la proprietà "Dominio" (verifica via record DNS TXT dal pannello del registrar).
   - In alternativa per aealanguagecenter.it: metodo file HTML caricato via File Manager di cPanel del vecchio hosting, o meta tag (il nuovo layout supporta già `SEO_GOOGLE_SITE_VERIFICATION` in .env).
2. **Baseline traffico**: in GSC del vecchio dominio, esporta Rendimento → query e pagine degli ultimi 3 mesi. Servirà per verificare dopo lo switch che le pagine importanti non abbiano perso posizioni.
3. Compila in `.env` di produzione: `SEO_GA4_ID`, `SEO_GOOGLE_SITE_VERIFICATION` (+ GTM/Bing se disponibili).
4. Carica `og-default.jpg` (1200×630), `favicon.png`, `apple-touch-icon.png`.
5. **Deploya i redirect** (`routes/redirects.php`, già pronto nel repo): sono innocui anche prima dello switch, così al cambio DNS sono già attivi.

### Fase 1 — Lo switch del dominio

6. Sul cPanel del nuovo hosting (account aeacenter): **Domini → Crea nuovo dominio** → aggiungi `aealanguagecenter.it` come alias/dominio con document root `public_html` (lo stesso del sito nuovo).
7. Dal registrar di aealanguagecenter.it: punta il DNS (record A / nameserver) al server del nuovo hosting.
8. In cPanel esegui **SSL/TLS Status → Run AutoSSL** per emettere il certificato HTTPS di aealanguagecenter.it (senza, i visitatori vedranno errori di certificato).
9. Aggiorna `.env` di produzione: `APP_URL=https://aealanguagecenter.it`, poi rideploya (il deploy esegue `php artisan optimize`, che rigenera la cache config — senza questo la sitemap continuerebbe a generare URL col dominio vecchio). Svuota anche la cache della sitemap (cache generale: il deploy con optimize è sufficiente se la cache è file-based; in dubbio, dal pannello Filament o attendere le 6h di TTL).

### Fase 2 — Canonicalizzazione host (subito dopo il cambio DNS)

10. Aggiungi in cima a `public/.htaccess` (dentro `<IfModule mod_rewrite.c>`, subito dopo `RewriteEngine On`) queste righe — **solo quando il DNS di aealanguagecenter.it punta già al nuovo server**, altrimenti aeacenter.it redirigerebbe verso un dominio che mostra ancora il vecchio sito:

```apache
# Canonical host: tutto su https://aealanguagecenter.it
RewriteCond %{HTTP_HOST} !^aealanguagecenter\.it$ [NC]
RewriteRule ^(.*)$ https://aealanguagecenter.it/$1 [R=301,L]
```

Questa regola con un colpo solo: aeacenter.it → aealanguagecenter.it, www → senza www, http → https. Tutte le URL indicizzate di aeacenter.it in questi mesi passano il ranking al dominio definitivo.

### Fase 3 — Subito dopo lo switch (stesso giorno)

11. **Test redirect** — verifica a campione che rispondano 301 verso la pagina giusta:
    - /i-corsi/ → /corsi
    - /corsi-per-le-aziende/ → /corsi-aziendali-roma
    - /courses/corso-di-inglese/ → /corsi-inglese-roma
    - /prodotto/esami-trinity/ → /corsi
    - /privacy-policy/ → /privacy
    - https://aeacenter.it/la-scuola → https://aealanguagecenter.it/la-scuola
    - https://www.aealanguagecenter.it/ → https://aealanguagecenter.it/
    (strumento comodo: httpstatus.io, incolli la lista e vedi i codici)
12. In GSC (proprietà aealanguagecenter.it): **Sitemap → invia** `https://aealanguagecenter.it/sitemap.xml`. Rimuovi/ignora la vecchia sitemap_index.xml di Yoast.
13. **Controllo URL** in GSC su homepage + 3 landing + /corsi → "Richiedi indicizzazione".
14. NON usare lo strumento "Cambio di indirizzo" di GSC: non serve, il dominio principale resta lo stesso. (Facoltativo: usarlo sulla proprietà di aeacenter.it indicando aealanguagecenter.it come destinazione, se aeacenter.it risulta già indicizzato.)

### Fase 4 — Le settimane successive

15. **Monitoraggio GSC** (settimanale per 4-6 settimane): Indicizzazione → Pagine → guarda i 404 ("Non trovata"): ogni 404 con traffico va aggiunto a `routes/redirects.php`. Confronta il Rendimento con la baseline esportata in Fase 0.
16. **Ricrea i contenuti che portavano traffico** come news del nuovo sito (poi aggiorna il redirect in `routes/redirects.php` dalla generica /news allo slug reale):
    - Esami Trinity College 2026
    - Certificazioni di inglese riconosciute dal MIM
    - Bonus cultura / carta del merito
17. **Valuta una pagina "Test di livello"**: le 5 pagine test del vecchio sito intercettavano ricerche tipo "test di inglese"; oggi redirigono su /iscriviti. Una pagina dedicata (anche solo un form di richiesta test gratuito) recupererebbe quel traffico.
18. **Google Business Profile**: aggiorna il sito web sul profilo, verifica NAP (nome, indirizzo, telefono +39 06 5743734) coerente col sito.
19. Se possibile, chiedi ai partner principali (Trinity, Confcommercio, ecc.) di aggiornare eventuali link — i 301 li coprono comunque.

### Cosa NON fare

- ❌ Non spegnere il vecchio hosting prima che il DNS punti al nuovo e i redirect siano verificati.
- ❌ Non lasciare aeacenter.it senza redirect: due siti identici su due domini = contenuti duplicati.
- ❌ Non usare redirect 302 (temporanei): solo 301.
- ❌ Non far redirigere tutto alla homepage: Google tratta i redirect di massa verso la home come soft-404. Per questo esiste la mappa pagina-per-pagina in `routes/redirects.php`.

---

## 5. File già pronti nel repo

| File | Cosa fa |
|---|---|
| `routes/redirects.php` | **Nuovo** — mappa 301 completa vecchie URL WP → nuove pagine (pagine, corsi, shop, blog, tassonomie, wildcard) |
| `routes/web.php` | Modificato — include `redirects.php` in fondo |
| `public/robots.txt` | Già corretto (Sitemap → aealanguagecenter.it), nessuna modifica necessaria |

Lo snippet `.htaccess` della Fase 2 va invece applicato **a mano al momento dello switch** (non è committato apposta: attivarlo prima romperebbe aeacenter.it).

## 6. Tempistiche attese

Con redirect 301 pagina-per-pagina sullo stesso dominio storico, la transizione per Google è quasi indolore: ricrawl completo in 1-4 settimane, possibili oscillazioni di posizione minime nelle prime 2 settimane, poi il profilo tecnico migliore del nuovo sito (schema, landing, velocità) dovrebbe portare a un **miglioramento** rispetto a oggi. Il monitoraggio della Fase 4 serve a intercettare subito eventuali 404 sfuggiti alla mappa.

---

## 7. Aggiornamento 09/07/2026 (pomeriggio) — recupero contenuti attuato

Implementati i punti 16 e 17 della Fase 4 (in anticipo sullo switch, così allo
switch sono già indicizzabili):

**Pagine test di livello** — ricreate sugli **stessi slug** del vecchio sito
(quindi niente redirect: la URL conserva direttamente il ranking):
- `/test-sul-livello-di-lingua` (hub) + `/test-di-inglese`, `/test-di-francese`,
  `/test-di-spagnolo`, `/test-di-italiano`
- Quiz interattivo client-side: 20 domande a scelta multipla per lingua
  (4 per fascia A1–C1, in `config/level_tests.php`), risultato CEFR indicativo
  immediato, CTA verso colloquio gratuito/iscrizione. Nessun dato inviato al server.
- Testi e meta editabili dal pannello **Contenuti sito → Test di livello**.
- Aggiunte a sitemap (priorità 0.8) e footer. I redirect test → /iscriviti sono
  stati rimossi; `/qsm_quiz/*` ora punta all'hub.

**News ricreate** (fonte: export WordPress del 09/07/2026, migration seed
`2026_07_09_000001_seed_initial_news_posts.php`, idempotente):
- `/news/esami-trinity-college-2026` (calendario GESE/ISE 2026 + sessione autunno)
- `/news/certificazioni-lingua-inglese-riconosciute-dal-mim` (Decreto 2813/2024)
- `/news/carta-cultura-giovani-e-carta-del-merito` (aggiornato: nati 2007,
  finestra 2026 chiusa il 30/06, spesa entro 31/12/2026, finestra 2027 per nati 2008)
- I redirect dei 3 vecchi slug ora puntano alle URL reali `/news/{slug}`
  (non più alla /news generica).

**Al deploy servono**: `php artisan migrate` (via run_migrate.php) + clear cache
config e view (config nuova `level_tests.php` + `site_contents.php` modificata).
