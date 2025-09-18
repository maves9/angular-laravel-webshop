<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
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
            'password' => Hash::make('password'),
        ]);

        // Clear existing products/variants/combinations to avoid duplicates when re-seeding
        DB::table('product_variant_combinations')->delete();
        DB::table('product_variants')->delete();
        DB::table('variant_types')->delete();
        DB::table('products')->delete();

        // If using SQLite, reset the sqlite_sequence table so autoincrement IDs start from 1.
        // Wrapped in try/catch so it is a no-op for other database drivers.
        try {
            DB::statement('DELETE FROM sqlite_sequence WHERE name="product_variant_combinations"');
            DB::statement('DELETE FROM sqlite_sequence WHERE name="product_variants"');
            DB::statement('DELETE FROM sqlite_sequence WHERE name="variant_types"');
            DB::statement('DELETE FROM sqlite_sequence WHERE name="products"');
            DB::statement('DELETE FROM sqlite_sequence WHERE name="users"');
        } catch (\Exception $e) {
            // ignore if not sqlite or sequence table doesn't exist
        }

        // Create sample products with variants. Each product can have any number of variant types.
        // We include an optional 'combinationSkipRule' for each product to intentionally omit some combinations.
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
                'combinationSkipRule' => function($opts) {
                    // Example: skip red+polyester combos
                    return (isset($opts['color']) && isset($opts['fabric']) && $opts['color'] === 'red' && $opts['fabric'] === 'polyester');
                },
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
                    ['type' => 'color', 'value' => 'white'],
                    ['type' => 'material', 'value' => 'linen'],
                ],
                'combinationSkipRule' => function($opts) {
                    // Example: skip XS + white (imagine that's not produced)
                    return (isset($opts['size']) && isset($opts['color']) && $opts['size'] === 'XS' && $opts['color'] === 'white');
                },
            ],

            [
                'name' => 'Denim Jacket',
                'description' => 'Classic denim jacket.',
                'price' => 89.99,
                'stock' => 10,
                'variants' => [
                    ['type' => 'color', 'value' => 'blue'],
                    ['type' => 'color', 'value' => 'black'],
                    ['type' => 'fabric', 'value' => 'denim'],
                    ['type' => 'fabric', 'value' => 'stretch-denim'],
                    ['type' => 'trim', 'value' => 'standard'],
                ],
                'combinationSkipRule' => function($opts) {
                    // Example: skip black + stretch-denim
                    return (isset($opts['color']) && isset($opts['fabric']) && $opts['color'] === 'black' && $opts['fabric'] === 'stretch-denim');
                },
            ],

            [
                'name' => 'Striped Hoodie',
                'description' => 'Cozy striped hoodie.',
                'price' => 59.99,
                'stock' => 30,
                'variants' => [
                    ['type' => 'size', 'value' => 'S'],
                    ['type' => 'size', 'value' => 'M'],
                    ['type' => 'size', 'value' => 'L'],
                ],
                'combinationSkipRule' => null,
            ],
        ];

        foreach ($products as $p) {
            $product = Product::create([
                'name' => $p['name'],
                'description' => $p['description'],
                'price' => $p['price'],
                'stock' => $p['stock'],
            ]);

            // Create variant types if they don't exist and store variant_type_id when creating variants
            foreach ($p['variants'] as $v) {
                $vt = DB::table('variant_types')->where('name', $v['type'])->first();
                if (!$vt) {
                    $vt_id = DB::table('variant_types')->insertGetId(['name' => $v['type'], 'created_at' => now(), 'updated_at' => now()]);
                } else {
                    $vt_id = $vt->id;
                }
                $product->variants()->create(['variant_type_id' => $vt_id, 'value' => $v['value']]);
            }
            // Build combinations generically based on whatever variant types exist for this product
            $variants = $product->variants()->with('variantType')->get();

            // Group values by variant type name (e.g., 'size' => ['S','M'], 'color' => ['red'])
            $valuesByType = [];
            foreach ($variants as $v) {
                if (!$v->variantType) continue;
                $name = $v->variantType->name;
                $valuesByType[$name][] = $v->value;
            }

            // Unique and reindex
            foreach ($valuesByType as $k => $arr) {
                $valuesByType[$k] = array_values(array_unique($arr));
            }

            $typeNames = array_keys($valuesByType);
            if (empty($typeNames)) continue; // no variants

            // Prepare lists in order of typeNames for cartesian product
            $lists = array_values($valuesByType);

            // Simple iterative Cartesian product generator (returns array of arrays)
            $cartesianProduct = function(array $arrays) {
                $result = [[]];
                foreach ($arrays as $values) {
                    $append = [];
                    foreach ($result as $productCombo) {
                        foreach ($values as $v) {
                            $append[] = array_merge($productCombo, [$v]);
                        }
                    }
                    $result = $append;
                }
                return $result;
            };

            $allCombos = $cartesianProduct($lists);

            // Determine stock per combination (distribute product stock across combos)
            $totalCombos = max(1, count($allCombos));
            $perComboStock = max(0, intval($product->stock / $totalCombos));

            // Optional skip rule provided per product
            $combinationSkipRule = $p['combinationSkipRule'] ?? null;

            // SKU builder: prefix with product initials then joined values
            $buildSku = function($product, $opts) {
                $parts = [strtoupper(substr($product->name, 0, 3))];
                foreach ($opts as $v) {
                    $parts[] = strtoupper((string)$v);
                }
                return implode('-', $parts);
            };

            foreach ($allCombos as $comboValues) {
                // Map back to type names
                $opts = [];
                foreach ($comboValues as $i => $val) {
                    $type = $typeNames[$i];
                    $opts[$type] = $val;
                }

                // Skip according to rule when provided
                if (is_callable($combinationSkipRule) && $combinationSkipRule($opts)) {
                    continue;
                }

                $sku = $buildSku($product, $opts);
                $product->combinations()->create([
                    'sku' => $sku,
                    'price' => $product->price,
                    'stock' => $perComboStock,
                    'options' => $opts,
                ]);
            }
        }
    }
}
