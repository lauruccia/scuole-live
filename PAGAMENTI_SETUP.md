# Setup Pagamenti Online

## 1. Installa dipendenze

```bash
composer require stripe/stripe-php
```

## 2. Esegui le migrazioni

```bash
php artisan migrate
```

Crea: `course_purchases` e aggiunge `is_active`, `is_public`, `short_description`, `level`, `image_path` a `courses`.

## 3. Aggiungi le variabili al `.env`

```env
# Stripe (ottieni le chiavi da https://dashboard.stripe.com/apikeys)
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

# PayPal (ottieni da https://developer.paypal.com/dashboard/applications)
PAYPAL_CLIENT_ID=...
PAYPAL_SECRET=...
PAYPAL_BASE_URL=https://api-m.paypal.com   # usa sandbox per test: https://api-m.sandbox.paypal.com

# Bonifico bancario
BANK_IBAN=IT00 0000 0000 0000 0000 0000 0
BANK_INTESTATARIO="A&A Language Center Srl"
```

## 4. Configura webhook Stripe

Nel dashboard Stripe → Webhooks → aggiungi endpoint:
- URL: `https://tuodominio.it/webhook/stripe`
- Evento: `checkout.session.completed`, `checkout.session.expired`
- Copia il webhook secret e inseriscilo in `STRIPE_WEBHOOK_SECRET`

## 5. Configura webhook PayPal (opzionale, per sicurezza extra)

Nel dashboard PayPal Developer → Webhooks:
- URL: `https://tuodominio.it/webhook/paypal`
- Evento: `PAYMENT.CAPTURE.COMPLETED`

## 6. Pubblica i corsi nel catalogo

Vai in **Admin → Corsi**, modifica un corso e attiva:
- ✅ "Corso attivo"
- ✅ "Pubblica nel catalogo online"

Il corso apparirà su `/corsi`.

## 7. Test

- Visita `/corsi` per vedere il catalogo
- Clicca "Scegli" su un corso per il checkout
- Usa le carte di test Stripe: `4242 4242 4242 4242`

## Flusso completo

1. Studente visita `/corsi` → sceglie un corso
2. Compila dati fatturazione + sceglie metodo pagamento
3. Viene reindirizzato al gateway (Stripe / PayPal) o alla pagina istruzioni bonifico
4. Dopo pagamento confermato → viene creato automaticamente:
   - Uno `Student` (o trovato per email)
   - Un `Contract` con stato `pending`
   - Collegamento studente ↔ contratto
   - Email di conferma all'acquirente
5. La segreteria vede l'acquisto in **Admin → Pagamenti → Acquisti online**
6. Può confermare manualmente i bonifici con il pulsante "Conferma pagamento"
7. Completa il contratto aggiungendo orari, docente, ecc.
