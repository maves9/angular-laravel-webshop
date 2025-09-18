<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$products = App\Models\Product::all(['id','name'])->toArray();
echo json_encode($products, JSON_PRETTY_PRINT);
