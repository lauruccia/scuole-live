<?php
/**
 * Diagnostica mail + invio email di test.
 * ⚠️  CANCELLA QUESTO FILE DOPO L'USO!
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
define('LARAVEL_START', microtime(true));

$base      = '/home/aeacenter/scuole_app';
$autoload  = $base . '/vendor/autoload.php';
$bootstrap = $base . '/bootstrap/app.php';

echo '<pre style="font-family:monospace;font-size:13px;padding:20px;background:#1e1e1e;color:#d4d4d4;min-height:100vh;">';

if (!file_exists($autoload))  { die("❌ Non trovato: $autoload\n"); }
if (!file_exists($bootstrap)) { die("❌ Non trovato: $bootstrap\n"); }

require $autoload;
$app    = require_once $bootstrap;
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// ── 1. Configurazione mail attuale ───────────────────────────────────────────
echo "=== CONFIGURAZIONE MAIL ===\n\n";

$mailer   = config('mail.default');
$redirect = config('mail.redirect_to');

echo "MAIL_MAILER      : " . ($mailer ?: '⚠️  NON IMPOSTATO (default: log)') . "\n";
echo "MAIL_REDIRECT_TO : " . ($redirect ?: '(non attivo)') . "\n\n";

$cfg = config("mail.mailers.{$mailer}") ?? [];
$safe = $cfg;
unset($safe['password']); // nasconde la password

foreach ($safe as $k => $v) {
    echo "  {$k}: " . (is_null($v) ? 'null' : $v) . "\n";
}

// ── 2. Ultimi errori mail nel log ────────────────────────────────────────────
echo "\n=== ULTIMI ERRORI MAIL NEL LOG ===\n\n";

$logFile = $base . '/storage/logs/laravel.log';
if (file_exists($logFile)) {
    $lines   = file($logFile);
    $total   = count($lines);
    $last    = array_slice($lines, max(0, $total - 300), 300);
    $matches = array_filter($last, fn($l) =>
        stripos($l, 'mail') !== false ||
        stripos($l, 'smtp') !== false ||
        stripos($l, 'OTP') !== false  ||
        stripos($l, 'socket') !== false ||
        stripos($l, 'Connection') !== false
    );
    if ($matches) {
        echo implode('', array_slice(array_values($matches), -30));
    } else {
        echo "(nessun errore mail nelle ultime 300 righe)\n";
    }
} else {
    echo "Log non trovato: {$logFile}\n";
}

// ── 3. Invio email di test ───────────────────────────────────────────────────
echo "\n=== INVIO EMAIL DI TEST ===\n\n";

$testTo = $redirect ?: 'sitireggiocal@gmail.com';
echo "Invio a: {$testTo}\n";

try {
    \Illuminate\Support\Facades\Mail::raw(
        "Test invio email dal server AEA Center.\nMailer: {$mailer}\nTimestamp: " . now()->toDateTimeString(),
        function ($m) use ($testTo) {
            $m->to($testTo)->subject('[TEST] Email diagnostica AEA Center');
        }
    );
    echo "✅ Email inviata senza eccezioni\n";
    echo "\n⚠️  Se non la ricevi:\n";
    echo "   - Controlla cartella SPAM\n";
    echo "   - MAIL_MAILER è '{$GLOBALS['mailer']}': se è 'log' le email finiscono solo nel log\n";
    echo "   - Verifica MAIL_HOST / MAIL_PORT / credenziali nel .env\n";
} catch (\Throwable $e) {
    echo "❌ ERRORE INVIO: " . $e->getMessage() . "\n\n";
    echo "Classe: " . get_class($e) . "\n";
    // Suggerimento in base all'errore
    if (stripos($e->getMessage(), 'Connection refused') !== false ||
        stripos($e->getMessage(), 'getaddrinfo') !== false ||
        stripos($e->getMessage(), 'SMTP') !== false) {
        echo "\n⚠️  PROBLEMA SMTP: host/porta/credenziali errati nel .env\n";
    }
}

echo "\n⚠️  CANCELLA QUESTO FILE ORA!\n";
echo '</pre>';
