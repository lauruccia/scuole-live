<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Conferma disiscrizione</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; max-width: 560px; margin: 4rem auto; padding: 0 1.5rem; color: #222; }
        h1 { font-size: 1.5rem; }
        .box { background: #f6f7f9; border-radius: 8px; padding: 1.5rem; margin: 1rem 0; }
        .btn { display: inline-block; padding: .75rem 1.25rem; background: #c0392b; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 1rem; }
        .btn:hover { background: #a52a1f; }
        .secondary { color: #666; font-size: .9rem; }
        textarea { width: 100%; padding: .5rem; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; }
    </style>
</head>
<body>
    <h1>Vuoi davvero disiscriverti?</h1>
    <div class="box">
        <p>Stai per disiscrivere l'indirizzo:</p>
        <p><strong>{{ $email }}</strong></p>
        <p class="secondary">Dopo la conferma non riceverai più comunicazioni promozionali o newsletter dal nostro istituto. Le comunicazioni strettamente legate ai tuoi contratti attivi (ad esempio scadenze rate) potrebbero comunque essere inviate.</p>
    </div>
    <form method="POST" action="{{ url('/unsubscribe/' . $token) }}">
        @csrf
        <p>
            <label for="reason" class="secondary">Motivo (opzionale, ci aiuta a migliorare):</label><br>
            <textarea name="reason" id="reason" rows="3" maxlength="100" placeholder="Es. Non più interessato, troppe email…"></textarea>
        </p>
        <button type="submit" class="btn">Confermo, voglio disiscrivermi</button>
    </form>
</body>
</html>
