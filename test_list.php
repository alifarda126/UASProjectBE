<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;

$files = Storage::disk('s3')->allFiles('logos');
foreach ($files as $file) {
    echo "Found: " . $file . "\n";
}
