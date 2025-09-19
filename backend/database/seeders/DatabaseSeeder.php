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
                    ['type' => 'fabric', 'value' => 'cotton', 'price' => 2.50],
                    ['type' => 'fabric', 'value' => 'polyester', 'price' => -1.00],
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
                    ['type' => 'fabric', 'value' => 'stretch-denim', 'price' => 15.00],
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
            // New products with additional variant types
            [
                'name' => 'Athletic Shorts',
                'description' => 'Lightweight shorts for workouts.',
                'price' => 29.99,
                'stock' => 40,
                'variants' => [
                    ['type' => 'size', 'value' => 'S'],
                    ['type' => 'size', 'value' => 'M'],
                    ['type' => 'size', 'value' => 'L'],
                    ['type' => 'color', 'value' => 'black'],
                    ['type' => 'color', 'value' => 'navy'],
                    ['type' => 'length', 'value' => 'short'],
                    ['type' => 'fit', 'value' => 'regular'],
                    ['type' => 'fit', 'value' => 'slim'],
                ],
                'combinationSkipRule' => null,
            ],

            [
                'name' => 'Raincoat',
                'description' => 'Waterproof raincoat with taped seams.',
                'price' => 129.99,
                'stock' => 15,
                'variants' => [
                    ['type' => 'size', 'value' => 'M'],
                    ['type' => 'size', 'value' => 'L'],
                    ['type' => 'color', 'value' => 'yellow'],
                    ['type' => 'color', 'value' => 'black'],
                    ['type' => 'material', 'value' => 'waterproof', 'price' => 20.00],
                    ['type' => 'material', 'value' => 'breathable'],
                    ['type' => 'closure', 'value' => 'zipper'],
                    ['type' => 'closure', 'value' => 'buttons'],
                ],
                'combinationSkipRule' => function($opts) {
                    // Avoid yellow + buttons for stylistic reasons
                    return (isset($opts['color']) && isset($opts['closure']) && $opts['color'] === 'yellow' && $opts['closure'] === 'buttons');
                },
                // Explicit per-combination overrides. Each entry specifies an
                // 'options' map to match and may include 'price', 'price_delta',
                // 'stock', and/or 'sku' to override the generated combination.
                'explicit_combinations' => [
                    [
                        'options' => ['material' => 'waterproof', 'closure' => 'zipper'],
                        'price' => 189.99,
                        // optional: set a custom stock or sku for this combination
                        'stock' => 5,
                    ],
                ],
            ],

            [
                'name' => 'Silk Scarf',
                'description' => 'Premium silk scarf with multiple patterns.',
                'price' => 39.99,
                'stock' => 60,
                'variants' => [
                    ['type' => 'pattern', 'value' => 'striped'],
                    ['type' => 'pattern', 'value' => 'polka'],
                    ['type' => 'material', 'value' => 'silk', 'price' => 25.00],
                    ['type' => 'length', 'value' => 'long'],
                    ['type' => 'print', 'value' => 'logo'],
                    ['type' => 'print', 'value' => 'plain'],
                ],
                'combinationSkipRule' => null,
            ],

            [
                'name' => 'Cargo Pants',
                'description' => 'Durable cargo pants with multiple pockets.',
                'price' => 69.99,
                'stock' => 25,
                'variants' => [
                    ['type' => 'size', 'value' => 'M'],
                    ['type' => 'size', 'value' => 'L'],
                    ['type' => 'size', 'value' => 'XL'],
                    ['type' => 'color', 'value' => 'khaki'],
                    ['type' => 'color', 'value' => 'olive'],
                    ['type' => 'fit', 'value' => 'relaxed'],
                    ['type' => 'length', 'value' => 'regular'],
                    ['type' => 'length', 'value' => 'long'],
                ],
                'combinationSkipRule' => null,
            ],

            [
                'name' => 'Running Cap',
                'description' => 'Breathable running cap with adjustable closure.',
                'price' => 19.99,
                'stock' => 100,
                'variants' => [
                    ['type' => 'color', 'value' => 'red'],
                    ['type' => 'color', 'value' => 'black'],
                    ['type' => 'color', 'value' => 'blue'],
                    ['type' => 'closure', 'value' => 'snapback'],
                    ['type' => 'closure', 'value' => 'strapback'],
                    ['type' => 'print', 'value' => 'logo'],
                    ['type' => 'print', 'value' => 'plain'],
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

                // Allow optional per-variant price (delta). Expecting 'price' in variant definition if needed.
                // Build a unique description per variant using product, type and value
                $enDesc = sprintf('%s - %s: %s', $product->name, $v['type'], $v['value']);
                $daDesc = sprintf('%s - %s: %s', $product->name, $v['type'], $v['value']);

                $variantData = [
                    'variant_type_id' => $vt_id,
                    'value' => $v['value'],
                    'descriptions' => [
                        'en' => $enDesc,
                        'da' => $daDesc,
                    ],
                ];
                $variantData['price'] = array_key_exists('price', $v) ? $v['price'] : 0;
                $variantData['stock'] = array_key_exists('stock', $v) ? intval($v['stock']) : 0;

                $product->variants()->create($variantData);
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

            // Determine stock per combination. If variants have per-variant stock
            // values, compute combination stock as the minimum of the selected
            // variant stocks (non-zero values only). Otherwise distribute the
            // product stock across combos as before.
            $totalCombos = max(1, count($allCombos));
            $defaultDistributedStock = max(0, intval($product->stock / $totalCombos));
            $perComboStock = $defaultDistributedStock;

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

                // Compute combination price: start with base product price and add any
                // per-variant price deltas stored on the ProductVariant entries.
                $comboPrice = $product->price;
                foreach ($opts as $type => $val) {
                    $pv = $product->variants()->whereHas('variantType', function($q) use ($type) {
                        $q->where('name', $type);
                    })->where('value', $val)->first();
                    if ($pv && isset($pv->price) && $pv->price != 0) {
                        $comboPrice += floatval($pv->price);
                    }
                }

                // Apply any explicit per-combination overrides defined on the
                // product seed entry. These can override price, stock and sku.
                $explicit = $p['explicit_combinations'] ?? [];
                $appliedExplicit = false;
                foreach ($explicit as $ex) {
                    $match = $ex['options'] ?? [];
                    $isMatch = true;
                    foreach ($match as $k => $v) {
                        if (!isset($opts[$k]) || strtolower((string)$opts[$k]) !== strtolower((string)$v)) {
                            $isMatch = false;
                            break;
                        }
                    }
                    if ($isMatch) {
                        if (array_key_exists('price', $ex)) {
                            $comboPrice = floatval($ex['price']);
                        } elseif (array_key_exists('price_delta', $ex)) {
                            $comboPrice += floatval($ex['price_delta']);
                        }
                        if (array_key_exists('stock', $ex)) {
                            $perComboStock = intval($ex['stock']);
                        }
                        if (array_key_exists('sku', $ex)) {
                            $sku = $ex['sku'];
                        }
                        $appliedExplicit = true;
                        break;
                    }
                }

                // If no explicit override applied, compute per-combination stock
                // from per-variant stocks when available.
                if (! $appliedExplicit) {
                    $variantStocks = [];
                    foreach ($opts as $type => $val) {
                        $pv = $product->variants()->whereHas('variantType', function($q) use ($type) {
                            $q->where('name', $type);
                        })->where('value', $val)->first();
                        if ($pv && isset($pv->stock) && $pv->stock > 0) {
                            $variantStocks[] = intval($pv->stock);
                        }
                    }
                    if (!empty($variantStocks)) {
                        $perComboStock = min($variantStocks);
                    }
                }

                $product->combinations()->create([
                    'sku' => $sku,
                    'price' => $comboPrice,
                    'stock' => $perComboStock,
                    'options' => $opts,
                ]);
            }
        }
    }
}
