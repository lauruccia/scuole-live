@php
    use App\Models\SchoolSetting;
    $schoolName       = SchoolSetting::schoolName();
    $schoolFullAddr   = SchoolSetting::schoolFullAddress();
    $schoolPhone      = SchoolSetting::schoolPhone();
    $schoolEmail      = SchoolSetting::schoolEmail();
    $schoolWebsite    = SchoolSetting::schoolWebsite() ?: config('app.url');
    $headerNote       = SchoolSetting::ricevutaHeaderNote(); // riusiamo il sottotitolo configurabile
    $courseInterest   = $lead->course_interest ?: 'Non specificato';
    $fullName         = trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? ''));
@endphp
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Richiesta ricevuta</title>
</head>
<body style="margin:0;padding:0;background:#f6faff;font-family:Arial,Helvetica,sans-serif;color:#18243a;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f6faff;padding:40px 20px;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(0,37,91,.10);">

      {{-- Header --}}
      <tr>
        <td style="background:linear-gradient(110deg,#001126 0%,#003580 55%,#0057d9 100%);padding:36px 40px;text-align:center;">
          <p style="margin:0;color:#fff;font-size:26px;font-weight:900;letter-spacing:-0.5px;">{{ $schoolName }}</p>
          @if ($headerNote)
            <p style="margin:6px 0 0;color:#ccdcf7;font-size:13px;">{{ $headerNote }}</p>
          @endif
        </td>
      </tr>

      {{-- Body --}}
      <tr>
        <td style="padding:36px 40px;">
          <p style="margin:0 0 6px;font-size:18px;font-weight:800;color:#001b3f;">
            Ciao {{ $lead->first_name }}! &#128075;
          </p>
          <p style="margin:0 0 24px;font-size:15px;color:#526173;line-height:1.7;">
            Abbiamo ricevuto la tua richiesta di informazioni. Il nostro staff ti contatter&agrave; entro
            <strong style="color:#001b3f;">24 ore lavorative</strong> per fissare un colloquio conoscitivo
            e trovare il percorso pi&ugrave; adatto a te.
          </p>

          {{-- Riepilogo richiesta --}}
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f6ff;border-radius:10px;margin-bottom:24px;">
            <tr><td style="padding:20px 24px;">
              <p style="margin:0 0 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#526173;">Riepilogo richiesta</p>
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:5px 0;font-size:13px;color:#526173;width:40%;">Nome</td>
                  <td style="padding:5px 0;font-size:13px;font-weight:600;color:#001b3f;">{{ $fullName }}</td>
                </tr>
                <tr>
                  <td style="padding:5px 0;font-size:13px;color:#526173;">Email</td>
                  <td style="padding:5px 0;font-size:13px;font-weight:600;color:#001b3f;">{{ $lead->email }}</td>
                </tr>
                @if ($lead->phone)
                <tr>
                  <td style="padding:5px 0;font-size:13px;color:#526173;">Telefono</td>
                  <td style="padding:5px 0;font-size:13px;font-weight:600;color:#001b3f;">{{ $lead->phone }}</td>
                </tr>
                @endif
                <tr>
                  <td style="padding:5px 0;font-size:13px;color:#526173;">Corso richiesto</td>
                  <td style="padding:5px 0;font-size:13px;font-weight:600;color:#001b3f;">{{ $courseInterest }}</td>
                </tr>
              </table>
            </td></tr>
          </table>

          @if ($userMessage)
            <p style="margin:0 0 8px;font-size:13px;font-weight:700;color:#001b3f;">Il tuo messaggio:</p>
            <p style="margin:0 0 24px;font-size:13px;color:#526173;line-height:1.7;background:#f9fafb;border-left:3px solid #0057d9;padding:12px 16px;border-radius:0 8px 8px 0;">{!! nl2br(e($userMessage)) !!}</p>
          @endif

          @if ($schoolPhone || $schoolEmail)
          <p style="margin:0 0 4px;font-size:14px;color:#18243a;line-height:1.7;">
            Se nel frattempo hai domande, puoi contattarci direttamente:
          </p>
          <p style="margin:0 0 28px;font-size:14px;">
            @if ($schoolPhone)
              📞 <a href="tel:{{ str_replace(' ', '', $schoolPhone) }}" style="color:#0057d9;font-weight:600;">{{ $schoolPhone }}</a>
            @endif
            @if ($schoolPhone && $schoolEmail) &nbsp;&nbsp; @endif
            @if ($schoolEmail)
              ✉️ <a href="mailto:{{ $schoolEmail }}" style="color:#0057d9;font-weight:600;">{{ $schoolEmail }}</a>
            @endif
          </p>
          @endif

          @if ($schoolWebsite)
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr><td align="center">
              <a href="{{ $schoolWebsite }}" style="display:inline-block;background:#0057d9;color:#fff;padding:14px 36px;border-radius:8px;font-size:14px;font-weight:800;text-decoration:none;letter-spacing:.02em;">
                Visita il nostro sito →
              </a>
            </td></tr>
          </table>
          @endif
        </td>
      </tr>

      {{-- Footer --}}
      <tr>
        <td style="background:#f0f4ff;padding:20px 40px;text-align:center;border-top:1px solid #dbe7f4;">
          <p style="margin:0;font-size:12px;color:#7a8ca8;line-height:1.6;">
            <strong style="color:#001b3f;">{{ $schoolName }}</strong>
            @if ($schoolFullAddr)
              <br>{{ $schoolFullAddr }}
            @endif
            <br>Hai ricevuto questa email perch&eacute; hai compilato il modulo di contatto sul nostro sito.<br>
            Per esercitare i tuoi diritti privacy: <a href="{{ rtrim(config('app.url'), '/') }}/privacy" style="color:#0057d9;">Privacy Policy</a>
          </p>
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>
