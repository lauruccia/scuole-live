<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Disiscrizione confermata</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; max-width: 560px; margin: 4rem auto; padding: 0 1.5rem; color: #222; text-align: center; }
        h1 { font-size: 1.5rem; color: #2e7d32; }
        .box { background: #f6f7f9; border-radius: 8px; padding: 1.5rem; margin: 1rem 0; }
        .secondary { color: #666; font-size: .9rem; }
    </style>
</head>
<body>
    <h1>{{ $already ? 'Eri già disiscritto' : 'Disiscrizione completata' }}</h1>
    <div class="box">
        <p>L'indirizzo <strong>{{ $email }}</strong> non riceverà più comunicazioni promozionali.</p>
        @unless($already)
            <p class="secondary">Se cambi idea, contatta direttamente la segreteria della scuola.</p>
        @endunless
    </div>
</body>
</html>
