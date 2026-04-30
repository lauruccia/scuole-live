<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benvenuto/a in A&amp;A Language Center!</title>
</head>
<body style="margin:0; padding:0; background:#f5f5f5; font-family: Arial, Helvetica, sans-serif; color:#222; line-height:1.7;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5; padding:30px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0"
                   style="width:600px; max-width:600px; background:#ffffff; border-radius:8px; overflow:hidden;">

                {{-- Header blu --}}
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

                        <p style="margin:0 0 20px 0; font-size:16px;">
                            Ciao <strong>{{ $firstName }}</strong>,
                        </p>

                        <p style="margin:0 0 16px 0; font-size:15px;">
                            Tutto lo staff di <strong>A&amp;A Language Center</strong> ti dà un
                            caloroso benvenuto!!! 🎉
                        </p>

                        <p style="margin:0 0 16px 0; font-size:15px;">
                            Siamo entusiaste di averti con noi e ci impegniamo a fornire
                            un'esperienza di apprendimento gratificante.<br>
                            <strong>Il tuo viaggio verso la crescita personale inizia ora!</strong>
                        </p>

                        <p style="margin:0 0 16px 0; font-size:15px;">
                            Questa breve guida contiene informazioni e consigli che ti torneranno
                            utili nel percorso di studi personalizzato che hai intrapreso.
                        </p>

                        <p style="margin:0 0 24px 0; font-size:15px;">
                            Assicurati di memorizzare questo indirizzo di posta per evitare che
                            finisca nella casella <strong>SPAM</strong>.
                        </p>

                        {{-- Box credenziali --}}
                        <table width="100%" cellpadding="0" cellspacing="0"
                               style="background:#f0f5ff; border:1px solid #c3d4ef; border-radius:8px;
                                      margin:0 0 28px 0; padding:20px;">
                            <tr>
                                <td style="padding:20px;">
                                    <p style="margin:0 0 10px 0; font-size:15px; font-weight:bold; color:#1e3a5f;">
                                        🔐 Le tue credenziali di accesso
                                    </p>
                                    <p style="margin:0 0 6px 0; font-size:14px;">
                                        <strong>Portale:</strong>
                                        <a href="{{ config('app.url') }}/studente"
                                           style="color:#1e3a5f;">
                                            {{ config('app.url') }}/studente
                                        </a>
                                    </p>
                                    <p style="margin:0 0 6px 0; font-size:14px;">
                                        <strong>Email:</strong> {{ $studentEmail }}
                                    </p>
                                    <p style="margin:0 0 10px 0; font-size:14px;">
                                        <strong>Password provvisoria:</strong>
                                        <span style="font-family:monospace; background:#e8eef8;
                                                     padding:2px 8px; border-radius:4px;">
                                            {{ $loginPassword }}
                                        </span>
                                    </p>
                                    <p style="margin:0; font-size:13px; color:#555;">
                                        Ti consigliamo di cambiare la password al primo accesso.
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:0 0 16px 0; font-size:15px;">
                            Se hai domande o hai bisogno di assistenza, il nostro team di supporto,
                            <strong>Egerja</strong> e <strong>Paola</strong>, è sempre qui per aiutarti.
                        </p>

                        <p style="margin:0 0 16px 0; font-size:15px;">
                            Puoi comunicare con noi tramite:
                        </p>

                        <ul style="margin:0 0 20px 20px; font-size:15px; padding:0;">
                            <li style="margin-bottom:6px;">
                                📱 WhatsApp:
                                <a href="https://wa.me/393463836175" style="color:#1e3a5f;">
                                    +39 346 3836175
                                </a>
                            </li>
                            <li style="margin-bottom:6px;">
                                ✉️ Email:
                                <a href="mailto:info@aealanguagecenter.it" style="color:#1e3a5f;">
                                    info@aealanguagecenter.it
                                </a>
                            </li>
                            <li style="margin-bottom:6px;">
                                ☎️ Telefono fisso: <strong>06 5743734</strong>
                            </li>
                        </ul>

                        <p style="margin:0 0 16px 0; font-size:15px;">
                            <strong>Orari di apertura A&amp;A:</strong><br>
                            Lunedì – Venerdì: 10:00 – 19:00 (orario continuato)<br>
                            Sabato: 09:00 – 13:00
                        </p>

                        <p style="margin:0 0 16px 0; font-size:15px;">
                            Le lezioni possono essere fruite <strong>in presenza</strong>,
                            in <strong>video conferenza</strong> e in modalità <strong>ibrida</strong>.
                        </p>

                        <p style="margin:0 0 24px 0; font-size:15px;">
                            ⚠️ Ti ricordiamo che le lezioni prenotate (individuali e/o full immersion)
                            vanno sempre disdette con un preavviso minimo di
                            <strong>24 ore</strong>, altrimenti saranno considerate come fruite.
                        </p>

                        {{-- Citazione --}}
                        <table width="100%" cellpadding="0" cellspacing="0"
                               style="background:#fff8e6; border-left:4px solid #f0b429;
                                      border-radius:4px; margin:0 0 28px 0;">
                            <tr>
                                <td style="padding:16px 20px; font-size:14px; font-style:italic; color:#555;">
                                    "Chi non conosce le lingue straniere, non sa nulla della propria.
                                    Chi conosce una lingua straniera ha una vita in più."<br>
                                    <span style="font-style:normal; font-weight:bold; color:#333;">
                                        — Goethe
                                    </span>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:0 0 16px 0; font-size:15px;">
                            È con questo doppio spirito — di volontà di condivisione e apertura alla
                            conoscenza degli altri (e di se stessi) — che ti accogliamo presso
                            <strong>A&amp;A Language Center</strong> e ti auguriamo di espandere
                            le tue conoscenze!
                        </p>

                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="background:#f0f4f8; border-top:1px solid #dde5ef;
                                padding:24px 40px; text-align:center;">
                        <p style="margin:0 0 8px 0; font-size:13px; color:#555;">
                            <strong>A&amp;A Language Center Srl</strong><br>
                            Viale Leonardo Da Vinci 193 — 00145 Roma<br>
                            Tel: 06 5743734 | WhatsApp: +39 346 3836175
                        </p>
                        <p style="margin:8px 0 0 0; font-size:13px;">
                            <a href="https://aealanguagecenter.it/" style="color:#1e3a5f;">
                                🌐 aealanguagecenter.it
                            </a>
                        </p>
                        <p style="margin:8px 0 0 0; font-size:12px; color:#888;">
                            Seguici sui nostri canali social per rimanere sempre aggiornato.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
