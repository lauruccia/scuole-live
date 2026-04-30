<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Codice di firma contratto</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f7;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .wrapper {
            max-width: 560px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .header {
            background-color: #f59e0b;
            padding: 28px 32px;
            text-align: center;
        }
        .header h1 {
            color: #fff;
            font-size: 20px;
            margin: 0;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .body {
            padding: 32px;
        }
        .body p {
            font-size: 15px;
            line-height: 1.6;
            margin: 0 0 16px;
        }
        .otp-box {
            background: #f9fafb;
            border: 2px dashed #f59e0b;
            border-radius: 8px;
            text-align: center;
            padding: 24px 16px;
            margin: 28px 0;
        }
        .otp-box .label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6b7280;
            margin-bottom: 10px;
        }
        .otp-box .code {
            font-size: 42px;
            font-weight: 800;
            letter-spacing: 12px;
            color: #1f2937;
            font-family: 'Courier New', monospace;
        }
        .otp-box .validity {
            font-size: 13px;
            color: #6b7280;
            margin-top: 10px;
        }
        .warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 12px 16px;
            border-radius: 4px;
            font-size: 13px;
            color: #92400e;
            margin-top: 20px;
        }
        .footer {
            background: #f9fafb;
            padding: 20px 32px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>Firma del Contratto #{{ $contractId }}</h1>
    </div>
    <div class="body">
        <p>Gentile {{ $firstName }},</p>
        <p>
            Hai richiesto di firmare digitalmente il tuo contratto. Inserisci il codice qui sotto
            nell'area riservata per completare la firma.
        </p>

        <div class="otp-box">
            <div class="label">Il tuo codice di conferma</div>
            <div class="code">{{ $otpCode }}</div>
            <div class="validity">Valido per {{ $validMinutes }} minuti</div>
        </div>

        <p>
            Se non hai richiesto tu questo codice, ignora questa email. Il tuo contratto
            resterà non firmato e nessuna modifica sarà apportata.
        </p>

        <div class="warning">
            <strong>Attenzione:</strong> Non condividere mai questo codice con nessuno.
            La nostra segreteria non ti chiederà mai il codice OTP per telefono o via chat.
        </div>
    </div>
    <div class="footer">
        Questa email è stata generata automaticamente — si prega di non rispondere.<br>
        © {{ date('Y') }} {{ config('app.name') }}
    </div>
</div>
</body>
</html>
