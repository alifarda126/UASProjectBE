<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;

try {
    Storage::disk('s3')->put('test.txt', 'hello world', 'public');
    echo 'Uploaded! URL: ' . Storage::disk('s3')->url('test.txt') . PHP_EOL;
} catch (\Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
