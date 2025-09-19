<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Product;
use App\Models\ProductVariant;

class CombinationPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_combination_price_is_sum_of_product_and_variant_prices()
    {
        // create product
        $product = Product::create(['name' => 'Test Coat', 'description' => 'test', 'price' => 100.00, 'stock' => 10]);

        // create variant types and variants (using raw DB for simplicity)
        $vt1 = \DB::table('variant_types')->insertGetId(['name' => 'material', 'created_at' => now(), 'updated_at' => now()]);
        $vt2 = \DB::table('variant_types')->insertGetId(['name' => 'color', 'created_at' => now(), 'updated_at' => now()]);

        // material: waterproof +20, breathable +0
        $product->variants()->create(['variant_type_id' => $vt1, 'value' => 'waterproof', 'price' => 20.00]);
        $product->variants()->create(['variant_type_id' => $vt1, 'value' => 'breathable', 'price' => 0.00]);

        // color: black +0, yellow +0
        $product->variants()->create(['variant_type_id' => $vt2, 'value' => 'black', 'price' => 0.00]);
        $product->variants()->create(['variant_type_id' => $vt2, 'value' => 'yellow', 'price' => 0.00]);

        // regenerate combinations using the seeder logic (simplified here)
        $variants = $product->variants()->with('variantType')->get();
        $valuesByType = [];
        foreach ($variants as $v) {
            $valuesByType[$v->variantType->name][] = $v->value;
        }
        foreach ($valuesByType as $k => $arr) {
            $valuesByType[$k] = array_values(array_unique($arr));
        }
        $typeNames = array_keys($valuesByType);
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

        foreach ($allCombos as $comboValues) {
            $opts = [];
            foreach ($comboValues as $i => $val) {
                $type = $typeNames[$i];
                $opts[$type] = $val;
            }

            // compute expected price
            $expected = $product->price;
            foreach ($opts as $type => $val) {
                $pv = $product->variants()->whereHas('variantType', function($q) use ($type) {
                    $q->where('name', $type);
                })->where('value', $val)->first();
                if ($pv && isset($pv->price)) {
                    $expected += floatval($pv->price);
                }
            }

            // create combination
            $comb = $product->combinations()->create(['sku' => 'TST', 'price' => $expected, 'stock' => 1, 'options' => $opts]);

            // reload and assert
            $this->assertEquals($expected, $comb->price);
        }
    }
}
