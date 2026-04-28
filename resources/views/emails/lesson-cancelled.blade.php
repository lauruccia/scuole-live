<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunicazione lezione — A&amp;A Language Center</title>
</head>
<body style="margin:0; padding:0; background:#f5f5f5; font-family: Arial, Helvetica, sans-serif; color:#222; line-height:1.7;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5; padding:30px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0"
                   style="width:600px; max-width:600px; background:#ffffff; border-radius:8px; overflow:hidden;">

                {{-- Header --}}
                <tr>
                    <td style="background:#1e3a5f; padding:24px 40px; text-align:center;">
                        <h1 style="margin:0; color:#ffffff; font-size:20px; letter-spacing:1px;">
                            A&amp;A Language Center
                        </h1>
                        <p style="margin:4px 0 0 0; color:#b0c8e8; font-size:13px;">
                            Comunicazione lezione
                        </p>
                    </td>
                </tr>

                {{-- Corpo --}}
                <tr>
                    <td style="padding:32px 40px;">

                        <p style="margin:0 0 16px 0; font-size:15px;">
                            Ciao <strong>{{ $studentName }}</strong>,
                        </p>

                        {{-- Box lezione --}}
                        <table width="100%" cellpadding="0" cellspacing="0"
                               style="background:#f8f9fb; border:1px solid #dde3ec; border-radius:8px; margin:0 0 24px 0;">
                            <tr>
                                <td style="padding:18px 20px;">
                                    <p style="margin:0 0 6px 0; font-size:13px; color:#555; font-weight:bold; text-transform:uppercase; letter-spacing:.5px;">
                                        Dettagli lezione
                                    </p>
                                    <p style="margin:0 0 4px 0; font-size:15px;">
                                        📅 <strong>Data:</strong>
                                        {{ $lesson->starts_at?->format('d/m/Y') ?? '—' }}
                                    </p>
                                    <p style="margin:0 0 4px 0; font-size:15px;">
                                        🕐 <strong>Orario:</strong>
                                        {{ $lesson->starts_at?->format('H:i') ?? '—' }}
                                        –
                                        {{ $lesson->ends_at?->format('H:i') ?? '—' }}
                                    </p>
                                    @if($lesson->language_id)
                                    <p style="margin:0 0 4px 0; font-size:15px;">
                                        🌍 <strong>Lingua:</strong> {{ $lesson->language_id }}
                                    </p>
                                    @endif
                                    @if($lesson->teacher)
                                    <p style="margin:0; font-size:15px;">
                                        👩‍🏫 <strong>Docente:</strong>
                                        {{ $lesson->teacher->name ?? $lesson->teacher->first_name . ' ' . $lesson->teacher->last_name }}
                                    </p>
                                    @endif
                                </td>
                            </tr>
                        </table>

                        {{-- Messaggio in base al tipo --}}
                        @if($cancellationType === 'recoverable')
                            <div style="background:#e8f5e9; border:1px solid #a5d6a7; border-radius:8px; padding:16px 20px; margin:0 0 20px 0;">
                                <p style="margin:0 0 6px 0; font-weight:bold; color:#2e7d32;">✅ Lezione annullata — recupero previsto</p>
                                <p style="margin:0; font-size:14px; color:#333;">
                                    La tua lezione è stata annullata con sufficiente preavviso (oltre 24 ore).
                                    Verrà programmata una lezione di recupero. Ti contatteremo per concordare la data.
                                </p>
                            </div>
                        @elseif($cancellationType === 'consumed')
                            <div style="background:#fff3e0; border:1px solid #ffcc80; border-radius:8px; padding:16px 20px; margin:0 0 20px 0;">
                                <p style="margin:0 0 6px 0; font-weight:bold; color:#e65100;">⚠️ Lezione annullata — ore scalate</p>
                                <p style="margin:0; font-size:14px; color:#333;">
                                    La tua lezione è stata annullata con meno di 24 ore di preavviso.
                                    Come da regolamento, le ore verranno scalate dal tuo contratto.
                                </p>
                                <p style="margin:8px 0 0 0; font-size:13px; color:#555;">
                                    Ricorda: le lezioni prenotate vanno sempre disdette con almeno
                                    <strong>24 ore di anticipo</strong> per non consumare le ore.
                                </p>
                            </div>
                        @else
                            <div style="background:#f3f4f6; border:1px solid #d1d5db; border-radius:8px; padding:16px 20px; margin:0 0 20px 0;">
                                <p style="margin:0 0 6px 0; font-weight:bold; color:#374151;">ℹ️ Lezione annullata</p>
                                <p style="margin:0; font-size:14px; color:#333;">
                                    La tua lezione è stata annullata. Le ore non verranno scalate dal tuo contratto.
                                    Per maggiori informazioni contatta la segreteria.
                                </p>
                            </div>
                        @endif

                        @if($lesson->cancellation_reason)
                        <p style="font-size:14px; color:#555; margin:0 0 20px 0;">
                            <strong>Motivo comunicato:</strong> {{ $lesson->cancellation_reason }}
                        </p>
                        @endif

                        <p style="font-size:15px; margin:0 0 16px 0;">
                            Per qualsiasi domanda contatta la segreteria:
                        </p>

                        <ul style="font-size:14px; margin:0 0 20px 20px; padding:0;">
                            <li style="margin-bottom:4px;">
                                📱 WhatsApp: <a href="https://wa.me/393463836175" style="color:#1e3a5f;">+39 346 3836175</a>
                            </li>
                            <li style="margin-bottom:4px;">
                                ✉️ <a href="mailto:info@aealanguagecenter.it" style="color:#1e3a5f;">info@aealanguagecenter.it</a>
                            </li>
                            <li>☎️ 06 5743734</li>
                        </ul>

                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="background:#f0f4f8; border-top:1px solid #dde5ef; padding:20px 40px; text-align:center;">
                        <p style="margin:0; font-size:12px; color:#888;">
                            A&amp;A Language Center Srl — Viale Leonardo Da Vinci 193, 00145 Roma<br>
                            <a href="https://aealanguagecenter.it/" style="color:#1e3a5f;">aealanguagecenter.it</a>
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
