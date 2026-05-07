# Roadmap migliorie consigliate — USO INTERNO

**⚠️ Documento riservato — non distribuire al cliente**

Funzionalità non ancora implementate, in ordine di priorità.

---

## 1. Riconciliazione automatica bonifici (alta)

**Cosa fa:** integrazione con API banca (PSD2) → il sistema verifica ogni notte i bonifici in entrata e marca automaticamente come `paid` quelli con causale corrispondente a un `bank_transfer_ref`.

**Beneficio:** elimina il lavoro manuale di "controllo bonifico → marca pagato → invia email conferma". Riduce ritardi nell'attivazione del corso.

**Costo stimato:** 2-3 giornate + costi banca per accesso API.

---

## 2. Two-Factor Authentication per admin (alta)

**Cosa fa:** chi accede a `/admin` deve inserire, oltre alla password, un codice OTP da Google Authenticator o SMS.

**Beneficio:** riduce drasticamente il rischio se la password admin viene compromessa. Particolarmente importante per il pannello che vede dati sensibili di tutti gli studenti.

**Costo stimato:** 1 giornata. Pacchetto già pronto per Filament: `filament/2fa`.

---

## 3. Dashboard self-service avanzata per studenti (media)

**Cosa fa:** lo studente vede saldo ore con grafico, calendario con richiesta spostamento, storico pagamenti con download ricevute, materiali filtrabili, quiz e progressi.

**Beneficio:** riduce richieste alla segreteria del 30-40%, migliora retention.

**Costo stimato:** 3-4 giornate.

---

## 4. App mobile (PWA) (bassa-media)

**Cosa fa:** versione mobile-first del pannello studente, installabile come "app" sul telefono (Progressive Web App, no store).

**Beneficio:** notifiche push (reminder lezione tra 1h), accesso offline ai materiali.

**Costo stimato:** 1 settimana.

---

## 5. Sistema di referral / sconti (bassa)

**Cosa fa:** lo studente ottiene un codice univoco → chi lo usa ha sconto X% → lo studente ottiene Y€ di credito.

**Costo stimato:** 2-3 giornate.

---

## 6. Sondaggi e NPS (bassa)

**Cosa fa:** invia automaticamente alla fine di un corso un questionario di gradimento (1-10), feedback testuale, valutazione del docente.

**Beneficio:** dati per migliorare l'offerta, identificare docenti bisognosi di formazione.

**Costo stimato:** 1-2 giornate.

---

## 7. Integrazione fatturazione elettronica (media-alta)

**Cosa fa:** la ricevuta diventa una vera fattura elettronica B2B/B2C inviata via SDI (Aruba Fatturazione, Fatture in Cloud, ecc.).

**Beneficio:** elimina la fase manuale del commercialista.

**Costo stimato:** 3-5 giornate + costo provider (~10€/mese).

---

## 8. Integrazione contabilità (media)

**Cosa fa:** ogni rata pagata produce automaticamente una scrittura contabile esportabile per il commercialista.

**Costo stimato:** 2-3 giornate.

---

## 9. Blocco accesso studente per morosità (bassa)

**Cosa fa:** se uno studente ha rate scadute oltre N giorni, vede un banner e perde temporaneamente accesso a materiali/lezioni online.

**Costo stimato:** 1 giornata.

---

*Documento interno — aggiornato 2026-05-07*
