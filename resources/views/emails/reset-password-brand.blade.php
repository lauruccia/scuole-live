<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Reimposta la password</title>
</head>
<body style="margin:0;background:#f6f7fb;font-family:Arial,Helvetica,sans-serif;color:#111;">
    <div style="max-width:640px;margin:0 auto;padding:28px 16px;">
        <!-- Header -->
        <div style="text-align:center;margin-bottom:18px;">
            <img src="{{ rtrim(config('app.url'), '/') . '/images/logo-scuola.png' }}"
     alt="A&A Language Center"
     width="220"
     style="height:64px;max-width:220px;display:block;margin:0 auto;border:0;outline:none;text-decoration:none;">
        </div>

        <!-- Card -->
        <div style="background:#ffffff;border-radius:14px;box-shadow:0 10px 28px rgba(16,24,40,.08);overflow:hidden;">
            <div style="padding:28px 26px;">
                <h1 style="margin:0 0 10px;font-size:22px;line-height:1.2;">
                    Reimposta la tua password
                </h1>

                <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#333;">
                    Ciao{{ isset($notifiable?->name) && $notifiable->name ? ' ' . e($notifiable->name) : '' }},
                    abbiamo ricevuto una richiesta di reimpostazione password per il tuo account.
                </p>

                <p style="margin:0 0 18px;font-size:15px;line-height:1.6;color:#333;">
                    Clicca sul pulsante qui sotto per scegliere una nuova password.
                </p>

                <!-- Button -->
                <div style="text-align:center;margin:22px 0 18px;">
                    <a href="{{ $url }}"
                       style="display:inline-block;background:#d97706;color:#fff;text-decoration:none;
                              padding:12px 18px;border-radius:10px;font-weight:700;font-size:14px;">
                        Reimposta password
                    </a>
                </div>

                <p style="margin:0 0 10px;font-size:13px;line-height:1.6;color:#555;">
                    Questo link scadrà tra <strong>{{ (int) $expire }}</strong> minuti.
                </p>

                <p style="margin:0 0 18px;font-size:13px;line-height:1.6;color:#555;">
                    Se non hai richiesto tu questa operazione, puoi ignorare tranquillamente questa email.
                </p>

                <!-- Fallback URL -->
                <div style="padding:14px 14px;border-radius:10px;background:#f6f7fb;font-size:12px;line-height:1.5;color:#444;">
                    Se il pulsante non funziona, copia e incolla questo link nel browser:<br>
                    <a href="{{ $url }}" style="color:#1d4ed8;word-break:break-all;">{{ $url }}</a>
                </div>
            </div>

            <!-- Footer -->
            <div style="background:#0f172a;color:#cbd5e1;padding:14px 18px;font-size:12px;text-align:center;">
                © {{ date('Y') }} A&amp;A Language Center — Questo è un messaggio automatico.
            </div>
        </div>

        <div style="text-align:center;margin-top:14px;font-size:11px;color:#6b7280;">
            Inviato da {{ config('mail.from.address') }}
        </div>
    </div>
</body>
</html>
