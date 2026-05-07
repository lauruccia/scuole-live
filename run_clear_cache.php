<?php
/**
 * Svuota config cache + application cache.
 * ⚠️  CANCELLA QUESTO FILE DOPO L'USO!
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('LARAVEL_START', microtime(true));
$base      = '/home/aeacenter/scuole_app';
$autoload  = $base . '/vendor/autoload.php';
$bootstrap = $base . '/bootstrap/app.php';

echo '<pre style="font-family:monospace;font-size:13px;padding:20px;">';

if (!file_exists($autoload))  { die("❌ Non trovato: $autoload\n"); }
if (!file_exists($bootstrap)) { die("❌ Non trovato: $bootstrap\n"); }

require $autoload;
$app    = require_once $bootstrap;
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo "=== CLEAR CACHE ===\n\n";

foreach (['config:clear', 'cache:clear', 'view:clear', 'route:clear'] as $cmd) {
    $kernel->call($cmd);
    echo "✅ {$cmd}\n" . $kernel->output();
}

echo "\n⚠️  CANCELLA QUESTO FILE ORA!\n";
echo '</pre>';
