<?php
$path = realpath(__DIR__ . '/../resources/views/checkout/catalogo.blade.php');
echo 'Path: ' . $path . PHP_EOL;
echo 'Hash: ' . md5($path) . PHP_EOL;
