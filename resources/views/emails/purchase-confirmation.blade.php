<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Conferma iscrizione</title>
</head>
<body style="margin:0; padding:0; background:#f5f5f5; font-family: Arial, Helvetica, sans-serif; color:#222; line-height:1.7;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5; padding:30px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0"
                   style="width:600px; max-width:600px; background:#ffffff; border-radius:8px; overflow:hidden;">

                {{-- Header --}}
                <tr>
                    <td style="background:#1e3a5f; padding:28px 40px; text-align:center;">
                        <h1 style="margin:0; color:#ffffff; font-size:22px; letter-spacing:1px;">
                            A&amp;A Language Center
                        </h1>
                        <p style="margin:6px 0 0 0; color:#b0c8e8; font-size:13px;">
                            <a href="https://aealanguagecenter.it/" style="color:#b0c8e8; text-decoration:none;">
                                aealanguagecenter.it
                            </a>
                        </p>
                    </td>
                </tr>

                {{-- Corpo --}}
                <tr>
                    <td style="padding:36px 40px;">

                        <p style="margin:0 0 6px 0; font-size:22px;">✅</p>
                        <h2 style="margin:0 0 20px 0; font-size:20px; color:#1e3a5f;">
                            Iscrizione confermata!
                        </h2>

                        <p style="margin:0 0 16px 0; font-size:15px;">
                            Ciao <strong>{{ $purchase->buyer_name }}</strong>,<br>
                            il tuo acquisto è stato ricevuto con successo. Ecco il riepilogo:
                        </p>

                        {{-- Riepilogo acquisto --}}
                        <table width="100%" cellpadding="0" cellspacing="0"
                               style="background:#f0f5ff; border:1px solid #c3d4ef; border-radius:8px; margin:0 0 24px 0;">
                            <tr>
                                <td style="padding:20px;">
                                    <p style="margin:0 0 8px 0; font-size:14px;">
                                        <strong>Corso:</strong> {{ $purchase->course->name }}
                                    </p>
                                    @if($purchase->course->level)
                                    <p style="margin:0 0 8px 0; font-size:14px;">
                                        <strong>Livello:</strong> {{ $purchase->course->level }}
                                    </p>
                                    @endif
                                    <p style="margin:0 0 8px 0; font-size:14px;">
                                        <strong>Importo pagato:</strong> €{{ number_format($purchase->amount, 2, ',', '.') }}
                                    </p>
                                    <p style="margin:0 0 8px 0; font-size:14px;">
                                        <strong>Metodo di pagamento:</strong> {{ $purchase->payment_method_label }}
                                    </p>
                                    <p style="margin:0 0 0 0; font-size:14px;">
                                        <strong>Riferimento:</strong> #{{ $purchase->id }}
                                    </p>
                                </td>
                            </tr>
                        </table>

                        @if($purchase->payment_method === 'bonifico')
                        {{-- Box bonifico --}}
                        <table width="100%" cellpadding="0" cellspacing="0"
                               style="background:#fff8e6; border:1px solid #f0b429; border-radius:8px; margin:0 0 24px 0;">
                            <tr>
                                <td style="padding:20px;">
                                    <p style="margin:0 0 10px 0; font-size:15px; font-weight:bold; color:#92400e;">
                                        ⚠️ In attesa di conferma pagamento
                                    </p>
                                    <p style="margin:0 0 8px 0; font-size:14px;">
                                        Hai scelto il pagamento tramite <strong>bonifico bancario</strong>.<br>
                                        La tua iscrizione sarà confermata dopo la ricezione del pagamento.
                                    </p>
                                    <p style="margin:0 0 6px 0; font-size:14px;">
                                        <strong>Intestatario:</strong> A&amp;A Language Center Srl
                                    </p>
                                    <p style="margin:0 0 6px 0; font-size:14px;">
                                        <strong>IBAN:</strong> {{ config('services.bank.iban', 'IT00 0000 0000 0000 0000 0000 0') }}
                                    </p>
                                    <p style="margin:0 0 6px 0; font-size:14px;">
                                        <strong>Importo:</strong> €{{ number_format($purchase->amount, 2, ',', '.') }}
                                    </p>
                                    <p style="margin:0 0 0 0; font-size:14px;">
                                        <strong>Causale:</strong> {{ $purchase->bank_transfer_ref }}
                                    </p>
                                </td>
                            </tr>
                        </table>
                        @else
                        <p style="margin:0 0 20px 0; font-size:15px;">
                            Il nostro staff ti contatterà a breve per definire gli orari delle lezioni e assegnarti il docente più adatto al tuo percorso.
                        </p>
                        @endif

                        <p style="margin:0 0 16px 0; font-size:15px;">
                            Per qualsiasi domanda siamo a tua disposizione:
                        </p>
                        <ul style="margin:0 0 20px 20px; font-size:15px; padding:0;">
                            <li style="margin-bottom:6px;">📱 WhatsApp: <a href="https://wa.me/393463836175" style="color:#1e3a5f;">+39 346 3836175</a></li>
                            <li style="margin-bottom:6px;">✉️ Email: <a href="mailto:info@aealanguagecenter.it" style="color:#1e3a5f;">info@aealanguagecenter.it</a></li>
                        </ul>

                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="background:#f0f4f8; border-top:1px solid #dde5ef; padding:24px 40px; text-align:center;">
                        <p style="margin:0; font-size:13px; color:#555;">
                            <strong>A&amp;A Language Center Srl</strong><br>
                            Viale Leonardo Da Vinci 193 — 00145 Roma<br>
                            Tel: 06 5743734 | WhatsApp: +39 346 3836175
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
