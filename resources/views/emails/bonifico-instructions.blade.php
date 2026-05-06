@php
    use App\Models\SchoolSetting;
    $schoolName = SchoolSetting::schoolName();
    $iban       = SchoolSetting::bankIban();
    $intest     = SchoolSetting::bankIntestatario();
    $courseName = $purchase->course->name ?? 'Corso';
    $amount     = number_format((float) $purchase->amount, 2, ',', '.');
    $ref        = $purchase->bank_transfer_ref;
    $supportPhone  = SchoolSetting::schoolPhone();
    $supportEmail  = SchoolSetting::schoolEmail();
@endphp
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Istruzioni bonifico</title>
</head>
<body style="margin:0;padding:0;background:#f6faff;font-family:Arial,Helvetica,sans-serif;color:#18243a;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f6faff;padding:40px 20px;">
  <tr><td align="center">
    <table width="640" cellpadding="0" cellspacing="0" style="max-width:640px;width:100%;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(0,37,91,.10);">

      {{-- Header --}}
      <tr>
        <td style="background:linear-gradient(110deg,#7c4a00,#d97706);padding:36px 40px;color:#fff;">
          <p style="margin:0 0 4px;font-size:13px;opacity:.85;letter-spacing:.05em;text-transform:uppercase;">{{ $schoolName }}</p>
          <p style="margin:0;font-size:22px;font-weight:900;">🏦 Istruzioni per il bonifico</p>
          <p style="margin:6px 0 0;font-size:14px;opacity:.85;">La tua iscrizione &egrave; in attesa del pagamento</p>
        </td>
      </tr>

      {{-- Body --}}
      <tr>
        <td style="padding:32px 40px;">

          <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#18243a;">
            Ciao <strong>{{ $purchase->billing_first_name ?? '' }}</strong>,<br>
            grazie per aver scelto il corso <strong>{{ $courseName }}</strong>.<br>
            Per completare l'iscrizione esegui un bonifico con questi dati:
          </p>

          {{-- Tabella dati bonifico --}}
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;margin-bottom:20px;">
            <tr>
              <td style="padding:14px 20px;border-bottom:1px solid #dbeafe;">
                <p style="margin:0 0 4px;font-size:11px;color:#526173;font-weight:700;text-transform:uppercase;letter-spacing:.05em;">Intestatario</p>
                <p style="margin:0;font-size:15px;font-weight:900;color:#001b3f;">{{ $intest ?: $schoolName }}</p>
              </td>
            </tr>
            <tr>
              <td style="padding:14px 20px;border-bottom:1px solid #dbeafe;">
                <p style="margin:0 0 4px;font-size:11px;color:#526173;font-weight:700;text-transform:uppercase;letter-spacing:.05em;">IBAN</p>
                <p style="margin:0;font-size:16px;font-weight:900;color:#001b3f;font-family:Courier New,monospace;letter-spacing:.05em;">{{ $iban ?: 'IBAN non configurato — contatta la scuola' }}</p>
              </td>
            </tr>
            <tr>
              <td style="padding:14px 20px;">
                <p style="margin:0 0 4px;font-size:11px;color:#526173;font-weight:700;text-transform:uppercase;letter-spacing:.05em;">Importo</p>
                <p style="margin:0;font-size:20px;font-weight:900;color:#0057d9;">&euro; {{ $amount }}</p>
              </td>
            </tr>
          </table>

          {{-- Causale evidenziata --}}
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#fffbeb;border:2px dashed #f59e0b;border-radius:12px;margin-bottom:24px;">
            <tr>
              <td style="padding:18px 22px;text-align:center;">
                <p style="margin:0 0 8px;font-size:12px;color:#92400e;font-weight:700;text-transform:uppercase;letter-spacing:.05em;">⚠️ Causale obbligatoria</p>
                <p style="margin:0;font-size:22px;font-weight:900;color:#78350f;letter-spacing:.06em;font-family:Courier New,monospace;">{{ $ref }}</p>
                <p style="margin:8px 0 0;font-size:12px;color:#92400e;">Inserisci esattamente questa causale, &egrave; il codice che ci permette di abbinare il pagamento alla tua iscrizione.</p>
              </td>
            </tr>
          </table>

          {{-- Steps --}}
          <p style="margin:0 0 12px;font-size:14px;font-weight:800;color:#001b3f;">Cosa fare adesso:</p>
          <ol style="margin:0 0 24px 22px;padding:0;font-size:14px;line-height:1.8;color:#18243a;">
            <li>Accedi alla tua banca online (o vai allo sportello)</li>
            <li>Imposta l'importo esatto: <strong>&euro; {{ $amount }}</strong></li>
            <li>Inserisci la causale: <strong>{{ $ref }}</strong></li>
            <li>Riceverai una email di conferma entro <strong>1&ndash;2 giorni lavorativi</strong> dalla ricezione del bonifico</li>
            <li>Il nostro staff ti contatter&agrave; per definire orari e docente</li>
          </ol>

          {{-- Box contatti --}}
          @if ($supportPhone || $supportEmail)
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border-radius:8px;margin-bottom:8px;">
            <tr>
              <td style="padding:14px 18px;font-size:13px;color:#526173;line-height:1.6;">
                Domande sul pagamento?
                @if ($supportPhone)
                  📞 <a href="tel:{{ str_replace(' ', '', $supportPhone) }}" style="color:#0057d9;font-weight:700;">{{ $supportPhone }}</a>
                @endif
                @if ($supportPhone && $supportEmail) &nbsp;&middot;&nbsp; @endif
                @if ($supportEmail)
                  ✉️ <a href="mailto:{{ $supportEmail }}" style="color:#0057d9;font-weight:700;">{{ $supportEmail }}</a>
                @endif
              </td>
            </tr>
          </table>
          @endif

        </td>
      </tr>

      {{-- Footer --}}
      <tr>
        <td style="background:#f0f4ff;padding:18px 40px;text-align:center;border-top:1px solid #dbe7f4;">
          <p style="margin:0;font-size:12px;color:#7a8ca8;line-height:1.6;">
            <strong style="color:#001b3f;">{{ $schoolName }}</strong>
            @if (SchoolSetting::schoolFullAddress())
              <br>{{ SchoolSetting::schoolFullAddress() }}
            @endif
            <br>Email automatica generata dal sistema di iscrizione &mdash; non rispondere a questo indirizzo.
          </p>
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>
