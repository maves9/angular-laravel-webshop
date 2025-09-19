<?php
require __DIR__ . '/../backend/vendor/autoload.php';
$app = require __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use Illuminate\Support\Facades\DB;

$product = Product::where('name', 'Raincoat')->with('variants.variantType')->first();
if (! $product) {
    echo "Raincoat not found\n";
    exit(1);
}

foreach ($product->variants as $v) {
    $type = strtolower(optional($v->variantType)->name ?? 'unknown');
    $en = sprintf('%s - %s: %s', $product->name, $type, $v->value);
    $da = sprintf('%s - %s: %s (DA)', $product->name, $type, $v->value);
    $v->descriptions = ['en' => $en, 'da' => $da];
    $v->save();
    echo "Updated variant {$v->id} ({$type}={$v->value})\n";
}

// clear any combination descriptions (they will be computed by controller on the fly)
DB::table('product_variant_combinations')->update(['description' => null]);

echo "Done\n";
