<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::with(['variants', 'combinations'])->get()->map(function ($product) {
            return $this->formatProduct($product);
        });

        return response()->json($products);
    }

    private function formatProduct(Product $product): array
    {
        $variants = $product->variants->load('variantType');
        $variantOptions = [];
        foreach ($variants as $v) {
            if (!$v->variantType || !$v->variantType->name) {
                continue;
            }

            $typeName = strtolower($v->variantType->name);
            // store objects with value and price (price may be 0)
            $variantOptions[$typeName][] = [
                'value' => $v->value,
                'price' => isset($v->price) ? floatval($v->price) : 0.0,
                // include descriptions (array of locale => text) so frontend can choose locale
                'descriptions' => isset($v->descriptions) ? $v->descriptions : null,
            ];
        }

        // make unique by value and reindex
        foreach ($variantOptions as $k => $items) {
            $unique = [];
            foreach ($items as $it) {
                $unique[$it['value']] = $it;
            }
            $variantOptions[$k] = array_values($unique);
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'stock' => $product->stock,
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,
            'variant_options' => $variantOptions,
            'combinations' => $product->combinations->map(function ($c) use ($product) {
                // Build a human readable description for the combination by
                // aggregating variant descriptions for the chosen options when available.
                $comboDesc = null;
                $opts = $c->options ?? [];
                $parts = [];
                foreach ($opts as $type => $val) {
                    $pv = $product->variants->first(function($x) use ($type, $val) {
                        return optional($x->variantType)->name && strtolower(optional($x->variantType)->name) === strtolower($type) && (string)$x->value === (string)$val;
                    });
                    if ($pv && is_array($pv->descriptions)) {
                        // prefer English if available
                        if (!empty($pv->descriptions['en'])) $parts[] = $pv->descriptions['en'];
                        else $parts[] = array_values($pv->descriptions)[0] ?? null;
                    }
                }
                if (!empty($parts)) $comboDesc = implode(' — ', array_filter($parts));

                return [
                    'id' => $c->id,
                    'sku' => $c->sku,
                    'price' => $c->price,
                    'stock' => $c->stock,
                    'options' => $c->options,
                    'description' => $comboDesc,
                ];
            })->values(),
        ];
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['variants', 'combinations']);
        return response()->json($this->formatProduct($product));
    }

    public function combinations(Product $product): JsonResponse
    {
        $product->load('combinations');
        return response()->json($product->combinations);
    }

    public function findCombination(Request $request, Product $product): JsonResponse
    {
        $params = array_filter($request->query(), function($v) { return $v !== null && $v !== ''; });
        $query = $product->combinations()->newQuery();

        if (empty($params)) {
            return response()->json(['message' => 'No variant query parameters provided'], 400);
        }

        foreach ($params as $name => $value) {
            $query->whereJsonContains("options->{$name}", $value);
        }

        $combination = $query->first();

        if (!$combination) {
            return response()->json(null, 404);
        }

        // Build a description for this combination from the product's variants
        $comboDesc = null;
        $opts = $combination->options ?? [];
        $parts = [];
        foreach ($opts as $type => $val) {
            $pv = $product->variants->first(function($x) use ($type, $val) {
                return optional($x->variantType)->name && strtolower(optional($x->variantType)->name) === strtolower($type) && (string)$x->value === (string)$val;
            });
            if ($pv && is_array($pv->descriptions)) {
                if (!empty($pv->descriptions['en'])) $parts[] = $pv->descriptions['en'];
                else $parts[] = array_values($pv->descriptions)[0] ?? null;
            }
        }
        if (!empty($parts)) $comboDesc = implode(' — ', array_filter($parts));

        $out = $combination->toArray();
        $out['description'] = $comboDesc;
        return response()->json($out);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'variants' => 'array',
        ]);

        $product = Product::create($data);

        if (!empty($data['variants'])) {
            foreach ($data['variants'] as $v) {
                $product->variants()->create($v);
            }
        }

        return response()->json($product->load('variants'), 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric',
            'stock' => 'sometimes|integer',
            'variants' => 'array',
        ]);

        $product->update($data);

        if (isset($data['variants'])) {
            $product->variants()->delete();
            foreach ($data['variants'] as $v) {
                $product->variants()->create($v);
            }
        }

        return response()->json($product->load('variants'));
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();
        return response()->json(null, 204);
    }
}

