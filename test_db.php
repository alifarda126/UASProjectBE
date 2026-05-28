<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$org = \App\Models\Organisasi::first();
echo 'Path di DB: ' . $org->logo . PHP_EOL;
echo 'URL yg di-generate: ' . $org->logo_url . PHP_EOL;
