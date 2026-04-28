<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectLine }}</title>
</head>
<body style="margin:0; padding:0; background:#f5f5f5; font-family: Arial, Helvetica, sans-serif; color:#222; line-height:1.6;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5; padding:30px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="width:600px; max-width:600px; background:#ffffff; border-radius:8px; padding:40px;">
                    <tr>
                        <td>
                            <h2 style="margin:0 0 20px 0; font-size:22px; color:#111;">
                                {{ $subjectLine }}
                            </h2>

                            <p style="margin:0 0 16px 0;">
                                Ciao {{ $studentName ?: 'studente' }},
                            </p>

                            <div style="margin:0 0 20px 0;">
                                {!! $htmlBody !!}
                            </div>

<p style="margin:25px 0 0 0;">
    Un saluto,<br><br>

    <strong>Segreteria</strong><br>
    A&amp;A LANGUAGE CENTER SRL<br>
    Viale Leonardo Da Vinci 193<br>
    00145 Roma<br>
    Tel +39 06.5743734<br>
    Mobile +39 3463836175<br>
    <a href="https://www.aealanguagecenter.it" target="_blank">
        www.aealanguagecenter.it
    </a>
</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>