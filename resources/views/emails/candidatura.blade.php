@php
    use App\Models\SchoolSetting;
    $schoolName = SchoolSetting::schoolName();
    $fullName   = trim($data['first_name'] . ' ' . $data['last_name']);
@endphp
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Nuova candidatura docente</title>
</head>
<body style="margin:0;padding:0;background:#f6faff;font-family:Arial,Helvetica,sans-serif;color:#18243a;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f6faff;padding:40px 20px;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(0,37,91,.10);">

      {{-- Header --}}
      <tr>
        <td style="background:linear-gradient(110deg,#001126 0%,#003580 55%,#0057d9 100%);padding:32px 40px;text-align:center;">
          <p style="margin:0;color:#fff;font-size:24px;font-weight:900;letter-spacing:-0.5px;">{{ $schoolName }}</p>
          <p style="margin:6px 0 0;color:#ccdcf7;font-size:13px;">Nuova candidatura dal sito — Lavora con Noi</p>
        </td>
      </tr>

      {{-- Body --}}
      <tr>
        <td style="padding:32px 40px;">
          <p style="margin:0 0 20px;font-size:17px;font-weight:800;color:#001b3f;">
            Candidatura Docente — {{ $data['lingua'] }}
          </p>

          <table width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#18243a;">
            <tr>
              <td style="padding:8px 0;border-bottom:1px solid #e8eef8;width:180px;color:#526173;">Nome e cognome</td>
              <td style="padding:8px 0;border-bottom:1px solid #e8eef8;font-weight:700;">{{ $fullName }}</td>
            </tr>
            <tr>
              <td style="padding:8px 0;border-bottom:1px solid #e8eef8;color:#526173;">Email</td>
              <td style="padding:8px 0;border-bottom:1px solid #e8eef8;"><a href="mailto:{{ $data['email'] }}" style="color:#0057d9;">{{ $data['email'] }}</a></td>
            </tr>
            <tr>
              <td style="padding:8px 0;border-bottom:1px solid #e8eef8;color:#526173;">Telefono</td>
              <td style="padding:8px 0;border-bottom:1px solid #e8eef8;"><a href="tel:{{ $data['phone'] }}" style="color:#0057d9;">{{ $data['phone'] }}</a></td>
            </tr>
            <tr>
              <td style="padding:8px 0;border-bottom:1px solid #e8eef8;color:#526173;">Lingua/e insegnate</td>
              <td style="padding:8px 0;border-bottom:1px solid #e8eef8;font-weight:700;">{{ $data['lingua'] }}</td>
            </tr>
            @if (!empty($data['laurea']))
            <tr>
              <td style="padding:8px 0;border-bottom:1px solid #e8eef8;color:#526173;">Laurea</td>
              <td style="padding:8px 0;border-bottom:1px solid #e8eef8;">{{ $data['laurea'] }}</td>
            </tr>
            @endif
            @if (!empty($data['certificazioni']))
            <tr>
              <td style="padding:8px 0;border-bottom:1px solid #e8eef8;color:#526173;">Certificazioni di insegnamento</td>
              <td style="padding:8px 0;border-bottom:1px solid #e8eef8;">{{ $data['certificazioni'] }}</td>
            </tr>
            @endif
          </table>

          @if (!empty($data['esperienze']))
            <p style="margin:22px 0 6px;font-size:13px;font-weight:700;color:#526173;text-transform:uppercase;letter-spacing:.04em;">Esperienze di lavoro rilevanti</p>
            <p style="margin:0;font-size:14px;color:#18243a;line-height:1.7;white-space:pre-line;">{{ $data['esperienze'] }}</p>
          @endif

          @if (!empty($data['message']))
            <p style="margin:22px 0 6px;font-size:13px;font-weight:700;color:#526173;text-transform:uppercase;letter-spacing:.04em;">Presentazione</p>
            <p style="margin:0;font-size:14px;color:#18243a;line-height:1.7;white-space:pre-line;">{{ $data['message'] }}</p>
          @endif

          <p style="margin:26px 0 0;padding:14px 18px;background:#eef4ff;border-radius:10px;font-size:13px;color:#003580;">
            &#128206; Il CV del candidato è allegato a questa email.
            Per rispondere basta usare "Rispondi": il messaggio andrà direttamente al candidato.
          </p>
        </td>
      </tr>

      {{-- Footer --}}
      <tr>
        <td style="background:#f6faff;padding:18px 40px;text-align:center;">
          <p style="margin:0;font-size:12px;color:#8194ab;">
            Candidatura inviata dal form "Lavora con Noi" di {{ config('app.url') }} — il candidato ha accettato l'informativa privacy.
          </p>
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>
