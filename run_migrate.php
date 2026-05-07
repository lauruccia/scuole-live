<?php
/**
 * ⚠️  FILE TEMPORANEO — CANCELLARE IMMEDIATAMENTE DOPO L'USO
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('LARAVEL_START', microtime(true));

$base     = '/home/aeacenter/scuole_app';
$autoload = $base . '/vendor/autoload.php';
$bootstrap = $base . '/bootstrap/app.php';

echo '<pre style="font-family:monospace; font-size:13px; padding:20px;">';

if (!file_exists($autoload))  { die("❌ Non trovato: $autoload\n"); }
if (!file_exists($bootstrap)) { die("❌ Non trovato: $bootstrap\n"); }

require $autoload;
$app    = require_once $bootstrap;
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo "=== LARAVEL MIGRATE ===\n\n";
$status = $kernel->call('migrate', ['--force' => true]);
echo $kernel->output();
echo "\n=== Completato con status: $status ===\n";
echo "\n⚠️  CANCELLA QUESTO FILE ORA!\n";
echo '</pre>';
