<?php
require __DIR__ . '/../backend/vendor/autoload.php';
$app = require __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

$product = Product::where('name', 'Raincoat')->with('variants.variantType')->first();
if (! $product) {
    echo "Raincoat not found\n";
    exit(1);
}

$variants = $product->variants;
$variantOptions = [];
foreach ($variants as $v) {
    $type = strtolower($v->variantType->name);
    $variantOptions[$type][] = [
        'value' => $v->value,
        'price' => isset($v->price) ? floatval($v->price) : 0.0,
    ];
}

// unique by value
foreach ($variantOptions as $k => $items) {
    $unique = [];
    foreach ($items as $it) {
        $unique[$it['value']] = $it;
    }
    $variantOptions[$k] = array_values($unique);
}

echo json_encode($variantOptions, JSON_PRETTY_PRINT) . "\n";
