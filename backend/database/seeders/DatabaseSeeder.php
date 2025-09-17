<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantCombination;
use Illuminate\Support\Facades\DB;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
        ]);

        // Clear existing products/variants/combinations to avoid duplicates when re-seeding
        DB::table('product_variant_combinations')->delete();
        DB::table('product_variants')->delete();
        DB::table('products')->delete();

        // Create sample products with variants
        $products = [
            [
                'name' => 'Classic T-Shirt',
                'description' => 'A comfortable classic t-shirt.',
                'price' => 19.99,
                'stock' => 50,
                'variants' => [
                    ['type' => 'size', 'value' => 'S'],
                    ['type' => 'size', 'value' => 'M'],
                    ['type' => 'size', 'value' => 'L'],
                    ['type' => 'color', 'value' => 'red'],
                    ['type' => 'color', 'value' => 'blue'],
                    ['type' => 'fabric', 'value' => 'cotton'],
                    ['type' => 'fabric', 'value' => 'polyester'],
                ],
            ],
            [
                'name' => 'Summer Dress',
                'description' => 'Lightweight summer dress.',
                'price' => 49.99,
                'stock' => 20,
                'variants' => [
                    ['type' => 'size', 'value' => 'XS'],
                    ['type' => 'size', 'value' => 'S'],
                    ['type' => 'size', 'value' => 'M'],
                    ['type' => 'color', 'value' => 'yellow'],
                    ['type' => 'fabric', 'value' => 'linen'],
                ],
            ],
            [
                'name' => 'Denim Jacket',
                'description' => 'Classic denim jacket.',
                'price' => 89.99,
                'stock' => 10,
                'variants' => [
                    ['type' => 'size', 'value' => 'M'],
                    ['type' => 'size', 'value' => 'L'],
                    ['type' => 'color', 'value' => 'blue'],
                    ['type' => 'fabric', 'value' => 'denim'],
                ],
            ],
        ];

        foreach ($products as $p) {
            $product = Product::create([
                'name' => $p['name'],
                'description' => $p['description'],
                'price' => $p['price'],
                'stock' => $p['stock'],
            ]);

            foreach ($p['variants'] as $v) {
                $product->variants()->create($v);
            }
            // Build full combinations for size x color x fabric
            $sizes = $product->variants()->where('type', 'size')->pluck('value')->unique()->values()->all();
            $colors = $product->variants()->where('type', 'color')->pluck('value')->unique()->values()->all();
            $fabrics = $product->variants()->where('type', 'fabric')->pluck('value')->unique()->values()->all();

            // Ensure at least one option exists for each type
            if (!empty($sizes) && !empty($colors) && !empty($fabrics)) {
                foreach ($sizes as $size) {
                    foreach ($colors as $color) {
                        foreach ($fabrics as $fabric) {
                            $sku = strtoupper(substr($product->name, 0, 3)) . "-" . strtoupper($size) . "-" . strtoupper($color) . "-" . strtoupper($fabric);
                            $product->combinations()->create([
                                'sku' => $sku,
                                'price' => $product->price,
                                'stock' => max(0, intval($product->stock / 3)),
                                'options' => [
                                    'size' => $size,
                                    'color' => $color,
                                    'fabric' => $fabric,
                                ],
                            ]);
                        }
                    }
                }
            }
        }
    }
}
